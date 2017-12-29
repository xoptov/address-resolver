<?php

namespace Xoptov\AddressResolver;

use Xoptov\AddressResolver\Model\Location;
use Xoptov\AddressResolver\Model\Coordinate;

class LocationManager
{
	/**
	 * @param string $name
	 * @param float $latitude
	 * @param float $longitude
	 *
	 * @return Location
	 */
	public function create($name, $latitude, $longitude)
	{
		$location = new Location($name, new Coordinate($latitude, $longitude));

		return $location;
	}
}