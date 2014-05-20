<?php namespace FintechFab\MPSP\Queue\Jobs;

use Illuminate\Queue\Jobs\Job;
use Log;
use FintechFab\MPSP\Repositories\TransferRepository;
use FintechFab\MPSP\Services\TransferStatusSwitcher;

class TransferCancelResultJob
{

	public function __construct(TransferRepository $transfers, TransferStatusSwitcher $transferStatusSwitcher)
	{
		$this->transfers = $transfers;
		$this->transferStatusSwitcher = $transferStatusSwitcher;
	}

	public function fire(Job $job, $data)
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

				Log::error('Невозможно отменить операцию по денежнему переводу', $transfer->toArray());
				$this->transferStatusSwitcher->doCancelError($transfer);

			} else {

				Log::info('Выполнена отмена денежного перевода', $transfer->toArray());
				$this->transferStatusSwitcher->doCancelSuccess($transfer);

			}

		}

		$job->delete();
	}

}