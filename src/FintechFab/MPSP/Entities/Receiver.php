<?php namespace FintechFab\MPSP\Entities;

use ArrayAccess;
use FintechFab\MPSP\Repositories\CityRepository;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;

/**
 * @property string $surname
 * @property string $name
 * @property string $thirdname
 * @property string $city
 * @property string $phone
 *
 */
class Receiver implements ArrayAccess, ArrayableInterface, JsonableInterface
{

	private $attributes = [];

	/**
	 * @var CityRepository
	 */
	private $cities;

	public function __construct(CityRepository $cities)
	{
		$this->cities = $cities;
	}

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
		$this->name = $transfer->receiver_name;
		$this->surname = $transfer->receiver_surname;
		$this->thirdname = $transfer->receiver_thirdname;
		$this->city = $transfer->receiver_city;

		if ($transfer->receiver) {
			$this->phone = $transfer->receiver->phone;
		}

		return $this;
	}

}