<?php

namespace Xoptov\AddressResolver\Model;

class Locality
{
	use IdTrait;

	use FiasTrait;

	use NameTrait;

	use CoordinateTrait;

	/** @var Region */
	private $region;

	/**
	 * @param Region $region
	 * @return Locality
	 */
	public function setRegion(Region $region)
	{
		$this->region = $region;

		return $this;
	}

	/**
	 * @return Region
	 */
	public function getRegion()
	{
		return $this->region;
	}
}