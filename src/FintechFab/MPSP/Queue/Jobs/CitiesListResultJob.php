<?php namespace FintechFab\MPSP\Queue\Jobs;

use FintechFab\MPSP\Entities\City;

class CitiesListResultJob extends AbstractJob
{

	public function __construct(City $city)
	{
		$this->city = $city;
	}

	protected function run($data)
	{
		$cities = $data['cities'];

		$this->city->truncate();

		foreach ($cities as $city) {

			$this->city->create($city);

		}

	}
}