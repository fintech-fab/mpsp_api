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

		if(!$data){
			Log::warning('Pull empty cities data list');
			Queue::connection('gateway')->push('citiesList', []);
			return;
		}

		$cities = $data['cities'];

		$this->city->truncate();

		foreach ($cities as $city) {

			$this->city->create($city);

		}

	}
}