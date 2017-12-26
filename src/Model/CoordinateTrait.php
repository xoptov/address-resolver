<?php

namespace Xoptov\AddressResolver\Model;

trait CoordinateTrait
{
	/** @var Coordinate */
	protected $coordinate;

	/**
	 * @param Coordinate $coordinate
	 * @return $this
	 */
	public function setCoordinate(Coordinate $coordinate)
	{
		$this->coordinate = $coordinate;

		return $this;
	}

	/**
	 * @return Coordinate
	 */
	public function getCoordinate()
	{
		return $this->coordinate;
	}

	/**
	 * @return float
	 */
	public function getLatitude()
	{
		return $this->coordinate->getLatitude();
	}

	/**
	 * @return float
	 */
	public function getLongitude()
	{
		return $this->coordinate->getLongitude();
	}
}