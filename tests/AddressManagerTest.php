<?php

namespace Xoptov\AddressResolver\Tests\Service;

use PHPUnit\Framework\TestCase;
use Xoptov\AddressResolver\Model\Region;
use Xoptov\AddressResolver\Model\Address;
use Xoptov\AddressResolver\Model\Locality;
use Xoptov\AddressResolver\AddressManager;
use Xoptov\AddressResolver\LocationManager;
use Xoptov\AddressResolver\Model\Coordinate;

class AddressManagerTest extends TestCase
{
	/** @var \PDO */
	private static $pdo;

	public static function setUpBeforeClass()
	{
		static::$pdo = new \PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
	}

	public function testGetByLocation()
	{
		$addressManager = new AddressManager(static::$pdo);
		$locationManager = new LocationManager();
		$location = $locationManager->create("Краснодар", 45.0263, 39.0382);

		$address = $addressManager->getByLocation($location);
		$this->assertInstanceOf(Address::class, $address);
	}

	public function testGetByFiasId()
	{
		$addressManager = new AddressManager(static::$pdo);
		$address = $addressManager->getByFiasId("bb36e20f-6946-4603-b7ee-61cdc084e077");

		$this->assertInstanceOf(Address::class, $address);
	}

	public function testCreate()
	{
		$region = new Region();
		$locality = new Locality();
		$coordinate = new Coordinate(45.0263, 39.0382);
		$addressManager = new AddressManager(static::$pdo);
		$address = $addressManager->create("bb36e20f-6946-4603-b7ee-61cdc084e077", "г Краснодар, ул Старокубанская", $region, $locality, $coordinate);

		$this->assertInstanceOf(Address::class, $address);
		$this->assertNull($address->getId());
		$this->assertEquals("bb36e20f-6946-4603-b7ee-61cdc084e077", $address->getFiasId());
		$this->assertEquals("г Краснодар, ул Старокубанская", $address->getValue());
		$this->assertEquals($region, $address->getRegion());
		$this->assertEquals($locality, $address->getLocality());
		$this->assertEquals($coordinate, $address->getCoordinate());
	}

	public function testInsert()
	{
		$address = $this->createMock(Address::class);
		$address->expects($this->once())->method("setId");

		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->expects($this->once())->method("execute")->willReturn(true);

		$pdo = $this->createMock(\PDO::class);
		$pdo->expects($this->once())->method("prepare")->willReturn($stmt);
		$pdo->expects($this->once())->method("lastInsertId")->willReturn(1);

		$addressManager = new AddressManager($pdo);
		$addressManager->insert($address);
	}

	public function testUpdate()
	{
		$address = $this->createMock(Address::class);
		$address->expects($this->exactly(2))
		        ->method("getId")
		        ->willReturn(1);

		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->expects($this->any())->method("bindValue")->withAnyParameters();
		$stmt->expects($this->once())->method("execute")->willReturn(true);

		$pdo = $this->createMock(\PDO::class);
		$pdo->expects($this->once())->method("prepare")->willReturn($stmt);

		$addressManager = new AddressManager($pdo);
		$addressManager->update($address);
	}
}