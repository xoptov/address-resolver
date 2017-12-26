<?php

namespace Xoptov\AddressResolver\Model;

class Coordinate
{
	/** @var float */
	private $latitude;

	/** @var float */
	private $longitude;

	/**
	 * Coordinate constructor.
	 * @param float $latitude
	 * @param float $longitude
	 */
	public function __construct($latitude, $longitude)
	{
		$this->latitude = $latitude;
		$this->longitude = $longitude;
	}

	/**
	 * @param float $latitude
	 * @return Coordinate
	 */
	public function setLatitude($latitude)
	{
		$this->latitude = $latitude;

		return $this;
	}

	/**
	 * @return float
	 */
	public function getLatitude()
	{
		return $this->latitude;
	}

	/**
	 * @param float $longitude
	 * @return Coordinate
	 */
	public function setLongitude($longitude)
	{
		$this->longitude = $longitude;

		return $this;
	}

	/**
	 * @return float
	 */
	public function getLongitude()
	{
		return $this->longitude;
	}
}