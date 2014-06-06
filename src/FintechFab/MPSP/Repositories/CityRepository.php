<?php namespace FintechFab\MPSP\Repositories;

use FintechFab\MPSP\Entities\City;

class CityRepository
{

	public function __construct(City $city)
	{
		$this->city = $city;
	}

	/**
	 * @param int $id
	 *
	 * @return \FintechFab\MPSP\Entities\City|null
	 */
	public function findById($id)
	{
		return $this->city->newInstance()->find($id);
	}

	/**
	 * @param string $name
	 *
	 * @return \FintechFab\MPSP\Entities\City|null
	 */
	public function findByName($name, $limit = 5)
	{
		return $this->city->newInstance()
			->where('name', 'like', "$name%")
			->limit($limit);
	}

} 