<?php namespace FintechFab\MPSP\Services;

use FintechFab\MPSP\Entities\Receiver;
use FintechFab\MPSP\Entities\Sender;
use FintechFab\MPSP\Entities\Transfer;
use Queue;

class TransferStatusSwitcher
{

	const C_STATUS_NEW = 1; // новый перевод
	const C_STATUS_CHECK_WAIT = 2; // ожидание проверки возможности перевода
	const C_STATUS_CHECK_ERROR = 3; // перевод нельзя осуществить
	const C_STATUS_CHECK_SUCCESS = 4; // перевод возможно осуществить
	const C_STATUS_ACQUIRING_WAIT = 5; // ожидание списания средств с карты
	const C_STATUS_ACQUIRING_3DS = 6; // требуется пройти 3DS авторизацию
	const C_STATUS_ACQUIRING_ERROR = 7; // ошибка списания средств
	const C_STATUS_ACQUIRING_SUCCESS = 8; // средства были списаны с карты
	const C_STATUS_TRANSFER_TO_SEND = 9; // ожидание выполнения перевода
	const C_STATUS_TRANSFER_ERROR = 10; // не удалось выполнить перевод
	const C_STATUS_TRANSFER_SUCCESS = 11; // перевод был успешно отправлен
	const C_STATUS_REFUND_WAIT = 12; // ожидание возврата средств
	const C_STATUS_REFUND_ERROR = 13; // не удалось вернуть средства
	const C_STATUS_REFUND_SUCCESS = 14; // средства были возвращены
	const C_STATUS_CANCEL_WAIT = 15; // Ожидание отмены перевода
	const C_STATUS_CANCEL_SUCCESS = 16; // Перевод отменён
	const C_STATUS_CANCEL_ERROR = 17; // Невозможно выполнить отмену перевода
	const C_STATUS_TRANSFER_PAID = 18; // Деньги выданы адресату

	/*
	 * // Система отказала в денежном переводе
	 * 1 -> 2 -> 3 (end)
	 *
	 * // Не удалось списать денежные средства
	 * 1 -> 2 -> 4 -> 5 -> 6 -> 7 (end) // через 3ds
	 * 1 -> 2 -> 4 -> 5 -> 7 (end)
	 *
	 * // Система отказала в переводе после эквайринга
	 * 1 -> 2 -> 4 -> 5 -> 8 -> 9 -> 10 -> 12 -> 13 (end) // Не удалось вернуть деньги
	 * 1 -> 2 -> 4 -> 5 -> 8 -> 9 -> 10 -> 12 -> 14 (end) // Деньги возвращены
	 *
	 * // Деньги успешно ушли
	 * 1 -> 2 -> 4 -> 5 -> 8 -> 9 -> 11 -> 18 (end)
	 *
	 * // Отмена денежного перевода (Система отказалась выполнять отмену)
	 * 11 -> 15 -> 17 (end)
	 *
	 * // Отмена денежнего перевода (Банк отказался возвращать средства)
	 * 11 -> 16 -> 12 -> 13 (end)
	 *
	 * // Отмена денежного перевода
	 * 11 -> 16 -> 12 -> 14 (end)
	 */

	/**
	 * Очищать данные банковских карт
	 *
	 * Значение указывается в секундах
	 */
	const C_CLEAN_CARD_DELAY = 3600;
	/**
	 * @var \FintechFab\MPSP\Entities\Sender
	 */
	private $sender;

	/**
	 * @param Sender   $sender
	 * @param Receiver $receiver
	 */
	public function __construct(Sender $sender, Receiver $receiver)
	{
		$this->sender = $sender;
		$this->transferReceiver = $receiver;
	}

	/**
	 * Новый трансфер
	 */
	public function doNew(Transfer $transfer)
	{
		$transfer->setStatus(self::C_STATUS_NEW);

		// Очистим данные о карте на случай если не прийдёт подтверждение перевода
		Queue::connection('api')
			->later(self::C_CLEAN_CARD_DELAY, 'cardClean', [
				'card_id' => $transfer->card->id
			]);
	}

	public function doToCheck(Transfer $transfer)
	{
		$transfer->setStatus(self::C_STATUS_CHECK_WAIT);

		// кидаем в очередь трансфер на проверку
		Queue::connection('gateway')
			->push('transferCheck', array(
				'transfer' => $transfer->toArray(),
				'sender' => $this->sender->loadFromTransfer($transfer)->toArray(),
				'receiver' => $this->transferReceiver->loadFromTransfer($transfer)->toArray(),
			));
	}

	/**
	 * Трансфер на отправку
	 */
	public function doToSend(Transfer $transfer)
	{
		$transfer->setStatus(self::C_STATUS_ACQUIRING_SUCCESS);

		$transfer->setStatus(self::C_STATUS_TRANSFER_TO_SEND);

		$requestData = [
			'checknumber' => $transfer->checknumber,

			'transfer'    => $transfer->toArray(),
			'receiver'    => $this->transferReceiver->loadFromTransfer($transfer)->toArray(),
			'sender' => $this->sender->loadFromTransfer($transfer)->toArray(),
		];

		// ставим задачу на снятие средств
		Queue::connection('gateway')
			->push('transferSend', $requestData);
	}

