<?php namespace FintechFab\MPSP\Queue\Jobs;

use FintechFab\MPSP\Entities\City;
use Log;
use Queue;

class CitiesListResultJob extends AbstractJob
{

	public function __construct(City $city)
	{
		$this->city = $city;
	}

	protected function run($data)
	{

		if (!$data || empty($data['cities'])) {
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