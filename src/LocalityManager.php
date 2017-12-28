<?php

namespace Xoptov\AddressResolver;

use PDO;
use Exception;
use Ds\Vector;
use Xoptov\AddressResolver\Model\Region;
use Xoptov\AddressResolver\Model\Locality;
use Xoptov\AddressResolver\Model\Location;
use Xoptov\AddressResolver\Model\Coordinate;

class LocalityManager
{
	/** @var PDO */
	private $pdo;

	/** @var CoordinateManager */
	private $coordinateManager;

	/** @var float */
	private $radius;

	/** @var Vector */
	private $buffer;

	/**
	 * @param PDO $pdo
	 * @param CoordinateManager $coordinateManager
	 * @param float $radius
	 */
	public function __construct(PDO $pdo, CoordinateManager $coordinateManager, $radius = 10.0)
	{
		$this->pdo = $pdo;
		$this->coordinateManager = $coordinateManager;
		$this->radius = $radius;

		$this->buffer = new Vector();
	}

	/**
	 * @param Location $location
	 * @return Locality|null
	 * @throws Exception
	 */
	public function getByLocation(Location $location)
	{
		$result = $this->buffer->filter(function(Locality $locality) use ($location) {
			$coordinate = $locality->getCoordinate();

			if (empty($coordinate)) {
				return false;
			}

			$from = array(
				"type" => "Point",
				"coordinates" => array($location->getLongitude(), $location->getLatitude())
			);

			$to = array(
				"type" => "Point",
				"coordinates" => array($coordinate->getLongitude(), $coordinate->getLatitude())
			);

			if ($locality->getName() === $location->getName() && vincenty($to, $from) / 1000 <= $this->radius) {
				return true;
			}

			return false;
		});

		if ($result->count()) {
			return $result->first();
		}

		$sql = "
			SELECT id, fias_id AS fiasId, `name`, `type`, region_id AS region, ST_AsWKB(coordinate) AS coordinate_wkb
			FROM locality
			WHERE name = :name
				AND ST_Distance_Sphere(coordinate, POINT(:longitude, :latitude)) / 1000 <= :radius
			LIMIT 1
		";

		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(":name", $location->getName(), \PDO::PARAM_STR);
		$stmt->bindValue(":longitude", $location->getLongitude());
		$stmt->bindValue(":latitude", $location->getLatitude());
		$stmt->bindValue(":radius", $this->radius);

		if ($stmt->execute()) {
			$locality = $stmt->fetchObject(Locality::class);

			if ($locality instanceof Locality) {
				$locality->setCoordinate($this->coordinateManager->createFromWKB($locality->coordinate_wkb));
				$this->buffer->push($locality);

				return $locality;
			}
		}

		return null;
	}

	/**
	 * @param string $localityFiasId
	 * @param string $settlementFiasId
	 * @return Locality|null
	 * @throws Exception
	 */
	public function getByFiasId($localityFiasId, $settlementFiasId)
	{
		$result = $this->buffer->filter(function (Locality $locality) use ($localityFiasId, $settlementFiasId) {
			if ($locality->getFiasId() === $localityFiasId || $locality->getFiasId() === $settlementFiasId) {
				return true;
			}

			return false;
		});

		if ($result->count()) {
			return $result->first();
		}

		$sql = "
			SELECT id, fias_id AS fiasId, `name`, `type`, region_id AS region, ST_AsWKB(coordinate) AS coordinate_wkb
			FROM locality
			WHERE fias_id = :locality_fias_id
				OR fias_id = :settlement_fias_id
			LIMIT 1
		";

		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(":locality_fias_id", $localityFiasId);
		$stmt->bindValue(":settlement_fias_id", $settlementFiasId);

		if ($stmt->execute()) {
			$locality = $stmt->fetchObject(Locality::class);
			if ($locality instanceof Locality) {
				$locality->setCoordinate($this->coordinateManager->createFromWKB($locality->coordinate_wkb));
				$this->buffer->push($locality);

				return $locality;
			}
		}

		return null;
	}

	/**
	 * @param string $fiasId
	 * @param string $name
	 * @param string $type
	 * @param Region $region
	 * @param Coordinate $coordinate
	 * @return Locality
	 */
	public function create($fiasId, $name, $type, Region $region, Coordinate $coordinate, $buffered = false)
	{
		$locality = new Locality();
		$locality
			->setFiasId($fiasId)
			->setName($name)
			->setType($type)
			->setRegion($region)
			->setCoordinate($coordinate);

		if ($buffered) {
			$this->buffer->push($locality);
		}

		return $locality;
	}

	/**
	 * @param Locality $locality
	 * @return bool
	 */
	public function insert(Locality $locality)
	{
		$sql = "
		  	INSERT INTO locality SET
				fias_id = :fias_id,
				`name` = :name,
				`type` = :type,
				region_id = :region_id,
				coordinate = ST_PointFromWKB(:coordinate_wkb),
				enabled = true, 
				as_default = false,
				created_at = NOW(),
				center = false
		";

		$stmt = $this->pdo->prepare($sql);
		$this->bindValues($stmt, $locality);

		if ($stmt->execute()) {
			$insertId = $this->pdo->lastInsertId();
			$locality->setId($insertId);

			return true;
		}

		return false;
	}

	/**
	 * @param Locality $locality
	 * @return bool
	 */
	public function update(Locality $locality)
	{
		$sql = "
			UPDATE locality SET
				fias_id = :fias_id,
				`name` = :name,
				`type` = :type,
				region_id = :region_id,
				coordinate = ST_PointFromWKB(:coordinate_wkb),
				updated_at = NOW()
			WHERE id = :id
		";

		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(":id", $locality->getId());
		$this->bindValues($stmt, $locality);

		return $stmt->execute();
	}

	/**
	 * @param \PDOStatement $stmt
	 * @param Locality $locality
	 */
	private function bindValues(\PDOStatement $stmt, Locality $locality)
	{
		$stmt->bindValue(":fias_id", $locality->getFiasId());
		$stmt->bindValue(":name", $locality->getName());
		$stmt->bindValue(":type", $locality->getType());

		$region = $locality->getRegion();
		if ($region instanceof Region) {
			$stmt->bindValue(":region_id", $region->getId());
		} elseif (!empty($region)) {
			$stmt->bindValue(":region_id", $region);
		} else {
			$stmt->bindValue(":region_id", null);
		}

		$coordinate = $locality->getCoordinate();
		if ($coordinate instanceof Coordinate) {
			$stmt->bindValue(":coordinate_wkb", $this->coordinateManager->readAsWKB($coordinate));
		} else {
			$stmt->bindValue(":coordinate_wkb", null);
		}
	}
}