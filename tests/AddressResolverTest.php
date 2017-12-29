<?php

namespace Xoptov\AddressResolver\Tests;

use PDO;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Xoptov\AddressResolver\AddressManager;
use Xoptov\AddressResolver\AddressResolver;
use Xoptov\AddressResolver\CoordinateManager;
use Xoptov\AddressResolver\DaData;
use Xoptov\AddressResolver\LocalityManager;
use Xoptov\AddressResolver\LocationManager;
use Xoptov\AddressResolver\RegionManager;
use Xoptov\AddressResolver\YandexGeoCoder;
use Xoptov\AddressResolver\Model\Coordinate;
use Xoptov\AddressResolver\Model\Location;

class AddressResolverTest extends TestCase
{
	public function testResolve()
	{
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

		$coordinate = new Coordinate(44.583774, 38.038101);
		$location = new Location(null, $coordinate);

		$address = $resolver->resolve($location);

		return;
	}
}