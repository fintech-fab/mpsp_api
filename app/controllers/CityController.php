<?php

use FintechFab\MPSP\Entities\City;
use FintechFab\MPSP\Entities\CityName;

class CityController extends BaseController
{

	const C_CITY_LIMIT = 10;

	/**
	 * @var \FintechFab\MPSP\Entities\City
	 */
	private $city;
	/**
	 * @var \FintechFab\MPSP\Entities\CityName
	 */
	private $cityName;

	public function __construct(City $city, CityName $cityName)
	{
		$this->city = $city;
		$this->cityName = $cityName;
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

		// Поиск городов названия которых начинаются с поисковых слов
		$cityIds = $this->cityName
			->where('value', 'like', "$search%")
			->limit(self::C_CITY_LIMIT)
			->get(['city_id']);

		// Если ничего не найдено пробуем искать по всему названию
		if (count($cityIds) === 0) {
			$cityIds = $this->cityName
				->where('value', 'like', "%$search%")
				->limit(self::C_CITY_LIMIT)
				->get(['city_id']);
		}

		$this->setResponseCode(self::C_CODE_SUCCESS);

		$cityIds = $cityIds->lists('city_id');

		$cities = [];
		if (count($cityIds) > 0) {
			$cities = $this->city->whereIn('id', $cityIds)
				->with('names')
				->get()
				->toArray();
		}

		return $this->createSuccessResponseData($cities);
	}

} 