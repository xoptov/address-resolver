<?php

namespace Xoptov\AddressResolver\Tests;

use PDO;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Xoptov\AddressResolver\DaData;
use Xoptov\AddressResolver\Model\Address;
use Xoptov\AddressResolver\RegionManager;
use Xoptov\AddressResolver\AddressManager;
use Xoptov\AddressResolver\YandexGeoCoder;
use Xoptov\AddressResolver\Model\Location;
use Xoptov\AddressResolver\AddressResolver;
use Xoptov\AddressResolver\LocalityManager;
use Xoptov\AddressResolver\LocationManager;
use Xoptov\AddressResolver\Model\Coordinate;
use Xoptov\AddressResolver\CoordinateManager;

class AddressResolverTest extends TestCase
{
	public function testResolve()
	{
	    $locations = array(
	        array(
	            "coordinate" => new Coordinate(45.0448400, 38.9760300),
                "name" => "г.Краснодар, ул.Красная 10"
            ),
	        array(
	            "coordinate" => new Coordinate(43.1056200, 131.8735300),
                "name" => "г.Владивосток, ул.100-лет Владивостоку 1"
            ),
	        array(
	            "coordinate" => new Coordinate(59.9386300, 30.3141300),
                "name" => "г.Санкт-Петербург, ул.Русская 1"
            ),
            array(
                "coordinate" => new Coordinate(55.7522200, 37.6155600),
                "name" => "г.Москва, ул.Русская 1"
            )
        );

		$pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
		$coordinateManager = new CoordinateManager();
		$addressManager = new AddressManager($pdo, $coordinateManager, 2);
		$localityManager = new LocalityManager($pdo, $coordinateManager, 10);
		$regionManager = new RegionManager($pdo);
		$client = new Client();
		$locationManager = new LocationManager();

		$geoCoder = new YandexGeoCoder($client, $locationManager, GEOCODER_API);
		$daData = new DaData($client, DADATA_API, DADATA_KEY, DADATA_SECRET);
		$resolver = new AddressResolver($pdo, $addressManager, $localityManager, $regionManager, $geoCoder, $daData);

		foreach ($locations as $location) {
		    $description = new Location($location["name"], $location["coordinate"]);
		    $address = $resolver->resolve($description);
            $this->assertInstanceOf(Address::class, $address);
        }
	}
}