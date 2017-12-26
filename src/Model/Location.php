<?php

namespace Xoptov\AddressResolver\Model;

class Location
{
	use CoordinateTrait;

	/** @var string */
	private $name;

	/**
	 * @param string $name
	 * @return Location
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
}