<?php namespace FintechFab\MPSP\Controllers;

use Exception;
use FintechFab\MPSP\Calculator\Calculator as TransferCostCalculator;
use FintechFab\MPSP\Entities\Card;
use FintechFab\MPSP\Entities\Receiver;
use FintechFab\MPSP\Entities\Sender;
use FintechFab\MPSP\Exceptions\CalculatorException;
use FintechFab\MPSP\Exceptions\ValidatorException;
use FintechFab\MPSP\Repositories\CityRepository;
use FintechFab\MPSP\Repositories\TransferRepository;
use FintechFab\MPSP\Services\TransferFactory;
use FintechFab\MPSP\Services\TransferStatusSwitcher;
use FintechFab\MPSP\Sms\Sms;
use Input;
use Log;
use Str;

class TransferController extends BaseController
{

	/**
	 * @var \FintechFab\MPSP\Services\TransferStatusSwitcher
	 */
	private $transferStatusSwitcher;
	/**
	 * @var \FintechFab\MPSP\Sms\Sms
	 */
	private $sms;

	public function __construct(
		TransferCostCalculator $transferCostCalculator,
		TransferFactory $transferFactory,
		Card $transferCard,
		Receiver $receiver,
		Sender $sender,
		TransferRepository $transfers,
		TransferStatusSwitcher $transferStatusSwitcher,
		CityRepository $cities,
		Sms $sms
	)
	{
		$this->transferCostCalculator = $transferCostCalculator;
		$this->transferFactory = $transferFactory;
		$this->transferCard = $transferCard;
		$this->receiver = $receiver;
		$this->sender = $sender;
		$this->transfers = $transfers;
		$this->transferStatusSwitcher = $transferStatusSwitcher;
		$this->cities = $cities;
		$this->sms = $sms;
	}

	/**
	 * Посчитать комиссию за перевод
	 *
	 * @return array
	 */
	public function cost()
	{
		$amount = Input::get('amount');
		$currency = Input::get('currency');

		$this->transferCostCalculator->setAmount($amount);
		$this->transferCostCalculator->setCurrency($currency);

		$cost = null;

		try {
			$cost = $this->transferCostCalculator->doCalculate();
		} catch (ValidatorException $exception) {

			// ошибка валидации
			$this->setResponseCode(self::C_CODE_VALIDATION_ERROR);

			return $this->createErrorResponseData([
				'errors' => $exception->getErrors(),
			]);
		} catch (CalculatorException $exception) {

			Log::info($exception->getMessage());
			$this->setResponseCode(self::C_CODE_RETRY_THE_REQUEST_LATER);

			return $this->createErrorResponseData();
		} catch (Exception $exception) {

			// системная ошибка
			// кидаем в лог и выходим
			Log::critical($exception);

			$this->setResponseCode(self::C_CODE_SYSTEM_ERROR);

			return $this->createErrorResponseData();
		}

		$totalAmount = $amount + $cost;

		$this->setResponseCode(self::C_CODE_SUCCESS);

		return $this->createSuccessResponseData([
			'amount' => (float)$amount,
			'cost'   => $cost,
			'total'  => $totalAmount,
		]);
	}

