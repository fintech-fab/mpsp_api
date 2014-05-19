<?php

use Illuminate\Console\Command;
use FintechFab\MPSP\Entities\Card;
use FintechFab\MPSP\Entities\Receiver;
use FintechFab\MPSP\Entities\Sender;
use FintechFab\MPSP\Exceptions\ValidatorException;
use FintechFab\MPSP\Services\TransferFactory;
use FintechFab\MPSP\Services\TransferStatusSwitcher;
use Symfony\Component\Console\Input\InputOption;

class TransferCreate extends Command
{

	protected $name = 'transfer:create';
	protected $description = 'Точка входа по созданию запроса на перевод';

	private $card;
	private $receiver;
	private $transferFactory;
	private $transferStatusSwitcher;

	public function __construct(
		TransferFactory $transferFactory,
		Card $card,
		Receiver $receiver,
		Sender $sender,
		TransferStatusSwitcher $transferStatusSwitcher
	)
	{
		$this->transferFactory = $transferFactory;
		$this->card = $card;
		$this->receiver = $receiver;
		$this->sender = $sender;
		$this->transferStatusSwitcher = $transferStatusSwitcher;

		parent::__construct();
	}

	public function fire()
	{
		$phone = $this->option('phone');
		$amount = $this->option('amount');

		// задаем данные для объекта банк. карты
		$this->card->number = $this->option('card-number');
		$this->card->expire_month = $this->option('card-expire-month');
		$this->card->expire_year = $this->option('card-expire-year');
		$this->card->cvv = $this->option('card-cvv');

		// задаем данные для объекта получателя
		$this->receiver->surname = $this->option('receiver-surname');
		$this->receiver->name = $this->option('receiver-name');
		$this->receiver->thirdname = $this->option('receiver-thirdname');
		$this->receiver->city = $this->option('receiver-city');
		$this->receiver->phone = $this->option('receiver-phone');

		// задаем данные для трансфера
		$this->sender->phone = $phone;

		$this->transferFactory->setCard($this->card);
		$this->transferFactory->setReceiver($this->receiver);
		$this->transferFactory->setSender($this->sender);
		$this->transferFactory->setAmount($amount);
		$this->transferFactory->setFee($this->option('fee'));
		$this->transferFactory->setCurrency($this->option('currency'));

		$transfer = null;

		try {
			// создаем трансфер
			$transfer = $this->transferFactory->create();
			Log::info('Запрос на перевод создан', $transfer->toArray());

			$this->transferStatusSwitcher->doToCheck($transfer);
			Log::info('Запрос на перевод отправлен в gateway', $transfer->toArray());

			$this->info('Запрос создан: ');
			$this->comment('  id     = ' . $transfer->id);
			$this->comment('  code   = ' . $transfer->code);
			$this->comment('  amount = ' . $transfer->amount . " + " . $transfer->fee . " " . $transfer->currency);
			$this->comment('  date   = ' . $transfer->created_at);

		} catch (ValidatorException $exception) {

			// ошибка валидации
			$this->line('');
			$this->error('');
			$this->error('Ошибка входных параметров: ');
			foreach ($exception->getErrors() as $fieldName => $errors) {
				foreach ($errors as $error) {
					$this->error(sprintf("  [%-15s] $error", $fieldName));
				}
			}
			$this->error('');
			$this->error('');
			$this->line('');
			$this->line(sprintf('<info>%s</info>', sprintf($this->getSynopsis(), $this->getName())));
			$this->line("");
			$this->line("");

			return;
		}

	}

	public function error($message)
	{
		parent::error(iconv('CP1251', 'UTF-8', sprintf("%-80s", iconv('UTF-8', 'cp1251', $message))));
	}

	protected function getArguments()
	{
		return [];
	}

	protected function getOptions()
	{
		return [
			['phone', null, InputOption::VALUE_REQUIRED, 'Телефон получателя', null],
			['amount', null, InputOption::VALUE_REQUIRED, 'Сумма перевода', null],
			['fee', null, InputOption::VALUE_REQUIRED, 'Комиссия за перевод', null],
			['currency', null, InputOption::VALUE_REQUIRED, 'Валюта перевода', 'RUR'],
			['card-number', null, InputOption::VALUE_REQUIRED, 'Номер карты отпрателя', null],
			['card-expire-month', null, InputOption::VALUE_REQUIRED, 'Срок действия карты, месяц', null],
			['card-expire-year', null, InputOption::VALUE_REQUIRED, 'Срок действия карты, год', null],
			['card-cvv', null, InputOption::VALUE_REQUIRED, 'CVV', null],
			['receiver-surname', null, InputOption::VALUE_REQUIRED, 'Фамилия получателя', null],
			['receiver-name', null, InputOption::VALUE_REQUIRED, 'Имя получателя', null],
			['receiver-thirdname', null, InputOption::VALUE_REQUIRED, 'Отчество получателя', null],
			['receiver-city', null, InputOption::VALUE_REQUIRED, 'Город получателя', null],
			['receiver-phone', null, InputOption::VALUE_REQUIRED, 'Телефон получателя', null],
		];
	}

}