<?php namespace FintechFab\MPSP\Entities;

use ArrayAccess;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;

/**
 * @property string $phone
 *
 */
class Sender implements ArrayAccess, ArrayableInterface, JsonableInterface
{

	private $attributes = [];

	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->attributes);
	}

	public function offsetUnset($offset)
	{
		unset($this->attributes[$offset]);
	}

	public function __get($name)
	{
		return $this->offsetGet($name);
	}

	public function __set($name, $value)
	{
		$this->offsetSet($name, $value);
	}

	public function offsetGet($offset)
	{
		return $this->attributes[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->attributes[$offset] = $value;
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int $options
	 *
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->toArray(), $options);
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->attributes;
	}

	public function loadFromTransfer(Transfer $transfer)
	{
		$this->phone = $transfer->sender->phone;

		return $this;
	}

}