	/**
	 * Эквайринг пройден, система выполнила перевод
	 */
	public function doSendSuccess(Transfer $transfer, $checkNumber)
	{
		$transfer->setStatus(self::C_STATUS_TRANSFER_SUCCESS);
		$transfer->checknumber = $checkNumber;
		$transfer->save();

		$requestData = [
			'transfer' => $transfer->toArray(),
			'receiver' => $transfer->receiver->toArray(),
			'sender'   => $transfer->sender->toArray(),
		];

		Queue::connection('gateway')
			->push('transferStatus', $requestData);
	}

	/**
	 * Ошибка перевода денег
	 *
	 * @param \FintechFab\MPSP\Entities\Transfer $transfer
	 */
	public function doSendError(Transfer $transfer)
	{
		$transfer->setStatus(self::C_STATUS_TRANSFER_ERROR);
		$this->doToRefund($transfer);
	}

	/**
	 * Выполнить возврат по переводу
	 *
	 * @param \FintechFab\MPSP\Entities\Transfer $transfer
	 */
	public function doToRefund(Transfer $transfer)
	{
		$transfer->setStatus(self::C_STATUS_REFUND_WAIT);

		// ставим задачу на возврат средств
		Queue::connection('gateway')
			->push('acquiringRefund', [
				'transfer' => ['id' => $transfer->id]
			]);
	}

	/**
	 * Ошибка списания средств
	 */
	public function doAcquiringError(Transfer $transfer)
	{
		$transfer->setStatus(self::C_STATUS_ACQUIRING_ERROR);
	}

	/**
	 * Требуется 3DS авторизация
	 *
	 * @param \FintechFab\MPSP\Entities\Transfer $transfer
	 * @param string                             $url
	 * @param array                              $data
	 */
	public function do3DS(Transfer $transfer, $url, array $data)
	{
		$transfer->setStatus(self::C_STATUS_ACQUIRING_3DS);

		// сохраняем данные по 3DS
		$transfer->setAttribute('3ds_url', $url);
		$transfer->setAttribute('3ds_post_data', json_encode($data, JSON_UNESCAPED_UNICODE));
		$transfer->save();
	}

	/**
	 * Перевод возможно осуществить
	 *
	 * @param \FintechFab\MPSP\Entities\Transfer $transfer
	 */
	public function doCheckSuccess(Transfer $transfer)
	{
		// меняем статус
		$transfer->setStatus(self::C_STATUS_CHECK_SUCCESS);

		$requestData = [
			'transfer' => $transfer->toArray(),
			'card'     => $transfer->card->toArray(),
		];

		// ставим задачу на снятие средств
		Queue::connection('gateway')
			->push('acquiring', $requestData);

		$transfer->setStatus(self::C_STATUS_ACQUIRING_WAIT);

		$transfer->card->clean();
	}

	/**
	 * Ошибка возможности осуществления перевода
	 */
	public function doCheckFailure(Transfer $transfer)
	{
		$transfer->setStatus(self::C_STATUS_CHECK_ERROR);
	}

	/**
	 * Возврат перевода выполнен
	 *
	 * @param \FintechFab\MPSP\Entities\Transfer $transfer
	 */
	public function doRefundSuccess(Transfer $transfer)
	{
		$transfer->setStatus(self::C_STATUS_REFUND_SUCCESS);
	}

	/**
	 * Невозможно выполнить возврат перевода
	 *
	 * @param \FintechFab\MPSP\Entities\Transfer $oTransfer
	 */
	public function doRefundError(Transfer $oTransfer)
	{
		$oTransfer->setStatus(self::C_STATUS_REFUND_ERROR);
	}

	/**
	 * Отменить операцию
	 *
	 * @param \FintechFab\MPSP\Entities\Transfer $transfer
	 *
	 * @return bool
	 */
	public function doToCancel(Transfer $transfer)
	{
		// Конечный статус 11 (TransferSuccess), все остальные статусы - "плавающие"
		// это означает, что работа по ним продолжается. Управлять этими процессами
		// мы не можем, поэтому остаётся только ждать когда получим статус TransferSuccess

		if ($transfer->status == self::C_STATUS_TRANSFER_SUCCESS) {

			$transfer->setStatus(self::C_STATUS_CANCEL_WAIT);

			Queue::connection('gateway')
				->push('transferCancel', ['transfer' => ['id' => $transfer->id]]);

			return true;
		}

		return false;
	}

	/**
	 * @param \FintechFab\MPSP\Entities\Transfer $transfer
	 */
	public function doCancelSuccess(Transfer $transfer)
	{
		$transfer->setStatus(self::C_STATUS_CANCEL_SUCCESS);

		$this->doToRefund($transfer);
	}

	public function doCancelError(Transfer $transfer)
	{
		$transfer->setStatus(self::C_STATUS_CANCEL_ERROR);
	}

	/**
	 * @param \FintechFab\MPSP\Entities\Transfer $transfer
	 */
	public function doPaid(Transfer $transfer)
	{
		$transfer->setStatus(self::C_STATUS_TRANSFER_PAID);
	}
}