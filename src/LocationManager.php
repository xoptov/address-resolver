<?php

namespace Xoptov\AddressResolver;

use Xoptov\AddressResolver\Model\Location;
use Xoptov\AddressResolver\Model\Coordinate;

class LocationManager
{
	public function create($name, $latitude, $longitude)
	{
		$location = new Location();
		$location
			->setName($name)
		    ->setCoordinate(new Coordinate($latitude, $longitude));

		return $location;
	}
}