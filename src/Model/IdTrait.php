<?php

namespace Xoptov\AddressResolver\Model;

trait IdTrait
{
	/** @var int */
	protected $id;

	/**
	 * @param int $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}
}