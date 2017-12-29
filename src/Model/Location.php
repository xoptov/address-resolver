<?php

namespace Xoptov\AddressResolver\Model;

class Location
{
	use CoordinateTrait;

	/** @var string */
	private $name;

	/**
	 * @param string $name
	 * @param Coordinate $coordinate
	 */
	public function __construct($name, Coordinate $coordinate)
	{
		$this->name = $name;
		$this->coordinate = $coordinate;
	}

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