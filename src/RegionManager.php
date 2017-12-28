<?php

namespace Xoptov\AddressResolver;

use PDO;
use Ds\Vector;
use Xoptov\AddressResolver\Model\Region;

class RegionManager
{
	/** @var PDO */
	private $pdo;

	/** @var Vector */
	private $buffer;

	/**
	 * RegionManager constructor.
	 *
	 * @param PDO $pdo
	 */
	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
		$this->buffer = new Vector();
	}

	/**
	 * @param int $id
	 * @return Region|null
	 */
	public function getById($id)
	{
		$result = $this->buffer->filter(function(Region $region) use ($id) {
			If ($region->getId() == $id) {
				return true;
			}

			return false;
		});

		if ($result->count()) {
			return $result->first();
		}

		$sql = "SELECT id, fias_id AS fiasId, name, type FROM region WHERE id = :id LIMIT 1";
		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(":id", $id);

		if ($stmt->execute()) {
			$region = $stmt->fetchObject(Region::class);
			if ($region instanceof Region) {
				$this->buffer->push($region);

				return $region;
			}
		}

		return null;
	}

	/**
	 * @param string $fiasId
	 * @return Region|null
	 */
	public function getByFiasId($fiasId)
	{
		$result = $this->buffer->filter(function(Region $region) use ($fiasId) {
			if ($region->getFiasId() === $fiasId) {
				return true;
			}

			return false;
		});

		if ($result->count()) {
			return $result->first();
		}

		$sql = "SELECT id, fias_id AS fiasId, name, type FROM region WHERE fias_id = :fias_id LIMIT 1";
		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(":fias_id", $fiasId);

		if ($stmt->execute()) {
			$region = $stmt->fetchObject(Region::class);
			if ($region instanceof Region) {
				$this->buffer->push($region);

				return $region;
			}
		}

		return null;
	}

	/**
	 * @param string $fiasId
	 * @param string $name
	 * @param string $type
	 * @param bool $buffered
	 * @return Region
	 */
	public function create($fiasId, $name, $type, $buffered = false)
	{
		$region = new Region();
		$region
			->setFiasId($fiasId)
			->setName($name)
			->setType($type);

		if ($buffered) {
			$this->buffer->push($region);
		}

		return $region;
	}

	/**
	 * @param Region $region
	 * @return bool
	 */
	public function insert(Region $region)
	{
		$sql = "
			INSERT INTO region SET
				fias_id = :fias_id,
				name = :name,
				`type` = :type,
				created_at = NOW(),
				enabled = true
		";

		$stmt = $this->pdo->prepare($sql);
		$this->bindValues($stmt, $region);

		if ($stmt->execute()) {
			$insertId = $this->pdo->lastInsertId();
			$region->setId($insertId);

			return true;
		}

		return false;
	}

	/**
	 * @param Region $region
	 * @return bool
	 */
	public function update(Region $region)
	{
		$sql = "
			UPDATE region SET
				fias_id = :fias_id,
				name = :name,
				type = :type,
				updated_at = NOW()
			WHERE id = :id";

		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(":id", $region->getId());
		$this->bindValues($stmt, $region);

		return $stmt->execute();
	}

	/**
	 * @param \PDOStatement $stmt
	 * @param Region $region
	 */
	private function bindValues(\PDOStatement $stmt, Region $region)
	{
		$stmt->bindValue(":fias_id", $region->getFiasId());
		$stmt->bindValue(":name", $region->getName());
		$stmt->bindValue(":type", $region->getType());
	}
}