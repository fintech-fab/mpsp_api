<?php

use Illuminate\Console\Command;
use FintechFab\MPSP\Repositories\TransferRepository;
use Symfony\Component\Console\Input\InputOption;

class TransferFind extends Command
{

	protected $name = 'transfer:find';
	protected $description = 'Найти перевод';

	private $transfers;

	public function __construct(TransferRepository $transfers)
	{
		parent::__construct();
		$this->transfers = $transfers;
	}

	public function fire()
	{
		$phone = $this->option('phone');
		$code = $this->option('code');

		// создаем трансфер
		$transfer = $this->transfers->findByPhoneAndCode($phone, $code);

		if ($transfer) {
			$this->info("Transfer: " . print_r($transfer->toArray(), true));
		} else {
			$this->comment('Transfer not found');
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
			['phone', null, InputOption::VALUE_REQUIRED, 'Телефон (получателя или отправителя)', null],
			['code', null, InputOption::VALUE_REQUIRED, 'Цифровой код', null],
		];
	}

}

