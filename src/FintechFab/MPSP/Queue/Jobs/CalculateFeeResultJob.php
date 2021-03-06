<?php namespace FintechFab\MPSP\Queue\Jobs;

use DB;
use FintechFab\MPSP\Entities\Currency;
use Illuminate\Queue\Jobs\Job;
use Log;

class CalculateFeeResultJob
{
	/**
	 * @var Currency
	 */
	private $transferCurrency;

	public function __construct(Currency $transferCurrency)
	{
		$this->transferCurrency = $transferCurrency;
	}

	public function fire(Job $job, $data)
	{
		// получаем код валюты
		$currency = $this->transferCurrency->offsetGet($data['currency']);


		Log::debug('Fire CalculateFeeResultJob', $data);

		// выбираем стоимость перевода из базы
		DB::table('transfer_costs')
			->where('id', $data['cost_id'])
			->update(array(
				'flag_query' => 0,
				'currency'   => $currency,
				'sum_from'   => $data['amount'],
				'sum_to'     => $data['amount'],
				'amount'     => $data['commission'],
				'updated_at' => date('Y-m-d H:i:s'),
			));

		$job->delete();
	}

}