	/**
	 * Создать перевод
	 */
	public function create()
	{
		$senderPhone = Input::get('sender_phone');

		$amount = Input::get('amount');
		$fee = Input::get('fee');
		$currency = Input::get('currency');

		$cardNumber = Input::get('card_number');
		$cardExpireMonth = Input::get('card_expire_month');
		$cardExpireYear = Input::get('card_expire_year');
		$cardCVV = Input::get('card_cvv');

		$receiverSurname = Input::get('receiver_surname');
		$receiverName = Input::get('receiver_name');
		$receiverThirdname = Input::get('receiver_thirdname');
		$receiverCityId = Input::get('receiver_city_id');
		$receiverPhone = Input::get('receiver_phone');

		// ищем город по id
		$city = $this->cities->findById($receiverCityId);

		// задаем данные для объекта банк. карты
		$this->transferCard->number = $cardNumber;
		$this->transferCard->expire_month = $cardExpireMonth;
		$this->transferCard->expire_year = $cardExpireYear;
		$this->transferCard->cvv = $cardCVV;

		// задаем данные для объекта отправителя
		$this->sender->phone = $senderPhone;

		// задаем данные для объекта получателя
		$this->receiver->surname = $receiverSurname;
		$this->receiver->name = $receiverName;
		$this->receiver->thirdname = $receiverThirdname;
		$this->receiver->city = !is_null($city) ? $city->id : null;
		$this->receiver->phone = $receiverPhone;

		// задаем данные для трансфера
		$this->transferFactory->setCard($this->transferCard);
		$this->transferFactory->setReceiver($this->receiver);
		$this->transferFactory->setSender($this->sender);
		$this->transferFactory->setAmount($amount);
		$this->transferFactory->setFee($fee);
		$this->transferFactory->setCurrency($currency);

		$transfer = null;

		try {
			// создаем трансфер
			$transfer = $this->transferFactory->create();

			$this->sms->code($transfer);
		} catch (ValidatorException $exception) {

			// ошибка валидации
			$this->setResponseCode(self::C_CODE_VALIDATION_ERROR);

			return $this->createErrorResponseData([
				'errors' => $exception->getErrors(),
			]);
		} catch (Exception $exception) {

			// системная ошибка
			// кидаем в лог и выходим
			Log::critical($exception);

			$this->setResponseCode(self::C_CODE_SYSTEM_ERROR);

			return $this->createErrorResponseData();
		}

		$this->setResponseCode(self::C_CODE_SUCCESS);

		return $this->createSuccessResponseData([
			'transfer' => ['phone' => $transfer->sender->phone],
		]);
	}

	/**
	 * Отправить перевод
	 *
	 * @return array
	 */
	public function send()
	{
		$phone = Input::get('phone');
		$code = Str::upper(Input::get('code'));

		// TODO валидация входных параметров

		$transfer = $this->transfers->findByPhoneAndCode($phone, $code);
		if (!$transfer) {
			$this->setResponseCode(self::C_CODE_TRANSFER_DOES_NOT_EXIST);

			return $this->createErrorResponseData();
		}

		$this->transferStatusSwitcher->doToCheck($transfer);

		$this->setResponseCode(self::C_CODE_SUCCESS);

		return $this->createSuccessResponseData([
			'transfer' => $transfer->toArray(),
		]);
	}

	/**
	 * получить статус
	 */
	public function status()
	{
		$phone = Input::get('phone');
		$code = Str::upper(Input::get('code'));

		$transfer = $this->transfers->findByPhoneAndCode($phone, $code);

		if (!$transfer) {
			$this->setResponseCode(self::C_CODE_TRANSFER_DOES_NOT_EXIST);

			return $this->createErrorResponseData();
		}

		$this->setResponseCode(self::C_CODE_SUCCESS);

		$result = [
			'transfer' => $transfer->toArray(),
		];

		if ($transfer->status == TransferStatusSwitcher::C_STATUS_TRANSFER_SUCCESS) {
			$result['checknumber'] = $transfer->checknumber;
		}

		return $this->createSuccessResponseData($result);
	}

	/**
	 * Отмена денежного перевода
	 */
	public function cancel()
	{
		$phone = Input::get('phone');
		$code = Input::get('code');

		$transfer = $this->transfers->findByPhoneAndCode($phone, $code);
		$cancelled = $this->transferStatusSwitcher->doToCancel($transfer);

		if (!$cancelled) {
			Log::warning('Невозможно выполнить отмену перевода', $transfer->toArray());

			$this->setResponseCode(self::C_CODE_RETRY_THE_REQUEST_LATER);

			return $this->createErrorResponseData();
		}

		$this->setResponseCode(self::C_CODE_SUCCESS);

		return $this->createSuccessResponseData([
			'transfer' => $transfer->toArray(),
		]);
	}

} 