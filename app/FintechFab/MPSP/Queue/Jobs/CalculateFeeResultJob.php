<?php namespace FintechFab\MPSP\Queue\Jobs;

use DB;
use Illuminate\Queue\Jobs\Job;
use FintechFab\MPSP\Entities\Currency;

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

		// выбираем стоимость перевода из базы
		DB::table('transfer_costs')
			->where('id', $data['cost_id'])
			->update(array(
				'flag_query' => 0,
				'currency'   => $currency,
				'sum_from'   => $data['amount'],
				'sum_to'     => $data['amount'],
				'amount'     => $data['commission'],
				'dt_update'  => date('Y-m-d H:i:s'),
			));

		$job->delete();
	}

}