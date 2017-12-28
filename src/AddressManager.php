<?php

namespace Xoptov\AddressResolver;

use PDO;
use Exception;
use Ds\Vector;
use PDOStatement;
use Xoptov\AddressResolver\Model\Region;
use Xoptov\AddressResolver\Model\Address;
use Xoptov\AddressResolver\Model\Locality;
use Xoptov\AddressResolver\Model\Location;
use Xoptov\AddressResolver\Model\Coordinate;

class AddressManager
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
	public function __construct(PDO $pdo, CoordinateManager $coordinateManager, $radius = 0.5)
	{
		$this->pdo = $pdo;
		$this->coordinateManager = $coordinateManager;
		$this->radius = $radius;

		$this->buffer = new Vector();
	}

	/**
	 * @param Location $location
	 * @return Address|null
	 * @throws Exception
	 */
	public function getByLocation(Location $location)
	{
		$result = $this->buffer->filter(function(Address $address) use ($location) {
			$coordinate = $address->getCoordinate();

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

			$pattern = sprintf("/%s/i", $location->getName());

			if (preg_match($pattern, $address->getValue()) && vincenty($to, $from) / 1000 <= $this->radius) {
				return true;
			}

			return false;
		});

		if ($result->count()) {
			return $result->first();
		}

		$sql = "
			SELECT a.id,
				a.region_id AS region,
				a.locality_id AS locality,
				a.fias_id AS fiasId,
				a.`value`,
				ST_AsWKB(a.coordinate) AS coordinate_wkb,
				ST_Distance_Sphere(a.coordinate, POINT(:longitude, :latitude)) AS distance 
			FROM address a
			WHERE a.value LIKE :value
				AND ST_Distance_Sphere(a.coordinate, POINT(:longitude, :latitude)) / 1000 <= :radius
			ORDER BY distance ASC
			LIMIT 1
		";

		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(":value", '%' . $location->getName() . '%', PDO::PARAM_STR);
		$stmt->bindValue(":longitude", $location->getLongitude());
		$stmt->bindValue(":latitude", $location->getLatitude());
		$stmt->bindValue(":radius", $this->radius);

		if ($stmt->execute()) {
			$address = $stmt->fetchObject(Address::class);
			if ($address instanceof Address) {
				$address->setCoordinate($this->coordinateManager->createFromWKB($address->coordinate_wkb));
				$this->buffer->push($address);

				return $address;
			}
		}

		return null;
	}

	/**
	 * @param string $fiasId
	 * @return Address|null
	 * @throws Exception
	 */
	public function getByFiasId($fiasId)
	{
		$result = $this->buffer->filter(function($value) use ($fiasId) {
			/** @var Address $value */
			if ($value->getFiasId() === $fiasId) {
				return true;
			}

			return false;
		});

		if ($result->count()) {
			return $result->first();
		}

		$sql = "
			SELECT id, region_id AS region, locality_id AS locality, fias_id AS fiasId, `value`, ST_AsWKB(coordinate) AS coordinate_wkb
			FROM address
			WHERE fias_id = :fias_id
			LIMIT 1
		";

		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(":fias_id", $fiasId, PDO::PARAM_STR);

		if ($stmt->execute()) {
			$address = $stmt->fetchObject(Address::class);
			if ($address instanceof Address) {
				$address->setCoordinate($this->coordinateManager->createFromWKB($address->coordinate_wkb));
				$this->buffer->push($address);

				return $address;
			}
		}

		return null;
	}

	/**
	 * @param string $fiasId
	 * @param string $value
	 * @param Region $region
	 * @param Locality $locality
	 * @param Coordinate $coordinate
	 * @param bool $buffered
	 * @return Address
	 */
	public function create($fiasId, $value, Region $region, Locality $locality, Coordinate $coordinate, $buffered = false)
	{
		$address = new Address();
		$address
			->setFiasId($fiasId)
			->setValue($value)
			->setRegion($region)
			->setLocality($locality)
			->setCoordinate($coordinate);

		if ($buffered) {
			$this->buffer->push($address);
		}

		return $address;
	}

	/**
	 * @param Address $address
	 * @return bool
	 */
	public function insert(Address $address)
	{
		$sql = "
			INSERT INTO address SET
				fias_id = :fias_id,
				region_id = :region_id,
				locality_id = :locality_id,
				coordinate = ST_PointFromWKB(:coordinate_wkb),
				`value` = :value,
				standard = true
			";

		$stmt = $this->pdo->prepare($sql);
		$this->bindValues($stmt, $address);

		if ($stmt->execute()) {
			$insertId = $this->pdo->lastInsertId();
			$address->setId($insertId);

			return true;
		}

		return false;
	}

	/**
	 * @param Address $address
	 * @return boolean
	 */
	public function update(Address $address)
	{
		if ($address->getId() == null) {
			throw new \InvalidArgumentException("Address model must have id for update!");
		}

		$sql = "
			UPDATE address SET
				fias_id = :fias_id,
				region_id = :region_id,
				locality_id = :locality_id,
				coordinate = ST_PointFromWKB(:coordinate_wkb),
				`value` = :value
			  WHERE id = :id
		";

		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(":id", $address->getId());
		$this->bindValues($stmt, $address);

		return $stmt->execute();
	}

	/**
	 * @param PDOStatement $stmt
	 * @param Address $address
	 */
	private function bindValues(PDOStatement $stmt, Address $address)
	{
		$stmt->bindValue(":fias_id", $address->getFiasId());
		$stmt->bindValue(":value", $address->getValue());

		$region = $address->getRegion();
		if ($region instanceof Region && $region->getId()) {
			$stmt->bindValue(":region_id", $region->getId());
		} elseif (!empty($region)) {
			$stmt->bindValue(":region_id", $region);
		} else {
			$stmt->bindValue(":region_id", null);
		}

		$locality = $address->getLocality();
		if ($locality instanceof Locality) {
			$stmt->bindValue(":locality_id", $locality->getId());
		} elseif (!empty($locality)) {
			$stmt->bindValue(":locality_id", $locality);
		} else {
			$stmt->bindValue(":locality_id",null);
		}

		$coordinate = $address->getCoordinate();

		if ($coordinate instanceof Coordinate) {
			$coordinateWKB = $this->coordinateManager->readAsWKB($coordinate);
			$stmt->bindValue(":coordinate_wkb", $coordinateWKB);
		} elseif (isset($address->coordinate_wkb) && !empty($address->coordinate_wkb)) {
			$stmt->bindValue(":coordinate_wkb", $address->coordinate_wkb);
		} else {
			$stmt->bindValue(":coordinate_wkb", null);
		}
	}
}