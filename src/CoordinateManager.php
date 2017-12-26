<?php

namespace Xoptov\AddressResolver;

use WKB;
use Point;
use Exception;
use Xoptov\AddressResolver\Model\Coordinate;

class CoordinateManager
{
	/** @var WKB */
	private $wkbAdapter;

	/**
	 * GeoManager constructor.
	 */
	public function __construct()
	{
		$this->wkbAdapter = new WKB();
	}

	/**
	 * @param string $wkb
	 * @return Coordinate
	 * @throws Exception
	 */
	public function createFromWKB($wkb)
	{
		$point = $this->wkbAdapter->read($wkb);
		$coordinate = new Coordinate($point->getY(), $point->getX());

		return $coordinate;
	}

	/**
	 * @param Coordinate $coordinate
	 * @return string
	 */
	public function readAsWKB(Coordinate $coordinate)
	{
		$point = new Point($coordinate->getLongitude(), $coordinate->getLatitude());
		$wkb = $this->wkbAdapter->write($point);

		return $wkb;
	}
}