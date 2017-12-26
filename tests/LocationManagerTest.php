<?php

namespace Xoptov\AddressResolver\Tests;

use PHPUnit\Framework\TestCase;
use Xoptov\AddressResolver\Model\Location;
use Xoptov\AddressResolver\LocationManager;
use Xoptov\AddressResolver\Model\Coordinate;

class LocationManagerTest extends TestCase
{
	public function testCreate()
	{
		$locationManager = new LocationManager();
		$location = $locationManager->create("Краснодар", 45.0263, 39.0382);

		$this->assertInstanceOf(Location::class, $location);
		$this->assertEquals("Краснодар", $location->getName());

		$this->assertInstanceOf(Coordinate::class, $location->getCoordinate());
		$this->assertEquals(45.0263, $location->getCoordinate()->getLatitude());
		$this->assertEquals(39.0382, $location->getCoordinate()->getLongitude());
	}
}