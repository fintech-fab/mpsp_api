<?php namespace FintechFab\MPSP\Queue\Jobs;

use Log;
use FintechFab\MPSP\Repositories\TransferRepository;
use FintechFab\MPSP\Services\TransferStatusSwitcher;

class TransferSendResultJob extends AbstractJob
{

	public function __construct(TransferRepository $transfers, TransferStatusSwitcher $transferStatusSwitcher)
	{
		$this->transfers = $transfers;
		$this->transferStatusSwitcher = $transferStatusSwitcher;
	}

	protected function run($data)
	{
		$transferId = $data['transfer_id'];

		$transfer = $this->transfers->findById($transferId);

		// трансфера с кодом не существует
		if (is_null($transfer)) {

			// логируем
			Log::error('transfer with id "' . $transferId . '" does not exist', $data);
		} else {

			$failed = isset($data['error']);

			if ($failed) {

				Log::info('Необходима отмена транзакции', $data);
				$this->transferStatusSwitcher->doSendError($transfer);

			} else {

				Log::info('Операция по переводу денежных средств выполнена. Ожидаем подтверждение статуса', $transfer->toArray());
				$this->transferStatusSwitcher->doSendSuccess($transfer);

			}

		}
	}
}