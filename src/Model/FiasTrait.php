<?php

namespace Xoptov\AddressResolver\Model;

trait FiasTrait
{
	/** @var string */
	protected $fiasId;

	/**
	 * @param string $fiasId
	 * @return $this
	 */
	public function setFiasId($fiasId)
	{
		$this->fiasId = $fiasId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getFiasId()
	{
		return $this->fiasId;
	}
}