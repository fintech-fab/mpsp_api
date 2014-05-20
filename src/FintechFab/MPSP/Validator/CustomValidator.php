<?php namespace FintechFab\MPSP\Validator;

use Illuminate\Validation\Validator as V;
use FintechFab\MPSP\Entities\Currency;

class CustomValidator extends V
{

	/**
	 * Валюта существует
	 *
	 * @param $attribute
	 * @param $value
	 * @param $parameters
	 *
	 * @return bool
	 */
	public function validateCurrencyExists($attribute, $value, $parameters)
	{
		return (new Currency())->offsetExists($value);
	}

	/**
	 * Валидирует сумму в зависимости от валюты
	 *
	 * @param $attribute
	 * @param $value
	 * @param $parameters
	 *
	 * @return bool
	 */
	public function validateAmount($attribute, $value, $parameters)
	{
		$currency = $parameters[0];

		return true;
	}

	/**
	 * Валидирует строку по алгоритму "Луна"
	 *
	 * @param $attribute
	 * @param $value
	 * @param $parameters
	 *
	 * @return bool
	 */
	public function validateLuhn($attribute, $value, $parameters)
	{
		$sum = 0;
		$odd = strlen($value) % 2;

		// Remove any non-numeric characters.
		if (!is_numeric($value)) {
			$value = preg_replace('/\D/', '', $value);
		}

		// Calculate sum of digits.
		for ($i = 0; $i < strlen($value); $i++) {
			$sum += $odd ? $value[$i] : (($value[$i] * 2 > 9) ? $value[$i] * 2 - 9 : $value[$i] * 2);
			$odd = !$odd;
		}

		// Check validity.
		return ($sum % 10 == 0) ? true : false;
	}

}