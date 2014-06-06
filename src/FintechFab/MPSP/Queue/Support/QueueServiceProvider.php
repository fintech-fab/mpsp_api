<?php namespace FintechFab\MPSP\Queue\Support;

use FintechFab\MPSP\Queue\Jobs\AcquiringRefundResultJob;
use FintechFab\MPSP\Queue\Jobs\AcquiringResultJob;
use FintechFab\MPSP\Queue\Jobs\CalculateFeeResultJob;
use FintechFab\MPSP\Queue\Jobs\CardCleanJob;
use FintechFab\MPSP\Queue\Jobs\CitiesListResultJob;
use FintechFab\MPSP\Queue\Jobs\TransferCancelResultJob;
use FintechFab\MPSP\Queue\Jobs\TransferCheckResultJob;
use FintechFab\MPSP\Queue\Jobs\TransferSendResultJob;
use FintechFab\MPSP\Queue\Jobs\TransferStatusResultJob;
use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{

	const C_CALCULATE_FEE_RESULT = 'calculateFeeResult';
	const C_CITIES_LIST_RESULT = 'citiesListResult';
	const C_TRANSFER_CHECK_RESULT = 'transferCheckResult';
	const C_TRANSFER_SEND_RESULT = 'transferSendResult';
	const C_TRANSFER_STATUS_RESULT = 'transferStatusResult';
	const C_TRANSFER_CANCEL_RESULT = 'transferCancelResult';
	const C_ACQUIRING_RESULT = 'acquiringResult';
	const C_ACQUIRING_REFUND_RESULT = 'acquiringRefundResult';
	const C_CARD_CLEAN = 'cardClean';

	public function register()
	{

		// результат подсчета комиссии
		$this->app->bind(self::C_CITIES_LIST_RESULT, CitiesListResultJob::class);

		// результат подсчета комиссии
		$this->app->bind(self::C_CALCULATE_FEE_RESULT, CalculateFeeResultJob::class);

		// результат проверки возможности осуществления перевода
		$this->app->bind(self::C_TRANSFER_CHECK_RESULT, TransferCheckResultJob::class);

		// результат перевода средств через Мигом
		$this->app->bind(self::C_TRANSFER_SEND_RESULT, TransferSendResultJob::class);

		// результат перевода средств через Мигом
		$this->app->bind(self::C_TRANSFER_STATUS_RESULT, TransferStatusResultJob::class);

		// результат перевода средств через Мигом
		$this->app->bind(self::C_TRANSFER_CANCEL_RESULT, TransferCancelResultJob::class);

		// результат снятия средств
		$this->app->bind(self::C_ACQUIRING_RESULT, AcquiringResultJob::class);

		// результат возврата денег
		$this->app->bind(self::C_ACQUIRING_REFUND_RESULT, AcquiringRefundResultJob::class);

		// Очищение данных о карте
		$this->app->bind(self::C_CARD_CLEAN, CardCleanJob::class);
	}
}