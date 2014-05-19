<?php namespace FintechFab\MPSP\Queue\Jobs;

use Illuminate\Queue\Jobs\Job;
use Log;
use FintechFab\MPSP\Repositories\TransferRepository;
use FintechFab\MPSP\Services\TransferStatusSwitcher;

class AcquiringRefundResultJob
{

	public function __construct(TransferRepository $transfers, TransferStatusSwitcher $transferStatusSwitcher)
	{
		$this->transfers = $transfers;
		$this->transferStatusSwitcher = $transferStatusSwitcher;
	}

	public function fire(Job $job, $data)
	{
		Log::debug(self::class, array($data));

		$transferId = $data['transfer_id'];

		$transfer = $this->transfers->findById($transferId);

		// трансфера с кодом не существует
		if (is_null($transfer)) {

			// логируем
			Log::error('transfer with id "' . $transferId . '" does not exist', $data);
			$job->delete();

			return;
		}

		$failed = isset($data['error']);

		if ($failed) {

			Log::warning('Ошибка возврата средств', $data);
			$this->transferStatusSwitcher->doRefundError($transfer);

		} else {

			Log::info('Произведён возврат денежных средств', $data);
			$this->transferStatusSwitcher->doRefundSuccess($transfer);

		}
		$job->delete();
	}

} 