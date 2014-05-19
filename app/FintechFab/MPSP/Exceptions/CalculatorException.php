<?php namespace FintechFab\MPSP\Exceptions;

use Exception;

class CalculatorException extends Exception
{
	const NECESSARY_TO_CALCULATE = 1;

	static public function necessaryToCalculate($currency, $amount)
	{
		throw new self(
			'В таблице transfer_costs не задана комиссия для валюты ' . $currency . ' для суммы ' . $amount . '',
			self::NECESSARY_TO_CALCULATE
		);
	}

} 