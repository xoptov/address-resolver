<?php

namespace Xoptov\AddressResolver;

use Xoptov\AddressResolver\Model\Coordinate;

interface GeoCoderInterface
{
	/**
	 * @param Coordinate $coordinate
	 * @return string
	 */
	public function getNameByCoordinate(Coordinate $coordinate);

	/**
	 * @param string $name
	 * @return Coordinate
	 */
	public function getCoordinateByName($name);
}