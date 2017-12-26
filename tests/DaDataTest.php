<?php

namespace Xoptov\AddressResolver\Tests;

use PHPUnit\Framework\TestCase;
use Xoptov\AddressResolver\DaData;
use Xoptov\AddressResolver\Model\Region;
use Xoptov\AddressResolver\RegionManager;
use Xoptov\AddressResolver\Model\Locality;
use Xoptov\AddressResolver\LocalityManager;
use Xoptov\AddressResolver\Model\Coordinate;

class DaDataTest extends TestCase
{
	public function testGetData()
	{
		$service = new DaData(DADATA_API, DADATA_KEY, DADATA_SECRET);
		$data = $service->getAddressObject("Краснодар");

		$this->assertInstanceOf(\StdClass::class, $data);
		$this->assertEquals("Краснодар", $data->city);
		$this->assertEquals("Краснодарский", $data->region);
	}

	public function testCreateRegion()
	{
		$addressObject = new \StdClass();
		$addressObject->region = "Краснодарский";
		$addressObject->region_fias_id = "d00e1013-16bd-4c09-b3d5-3cb09fc54bd8";
		$addressObject->region_type_full = "край";

		$pdo = $this->createMock(\PDO::class);
		$regionManager = new RegionManager($pdo);

		$service = new DaData(DADATA_API, DADATA_KEY, DADATA_SECRET);
		$region = $service->createRegion($regionManager, $addressObject);

		$this->assertInstanceOf(Region::class, $region);
		$this->assertEquals($addressObject->region, $region->getName());
		$this->assertEquals($addressObject->region_fias_id, $region->getFiasId());
		$this->assertEquals($addressObject->region_type_full, $region->getType());
	}

	public function testCreateLocalityForCity()
	{
		$addressObject = new \StdClass();
		$addressObject->city = "Омск";
		$addressObject->city_fias_id = "140e31da-27bf-4519-9ea0-6185d681d44e";
		$addressObject->city_type_full = "город";
		$addressObject->settlement = null;
		$addressObject->settlement_fias_id = null;
		$addressObject->settlement_type_full = null;

		$pdo = $this->createMock(\PDO::class);
		$localityManager = new LocalityManager($pdo);

		$region = new Region();
		$region
			->setId(1)
			->setFiasId("05426864-466d-41a3-82c4-11e61cdc98ce")
			->setName("Омская")
			->setType("область");

		$coordinate = $this->createMock(Coordinate::class);

		$service = new DaData(DADATA_API, DADATA_KEY, DADATA_SECRET);
		$locality = $service->createLocality($localityManager, $addressObject, $region, $coordinate);

		$this->assertInstanceOf(Locality::class, $locality);
		$this->assertEquals($addressObject->city, $locality->getName());
		$this->assertEquals($addressObject->city_fias_id, $locality->getFiasId());
		$this->assertEquals($addressObject->city_type_full, $locality->getType());
	}

	public function testCreateLocalityForSettlement()
	{
		$addressObject = new \StdClass();
		$addressObject->city = null;
		$addressObject->city_fias_id = null;
		$addressObject->city_type_full = null;
		$addressObject->settlement = "Омский уезд";
		$addressObject->settlement_fias_id = "140e31da-27bf-4519-9ea0-6185d681d44e";
		$addressObject->settlement_type_full = "поселок";

		$pdo = $this->createMock(\PDO::class);
		$localityManager = new LocalityManager($pdo);

		$region = new Region();
		$region
			->setId(1)
			->setFiasId("05426864-466d-41a3-82c4-11e61cdc98ce")
			->setName("Омская")
			->setType("область");

		$coordinate = $this->createMock(Coordinate::class);

		$service = new DaData(DADATA_API, DADATA_KEY, DADATA_SECRET);
		$locality = $service->createLocality($localityManager, $addressObject, $region, $coordinate);

		$this->assertInstanceOf(Locality::class, $locality);
		$this->assertEquals($addressObject->settlement, $locality->getName());
		$this->assertEquals($addressObject->settlement_fias_id, $locality->getFiasId());
		$this->assertEquals($addressObject->settlement_type_full, $locality->getType());
	}
}