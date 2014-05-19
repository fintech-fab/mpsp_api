<?php namespace FintechFab\MPSP\Services;

use Carbon\Carbon;
use Config;
use Exception;
use Log;
use Monemobo\Services\Facade;
use FintechFab\MPSP\Constants\MemberType;
use FintechFab\MPSP\Entities\Member;
use FintechFab\MPSP\Entities\Transfer;
use FintechFab\MPSP\Entities\Card;
use FintechFab\MPSP\Entities\Receiver;
use FintechFab\MPSP\Entities\Sender;
use FintechFab\MPSP\Exceptions\ValidatorException;
use FintechFab\MPSP\Repositories\TransferRepository;
use FintechFab\MPSP\Validator\CardValidator;
use FintechFab\MPSP\Validator\ReceiverValidator;
use FintechFab\MPSP\Validator\SenderValidator;
use FintechFab\MPSP\Validator\TransferValidator;

class TransferFactory
{

	private $salt;

	/**
	 * @var \FintechFab\MPSP\Entities\Card
	 */
	private $card;

	/**
	 * @var Receiver
	 */
	private $receiver;

	/**
	 * @var Sender
	 */
	private $sender;

	/**
	 * @var \FintechFab\MPSP\Entities\Member
	 */
	private $member;

	/**
	 * Длина кода для номера транзакции
	 *
	 * @var int
	 */
	private $codeLength = 5;
	/**
	 * @var Member
	 */
	private $members;
	/**
	 * @var \FintechFab\MPSP\Repositories\TransferRepository
	 */
	private $transfers;
	/**
	 * @var \FintechFab\MPSP\Validator\CardValidator
	 */
	private $cardValidator;
	/**
	 * @var \FintechFab\MPSP\Validator\ReceiverValidator
	 */
	private $receiverValidator;

	public function __construct(
		Transfer $transfer,
		TransferStatusSwitcher $transferStatusSwitcher,
		TransferValidator $transferValidator,
		CardValidator $cardValidator,
		ReceiverValidator $receiverValidator,
		SenderValidator $senderValidator,
		TransferRepository $transfers,
		Carbon $carbon,
		Member $members,
		Code $code
	)
	{
		$this->transfer = $transfer;
		$this->transferStatusSwitcher = $transferStatusSwitcher;
		$this->transferValidator = $transferValidator;
		$this->cardValidator = $cardValidator;
		$this->receiverValidator = $receiverValidator;
		$this->senderValidator = $senderValidator;
		$this->transfers = $transfers;
		$this->carbon = $carbon;
		$this->salt = Config::get('transfer.salt');
		$this->members = $members;
		$this->code = $code;
	}

	/**
	 * Задать сумму перевода
	 *
	 * @param $amount
	 */
	public function setAmount($amount)
	{
		$this->transfer->amount = $amount;
	}

	/**
	 * Задать сумму комиссии
	 *
	 * @param $amount
	 */
	public function setFee($amount)
	{
		$this->transfer->fee = $amount;
	}

	/**
	 * Задать валюту перевода
	 *
	 * @param $currency
	 */
	public function setCurrency($currency)
	{
		$this->transfer->currency = $currency;
	}

	/**
	 * Задать банк. карту
	 *
	 * @param \FintechFab\MPSP\Entities\Card $card
	 */
	public function setCard(Card $card)
	{
		$this->card = $card;
	}

	/**
	 * Задать отправителя
	 *
	 * @param Sender $sender
	 */
	public function setSender(Sender $sender)
	{
		$this->sender = $sender;
	}

	/**
	 * Задать получателя
	 *
	 * @param \FintechFab\MPSP\Entities\Receiver $receiver
	 */
	public function setReceiver(Receiver $receiver)
	{
		$this->receiver = $receiver;

		$this->transfer->receiver_name = $receiver->name;
		$this->transfer->receiver_surname = $receiver->surname;
		$this->transfer->receiver_thirdname = $receiver->thirdname;
		$this->transfer->receiver_city = $receiver->city;
	}

	/**
	 * @return \FintechFab\MPSP\Entities\Transfer
	 *
	 * @throws \FintechFab\MPSP\Exceptions\ValidatorException
	 */
	public function create()
	{
		$this->transferValidator->doValidate($this->getTransferAttributes());
		$this->cardValidator->doValidate($this->getCardAttributes());
		$this->receiverValidator->doValidate($this->getReceiverAttributes());
		$this->senderValidator->doValidate($this->getSenderAttributes());

		// создаем объект Member
		$this->member = $this->members->firstOrCreate(['phone' => $this->sender->phone]);

		// задаем код трансфера
		$this->setTransferCode();

		// Сохраняем банковскую карту
		$this->card->member_id = $this->member->id;
		$this->card->save();

		$this->transfer->transfer_card_id = $this->card->id;

		// сохраняем данные
		$this->transfer->save();

		// Связываем клиента и транзакцию
		$this->transfer->members()->attach($this->member, ['type' => MemberType::C_TYPE_SENDER]);

		// Телефон получателя
		if ($this->receiver->phone) {
			$receiver = $this->members->firstOrCreate(['phone' => $this->receiver->phone]);
			$this->transfer->members()->attach($receiver, ['type' => MemberType::C_TYPE_RECEIVER]);
		}

		// переключаем в статус - новый
		$this->transferStatusSwitcher->doNew($this->transfer);

		Log::info('Транзакция создана', ['id' => $this->transfer->id, 'code' => $this->transfer->code]);

		return $this->transfer;
	}

	/**
	 * Получить данные трансфера
	 *
	 * @return array
	 */
	public function getTransferAttributes()
	{
		return $this->transfer->toArray();
	}

	/**
	 * Получить данные трансфера
	 *
	 * @return array
	 */
	public function getCardAttributes()
	{
		return $this->card->getDecryptedAttributes();
	}

	/**
	 * Получить данные получателя
	 *
	 * @return array
	 */
	public function getReceiverAttributes()
	{
		return $this->receiver->toArray();
	}

	/**
	 * Получить данные отправителя
	 *
	 * @return array
	 */
	public function getSenderAttributes()
	{
		return $this->sender->toArray();
	}

	/**
	 * Задать код трансфера
	 */
	public function setTransferCode()
	{
		// Всего 100 попыток на генерацию уникального кода
		$attempts = 100;

		while ($attempts > 0) {
			$attempts--;

			$this->transfer->code = $this->code->generatePassword($this->codeLength);
			Log::info('Generate code ' . $this->transfer->code, [$this->member->phone, $this->receiver->phone]);

			$transfer = $this->transfers->findByPhoneAndCode($this->member->phone, $this->transfer->code);
			if (!is_null($transfer)) {
				// Отправитель с таким кодом уже существует, следующая попытка
				continue;
			}

			if ($this->receiver->phone) {
				$transfer = $this->transfers->findByPhoneAndCode($this->receiver->phone, $this->transfer->code);
				if ($transfer) {
					// Получатель с таким кодом уже существует.
					continue;
				}
			}

			// Всё хорошо, код сгенерирован, дубликатов нет
			return;
		}

		throw new Exception('Невозможно сгенерировать проверочный код');
	}
}