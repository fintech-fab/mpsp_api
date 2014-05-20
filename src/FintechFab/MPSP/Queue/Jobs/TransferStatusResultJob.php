<?php namespace FintechFab\MPSP\Queue\Jobs;

use Exception;
use Log;
use FintechFab\MPSP\Constants\TransferStatus;
use FintechFab\MPSP\Repositories\TransferRepository;
use FintechFab\MPSP\Services\TransferStatusSwitcher;

class TransferStatusResultJob extends AbstractJob
{

	public function __construct(TransferRepository $transfers, TransferStatusSwitcher $transferStatusSwitcher)
	{
		$this->transferStatusSwitcher = $transferStatusSwitcher;
		$this->transfers = $transfers;
	}

	protected function run($data)
	{
		if (!isset($data['transfer_id']) || !isset($data['status'])) {

			Log::error('TransferStatusResultJob::run() Неправильно переданы параметры', $data);

			return;
		}

		$transferId = $data['transfer_id'];
		$resultStatus = $data['status'];

		$transfer = $this->transfers->findById($transferId);

		if (!$transfer) {

			Log::error("TransferStatusResultJob::run() Перевод не найден", $data);

			return;
		}

		try {

			if ($resultStatus === TransferStatus::C_PAID) {

				Log::info("Деньги выданы адресату", $transfer->toArray());
				$this->transferStatusSwitcher->doPaid($transfer);

			} elseif ($resultStatus === TransferStatus::C_DELETE) {

				Log::info("Транзакция удалена", $transfer->toArray());
				$this->transferStatusSwitcher->doToCancel($transfer);

			} else {

				Log::info("Игнорирование обработки статуса", ['transfer' => $transfer->toArray(), 'status' => $transferId]);

			}

		} catch (Exception $e) {

			Log::error($e, $data);

		}
	}
}