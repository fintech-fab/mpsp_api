<?php namespace FintechFab\MPSP\Entities;

use ArrayAccess;
use Exception;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;

class Currency implements ArrayAccess, ArrayableInterface, JsonableInterface
{

	private $values = [
		1 => 'RUR',
		2 => 'USD',
		3 => 'EUR',
		4 => 'BYR',
	];

	/**
	 * Валюта существует?
	 *
	 * @param string $offset
	 *
	 * @return boolean true on success or false on failure.
	 */
	public function offsetExists($offset)
	{
		return (bool)array_search($offset, $this->values);
	}

	/**
	 * Получить код валюты
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 *
	 * @param mixed $offset <p>
	 *                      The offset to retrieve.
	 *                      </p>
	 *
	 * @return mixed Can return all value types.
	 */
	public function offsetGet($offset)
	{
		return array_search($offset, $this->values);
	}


	/**
	 * @deprecated
	 *
	 * @throws Exception
	 */
	public function offsetSet($offset, $value)
	{
		throw new Exception('offsetSet for ' . self::class . ' is not supported');
	}

	/**
	 * @deprecated
	 *
	 * @throws Exception
	 */
	public function offsetUnset($offset)
	{
		throw new Exception('offsetUnset for ' . self::class . ' is not supported');
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
		return $this->values;
	}

}