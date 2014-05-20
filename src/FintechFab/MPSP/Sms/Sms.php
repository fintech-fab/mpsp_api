<?php namespace FintechFab\MPSP\Sms;

use Lang;
use Log;
use FintechFab\MPSP\Entities\Transfer;
use Queue;

class Sms
{

	/**
	 * Отправить сообщение с кодом транзакции
	 *
	 * @param Transfer $transfer
	 */
	public function code(Transfer $transfer)
	{
		if (!$transfer->code) {
			Log::error('Попытка отправить пустой код-подтверждения по смс', $transfer->toArray());

			return;
		}

		Queue::connection('gateway')
			->push('sms', array(
				'phone'   => $transfer->sender->phone,
				'message' => Lang::get('sms.code', ['code' => $transfer->code]),
			));
	}

} 