<?php namespace FintechFab\MPSP\Repositories;

use FintechFab\MPSP\Entities\City;

class CityRepository
{

	public function __construct(City $city)
	{
		$this->city = $city;
	}

	/**
	 * @param $id
	 *
	 * @return \FintechFab\MPSP\Entities\City|null
	 */
	public function findById($id)
	{
		return $this->city->newInstance()->find($id);
	}

} 