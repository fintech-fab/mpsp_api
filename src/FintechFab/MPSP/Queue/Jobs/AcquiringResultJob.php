<?php namespace FintechFab\MPSP\Queue\Jobs;

use Illuminate\Queue\Jobs\Job;
use Log;
use FintechFab\MPSP\Repositories\TransferRepository;
use FintechFab\MPSP\Services\TransferStatusSwitcher;

class AcquiringResultJob
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
		} else {

			$failed = isset($data['error']);

			$transfer->irn = $data['irn'];
			$transfer->rrn = $data['rrn'];
			$transfer->save();

			// требуется прохождение 3DS
			if ($data['need_3ds']) {

				Log::info('Отправлено на 3DS', $data);

				$this->transferStatusSwitcher->do3DS($transfer, $data['3ds_url'], $data['3ds_post_data']);

			} elseif ($failed) {

				Log::info('Ошибка списания средств', $data);

				// ошибка списания средств
				$this->transferStatusSwitcher->doAcquiringError($transfer);

			} else {

				Log::info('Трансфер готов к отправке', $data);

				// трансфер готов к отправке
				$this->transferStatusSwitcher->doToSend($transfer);

			}

		}

		$job->delete();
	}

} 