<?php

use FintechFab\MPSP\Repositories\TransferRepository;

class AcquiringController extends BaseController
{

	public function __construct(TransferRepository $transfers)
	{
		$this->transfers = $transfers;
	}

	/**
	 * завершить 3DS
	 */
	public function finish_3ds()
	{
		$phone = Input::get('phone');
		$code = Input::get('code');

		$dataFor3DS = Input::except('phone', 'code');

		if (is_null($phone) || is_null($code)) {
			$this->setResponseCode(self::C_CODE_VALIDATION_ERROR);
			$this->setResponseMessage('phone или code некорректны');

			return $this->createErrorResponseData();
		}

		// ищем трансфер
		$transfer = $this->transfers->findByPhoneAndCode($phone, $code);

		// трансфер не найден
		if (is_null($transfer)) {
			$this->setResponseCode(self::C_CODE_TRANSFER_DOES_NOT_EXIST);

			return $this->createErrorResponseData();
		}

		$queueData = [
			'transfer' => $transfer->toArray(),
			'3ds_data' => $dataFor3DS,
		];

		// кидаем в очередь задачу с полученными данными
		Queue::connection('gateway')
			->push('acquiringFinish3DS', $queueData);

		$this->setResponseCode(self::C_CODE_SUCCESS);

		return $this->createSuccessResponseData([
			'transfer' => $transfer->toArray(),
		]);
	}

} 