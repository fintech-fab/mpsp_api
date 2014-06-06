<?php namespace FintechFab\MPSP\Controllers;

use FintechFab\MPSP\Entities\City;
use FintechFab\MPSP\Repositories\CityRepository;
use Input;

class CityController extends BaseController
{

	const C_CITY_LIMIT = 10;

	public function __construct(City $city, CityRepository $cities)
	{
		$this->city = $city;
		$this->cities = $cities;
	}

	public function search()
	{
		$search = Input::get('q');

		// убираем спецсимволы для выражения like
		$search = str_replace("%", "", $search);

		// Начинаем искать только с трёх символов
		if (mb_strlen($search) < 3) {
			$this->setResponseCode(self::C_CODE_VALIDATION_ERROR);

			return $this->createErrorResponseData([], 'Укажите хотя бы три символа');
		}

		$cities = $this->cities->findByName($search, self::C_CITY_LIMIT);

		$this->setResponseCode(self::C_CODE_SUCCESS);

		return $this->createSuccessResponseData($cities->toArray());
	}

} 