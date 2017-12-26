<?php

namespace Xoptov\AddressResolver\Model;

class Address
{
	use IdTrait;

	use FiasTrait;

	use CoordinateTrait;

	/** @var Region|int */
	private $region;

	/** @var Locality|int */
	private $locality;

	/** @var string */
	private $value;

	/**
	 * @param Region $region
	 * @return Address
	 */
	public function setRegion(Region $region)
	{
		$this->region = $region;

		return $this;
	}

	/**
	 * @return Region|int
	 */
	public function getRegion()
	{
		return $this->region;
	}

	/**
	 * @param Locality $locality
	 * @return Address
	 */
	public function setLocality(Locality $locality)
	{
		$this->locality = $locality;

		return $this;
	}

	/**
	 * @return Locality|int
	 */
	public function getLocality()
	{
		return $this->locality;
	}

	/**
	 * @param string $value
	 * @return Address
	 */
	public function setValue($value)
	{
		$this->value = $value;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}
}