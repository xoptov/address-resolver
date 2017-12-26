<?php

namespace Xoptov\AddressResolver\Tests;

use PHPUnit\Framework\TestCase;
use Xoptov\AddressResolver\YandexGeoCoder;
use Xoptov\AddressResolver\Model\Coordinate;

class YandexGeoCoderTest extends TestCase
{
	public function testGetAddressName()
	{
		$geoCoder = new YandexGeoCoder(GEOCODER_API);
		$coordinate = new Coordinate(45.04484, 38.97603);
		$name = $geoCoder->getNameByCoordinate($coordinate);

		$this->assertEquals("Краснодар", $name);
	}
}