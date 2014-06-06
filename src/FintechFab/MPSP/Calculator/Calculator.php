<?php namespace FintechFab\MPSP\Calculator;

use DB;
use FintechFab\MPSP\Entities\Currency;
use FintechFab\MPSP\Exceptions\CalculatorException;
use FintechFab\MPSP\Exceptions\ValidatorException;
use Queue;
use Validator;

class Calculator
{

	private $amount = null;
	private $currency = null;

	public function __construct(Currency $transferCurrency)
	{
		$this->transferCurrency = $transferCurrency;
	}

	/**
	 * Задать сумму
	 *
	 * @param $amount
	 */
	public function setAmount($amount)
	{
		$this->amount = $amount;
	}

	/**
	 * Задать валюту
	 *
	 * @param $currency
	 */
	public function setCurrency($currency)
	{
		$this->currency = $currency;
	}

	/**
	 * Высчитать комиссию
	 *
	 * @throws \FintechFab\MPSP\Exceptions\ValidatorException
	 */
	public function doCalculate()
	{
		$this->validate();

		// получаем код валюты
		$currency = $this->transferCurrency->offsetGet($this->currency);

		// выбираем стоимость перевода из базы
		$transferCosts = DB::table('transfer_costs')
			->where('currency', $currency)
			->where('sum_from', '<=', $this->amount)
			->where('sum_to', '>=', $this->amount)
			->orderBy('updated_at', 'desc')
			->first();

		if (is_null($transferCosts)) {

			$transferCostId = DB::table('transfer_costs')
				->insertGetId([
					'flag_query' => 0,
					'currency'   => $currency,
					'sum_from'   => $this->amount,
					'sum_to'     => $this->amount,
					'created_at'     => date('Y-m-d H:i:s'),
					'updated_at'  => 0,
				]);

			$transferCosts = Db::table('transfer_costs')->find($transferCostId);
		}

		// Если обновление не запущено и информация устарела
		if ($transferCosts->flag_query == 0 && $transferCosts->updated_at < date('Y-m-d H:i:s', time() - 86400)) {

			// Запись в обработке
			Db::table('transfer_costs')->where('id', '=', $transferCosts->id)->update(['flag_query' => 1]);

			// кидаем задание в очередь на подсчет комиссии
			Queue::connection('gateway')->push('calculateFee', [
				'cost_id'  => $transferCosts->id,
				'amount'   => $this->amount,
				'currency' => $this->currency,
			]);

		}

		// комиссия не задана
		if ($transferCosts->amount == 0) {

			CalculatorException::necessaryToCalculate($currency, $this->amount);

		}


		return (float)$transferCosts->amount;
	}

	/**
	 * Провалидировать данные
	 *
	 * @throws \FintechFab\MPSP\Exceptions\ValidatorException
	 */
	private function validate()
	{
		$validator = $this->getValidator();

		if (!$validator->passes()) {
			$exception = new ValidatorException;

			$errors = $validator
				->errors()
				->getMessages();

			$exception->setErrors($errors);

			throw $exception;
		}
	}

	/**
	 * @return \Illuminate\Validation\Validator
	 */
	private function getValidator()
	{
		// получаем список доступных валют
		$currencies = $this->transferCurrency->toArray();
		$currencies = implode(',', $currencies);

		// правила валидации
		$rules = [
			'amount'   => [
				'required',
				'numeric',
				'min:1',
				'max:15000',
			],
			'currency' => [
				'required',
				'in:' . $currencies,
			],
		];

		// данные для валидации
		$data = [
			'amount'   => $this->amount,
			'currency' => $this->currency,
		];

		return Validator::make($data, $rules);
	}

}