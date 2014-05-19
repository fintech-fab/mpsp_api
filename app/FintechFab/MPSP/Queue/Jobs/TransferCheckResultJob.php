<?php namespace FintechFab\MPSP\Queue\Jobs;

use Log;
use FintechFab\MPSP\Repositories\TransferRepository;
use FintechFab\MPSP\Services\TransferStatusSwitcher;

class TransferCheckResultJob extends AbstractJob
{

	public function __construct(TransferRepository $transfers, TransferStatusSwitcher $transferStatusSwitcher)
	{
		$this->transfers = $transfers;
		$this->transferStatusSwitcher = $transferStatusSwitcher;
	}

	public function run($data)
	{
		$transferId = $data['transfer_id'];

		$transfer = $this->transfers->findById($transferId);

		// трансфера с кодом не существует
		if (is_null($transfer)) {

			// логируем
			Log::error('transfer with id "' . $transferId . '" does not exist', $data);

			return;
		}

		$failed = isset($data['error']) || !isset($data['checknumber']);

		if ($failed) {

			Log::info('Перевод выполнить невозможно', $transfer->toArray());
			$this->transferStatusSwitcher->doCheckFailure($transfer);

		} else {

			Log::info('Отправляем задачу на снятие средств', $transfer->toArray());
			$this->transferStatusSwitcher->doCheckSuccess($transfer, $data['checknumber']);

		}

	}

} 