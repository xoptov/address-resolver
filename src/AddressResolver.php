<?php

namespace Xoptov\AddressResolver;

use PDO;
use StdClass;
use Exception;
use Respect\Validation\Validator;
use Xoptov\AddressResolver\Model\Region;
use Xoptov\AddressResolver\Model\Address;
use Xoptov\AddressResolver\Model\Locality;
use Xoptov\AddressResolver\Model\Location;
use Xoptov\AddressResolver\Model\Coordinate;
use Xoptov\AddressResolver\Exception\AddressResolveException;

class AddressResolver
{
	/** @var PDO */
	private $pdo;

	/** @var AddressManager */
	private $addressManager;

	/** @var LocalityManager */
	private $localityManager;

	/** @var RegionManager */
	private $regionManager;

	/** @var GeoCoderInterface */
	private $goeCoder;

	/** @var DaData */
	private $daData;

	/**
	 * AddressResolver constructor.
	 *
	 * @param PDO $pdo
	 * @param AddressManager $addressManager
	 * @param LocalityManager $localityManager
	 * @param RegionManager $regionManager
	 * @param GeoCoderInterface $geoCoder
	 * @param DaData $daData
	 */
	public function __construct(PDO $pdo, AddressManager $addressManager, LocalityManager $localityManager, RegionManager $regionManager, GeoCoderInterface $geoCoder, DaData $daData)
	{
		$this->pdo = $pdo;
		$this->addressManager = $addressManager;
		$this->localityManager = $localityManager;
		$this->regionManager = $regionManager;
		$this->goeCoder = $geoCoder;
		$this->daData = $daData;
	}

	/**
	 * @param Location $location
	 * @return Address
	 * @throws AddressResolveException
	 * @throws Exception
	 */
	public function resolve(Location $location)
	{
		$address = $this->addressManager->getByLocation($location);

		if ($address) {
			return $address;
		}

		$locality = $this->localityManager->getByLocation($location);

		if ($locality instanceof Locality) {
			$address = $this->addressManager->getByFiasId($locality->getFiasId());

			if ($address) {
				return $address;
			}
		}

		$addressName = $this->goeCoder->getNameByCoordinate($location->getCoordinate());

		if ($addressName) {
			$addressObject = $this->daData->getAddressObject($addressName);
		} elseif ($location->getName()) {
			$addressObject = $this->daData->getAddressObject($location->getName());
		} else {
			return null;
		}

		Validator::attribute("fias_id")->check($addressObject);

		$address = $this->addressManager->getByFiasId($addressObject->fias_id);
		// Если есть уже такой адрес значит у него не корректные координаты и их нужно исправить.
		if ($address) {
			$address->setCoordinate($location->getCoordinate());
			$this->addressManager->update($address);

			return $address;
		}

		return $this->createFromDaData($addressObject, $location->getCoordinate());
	}

	/**
	 * @param Locality $locality
	 * @param bool $persist
	 * @return Address
	 * @throws AddressResolveException
	 */
	private function createFromLocality(Locality $locality, $persist = false)
	{
		$region = $locality->getRegion();
		if (!$region instanceof Region && is_numeric($region)) {
			$region = $this->regionManager->getById($region);
		}

		if (!$region) {
			throw new AddressResolveException("Can not retrieve region by locality.");
		}

		$address = $this->addressManager->create($locality->getFiasId(), $locality->getName(), $region, $locality, $locality->getCoordinate(), true);

		if ($persist) {
			if (!$this->addressManager->insert($address) && $this->pdo->inTransaction()) {
				$this->pdo->rollBack();

				throw new \PDOException("Can not inserting new address from locality.");
			}
		}

		return $address;
	}

	/**
	 * @param StdClass $addressObject
	 * @return Address
	 * @throws AddressResolveException
	 * @throws Exception
	 */
	private function createFromDaData(StdClass $addressObject, Coordinate $coordinate)
	{
		$validator = Validator::create();

		$validator->addRules(array(
			Validator::attribute("result", Validator::notBlank()),
			Validator::attribute("fias_id", Validator::notBlank()),
			Validator::attribute("region_fias_id", Validator::notBlank()),
			Validator::oneOf(
				Validator::attribute("city_fias_id", Validator::notBlank()),
				Validator::attribute("settlement_fias_id", Validator::notBlank())
			)
		));

		$validator->check($addressObject);
		$locality = $this->localityManager->getByFiasId($addressObject->city_fias_id, $addressObject->settlement_fias_id);

		if ($locality instanceof Locality) {
			// Если мы нашли населенный пункт по FiasId нужно проверить его координаты, и если координат нет то
			// необходимо выставить новые координаты.
			if ($locality->getCoordinate() === null) {
				$locality->setCoordinate($coordinate);

				if (!$this->localityManager->update($locality)) {
					throw new \PDOException("Error updating locality coordinates.");
				}
			}

			if ($address = $this->addressManager->getByFiasId($locality->getFiasId())) {
				return $address;
			}

			return $this->createFromLocality($locality, true);
		}

		//Если мы ненашли населенный пункт в нашей БД то создаём заного Регион, Населенный пункт и адрес по данным DaData.
		$this->pdo->beginTransaction();

		$region = $this->regionManager->getByFiasId($addressObject->region_fias_id);

		if (!$region) {
			$region = $this->daData->createRegion($this->regionManager, $addressObject);

			if (!$this->regionManager->insert($region)) {
				$this->pdo->rollBack();

				throw new \PDOException("Error inserting new region.");
			}
		}

		$locality = $this->daData->createLocality($this->localityManager, $addressObject, $region, $coordinate);
		$locality->setRegion($region);

		if (!$this->localityManager->insert($locality)) {
			$this->pdo->rollBack();

			throw new \PDOException("Error inserting new locality.");
		}

		$address = $this->createFromLocality($locality, true);

		if ($this->pdo->commit()) {
			return $address;
		}

		throw new \PDOException("Error creating address from DaData.");
	}
}