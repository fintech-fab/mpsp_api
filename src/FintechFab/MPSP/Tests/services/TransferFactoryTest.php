<?php namespace FintechFab\MPSP\Tests\Services;

use Carbon\Carbon;
use Config;
use Exception;
use FintechFab\MPSP\Tests\TestCase;
use Illuminate\Queue\QueueInterface;
use FintechFab\MPSP\Entities\Member;
use FintechFab\MPSP\Entities\Transfer;
use FintechFab\MPSP\Entities\Card;
use FintechFab\MPSP\Entities\Receiver;
use FintechFab\MPSP\Entities\Sender;
use FintechFab\MPSP\Exceptions\ValidatorException;
use FintechFab\MPSP\Repositories\TransferRepository;
use FintechFab\MPSP\Services\Code;
use FintechFab\MPSP\Services\TransferFactory;
use FintechFab\MPSP\Services\TransferStatusSwitcher;
use FintechFab\MPSP\Validator\CardValidator;
use FintechFab\MPSP\Validator\ReceiverValidator;
use FintechFab\MPSP\Validator\SenderValidator;
use FintechFab\MPSP\Validator\TransferValidator;
use Log;
use Queue;

/**
 * @property \Mockery\MockInterface                                             $transfer
 * @property \Mockery\MockInterface                                             $transferValidator
 * @property \Mockery\MockInterface|mixed                                       $card
 * @property \Mockery\MockInterface|mixed                                       $receiver
 * @property \Mockery\MockInterface|mixed                                       $sender
 * @property \Mockery\MockInterface                                             $queue
 * @property \Mockery\MockInterface|mixed                                       $carbon
 * @property \Mockery\MockInterface|mixed                                       $transferStatusSwitcher
 * @property \Mockery\MockInterface|mixed                                       $member
 * @property \FintechFab\MPSP\Services\TransferFactory                                     $transferFactory
 * @property \Mockery\MockInterface|mixed                                       $transfers
 * @property \Mockery\MockInterface|mixed                                       $code
 * @property \Mockery\MockInterface|mixed                                       $cardValidator
 * @property \Mockery\MockInterface|mixed                                       $receiverValidator
 * @property \Mockery\MockInterface|mixed                                       $senderValidator
 */
class TransferFactoryTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->transfer = $this->mock(Transfer::class);
		$this->transferValidator = $this->mock(TransferValidator::class);
		$this->cardValidator = $this->mock(CardValidator::class);
		$this->receiverValidator = $this->mock(ReceiverValidator::class);
		$this->senderValidator = $this->mock(SenderValidator::class);
		$this->card = $this->mock(Card::class);
		$this->sender = $this->mock(Sender::class);
		$this->receiver = $this->mock(Receiver::class);
		$this->queue = $this->mock(QueueInterface::class);
		$this->transferStatusSwitcher = $this->mock(TransferStatusSwitcher::class);
		$this->carbon = $this->mock(Carbon::class);
		$this->member = $this->mock(Member::class);
		$this->transfers = $this->mock(TransferRepository::class);
		$this->code = $this->mock(Code::class);

		Config::shouldReceive('get')
			->withArgs(array('transfer.salt'))
			->andReturn(123)
			->once()
			->ordered();

		Log::shouldReceive('info');

		$this->transferFactory = new TransferFactory(
			$this->transfer, $this->transferStatusSwitcher,
			$this->transferValidator, $this->cardValidator, $this->receiverValidator, $this->senderValidator,
			$this->transfers,
			$this->carbon,
			$this->member,
			$this->code
		);
	}

	// задать сумму
	public function testSetAmount()
	{
		$amount = 100.5;

		$this->transfer->shouldReceive('setAttribute')
			->with('amount', $amount)
			->once();

		$this->transferFactory->setAmount($amount);
	}

	// задать комиссию
	public function testSetFee()
	{
		$fee = 30;

		$this->transfer->shouldReceive('setAttribute')
			->with('fee', $fee)
			->once();

		$this->transferFactory->setFee($fee);
	}

	// задать валюту
	public function testSetCurrency()
	{
		$currency = 'RUR';

		$this->transfer->shouldReceive('setAttribute')
			->with('currency', $currency)
			->once();

		$this->transferFactory->setCurrency($currency);
	}

	/**
	 * задать получателя перевода
	 */
	public function testSetReceiver()
	{
		$receiverName = 'dsdzsdzsd';
		$receiverSurname = 'zzzzz';
		$receiverThirdname = 'dzsldszkdzs';
		$receiverCity = 'Одесса';

		$receiverAttributes = [
			'receiver_name'      => $receiverName,
			'receiver_surname'   => $receiverSurname,
			'receiver_thirdname' => $receiverThirdname,
			'receiver_city'      => $receiverCity,
		];

		// получаем данные получателя перевода
		$this->receiver->shouldReceive('offsetGet')
			->with('name')
			->andReturn($receiverName)->once()->ordered();

		$this->receiver->shouldReceive('offsetGet')
			->with('surname')
			->andReturn($receiverSurname)->once()->ordered();

		$this->receiver->shouldReceive('offsetGet')
			->with('thirdname')
			->andReturn($receiverThirdname)->once()->ordered();

		$this->receiver->shouldReceive('offsetGet')
			->with('city')
			->andReturn($receiverCity)->once()->ordered();

		$this->transfer->shouldReceive('setAttribute')
			->with('receiver_name', $receiverName)->once()->ordered();

		$this->transfer->shouldReceive('setAttribute')
			->with('receiver_surname', $receiverSurname)->once()->ordered();

		$this->transfer->shouldReceive('setAttribute')
			->with('receiver_thirdname', $receiverThirdname)->once()->ordered();

		$this->transfer->shouldReceive('setAttribute')
			->with('receiver_city', $receiverCity)->once()->ordered();

		$this->transferFactory->setReceiver($this->receiver);

		$this->receiver->shouldReceive('toArray')
			->andReturn($receiverAttributes)
			->once();

		$this->assertEquals($receiverAttributes, $this->transferFactory->getReceiverAttributes());
	}

	/**
	 * Создание трансфера с успешной валидацией
	 */
	public function testSetCreate()
	{
		$transferAttributes = [];
		$cardAttributes = [];
		$receiverAttributes = [];
		$senderAttributes = [
			'phone' => '3213213211',
		];
		$this->transfer->shouldReceive('toArray')->andReturn($transferAttributes);
		$this->card->shouldReceive('getDecryptedAttributes')->andReturn($cardAttributes);
		$this->receiver->shouldReceive('toArray')->andReturn($receiverAttributes);
		$this->sender->shouldReceive('toArray')->andReturn($senderAttributes);

		//
		$this->receiver->shouldReceive('offsetGet')->with('name')->andReturn('Name');
		$this->receiver->shouldReceive('offsetGet')->with('surname')->andReturn('Surname');
		$this->receiver->shouldReceive('offsetGet')->with('thirdname')->andReturn('Thirdname');
		$this->receiver->shouldReceive('offsetGet')->with('city')->andReturn('MyCity');
		$this->transfer->shouldReceive('setAttribute')
			->with('receiver_name', 'Name')->once();
		$this->transfer->shouldReceive('setAttribute')
			->with('receiver_surname', 'Surname')->once();
		$this->transfer->shouldReceive('setAttribute')
			->with('receiver_thirdname', 'Thirdname')->once();
		$this->transfer->shouldReceive('setAttribute')
			->with('receiver_city', 'MyCity')->once();
		$this->transfer->shouldReceive('setAttribute')
			->with('amount', 100)->once();
		$this->transfer->shouldReceive('setAttribute')
			->with('currency', 'RUR')->once();
		$this->transfer->shouldReceive('setAttribute')
			->with('fee', 30)->once();

		// валидируем данные
		$this->transferValidator->shouldReceive('doValidate')
			->with($transferAttributes);
		$this->cardValidator->shouldReceive('doValidate')
			->with($cardAttributes);
		$this->receiverValidator->shouldReceive('doValidate')
			->with($receiverAttributes);
		$this->senderValidator->shouldReceive('doValidate')
			->with($senderAttributes);

		//
		$this->sender->shouldReceive('offsetGet')
			->with('phone')
			->andReturn($senderAttributes['phone']);

		$this->member->shouldReceive('firstOrCreate')
			->with(['phone' => $senderAttributes['phone']])
			->andReturn($this->member)
			->once();

		// задаем код трансфера
		$this->code->shouldReceive('generatePassword')
			->with(5)
			->andReturn('dsazs')
			->once();

		$this->transfer->shouldReceive('setAttribute')->with('code', 'dsazs')->once();

		$this->transfer->shouldReceive('getAttribute')->with('code')->andReturn('dsazs');

		$this->member->shouldReceive('getAttribute')
			->with('phone')
			->andReturn($senderAttributes['phone']);

		$this->receiver->shouldReceive('offsetGet')->with('phone');

		$this->transfers->shouldReceive('findByPhoneAndCode')
			->with($senderAttributes['phone'], 'dsazs')
			->once();

		// сохраняем карту
		$this->member->shouldReceive('getAttribute')
			->with('id')
			->andReturn(55)
			->once();

		$this->card->shouldReceive('setAttribute')
			->with('member_id', 55)
			->once();

		$this->card->shouldReceive('save')->once();

		$this->card->shouldReceive('getAttribute')
			->with('id')
			->andReturn(77);

		$this->transfer->shouldReceive('setAttribute')
			->with('transfer_card_id', 77)
			->once();

		// сохраняем данные трансфера
		$this->transfer->shouldReceive('save')->once()->ordered();

		// связываем клиента и транзакцию
		$this->transfer->shouldReceive('members')->andReturn($this->transfer);
		$this->transfer->shouldReceive('attach')
			->with($this->member, ['type' => 1])
			->once();

		// переключаем в статус - новый
		$this->transferStatusSwitcher->shouldReceive('doNew')->with($this->transfer)->once();
		$this->transfer->shouldReceive('getAttribute')->with('id');

		// задаем данные
		$this->transferFactory->setSender($this->sender);
		$this->transferFactory->setReceiver($this->receiver);
		$this->transferFactory->setAmount(100);
		$this->transferFactory->setCurrency('RUR');
		$this->transferFactory->setFee(30);
		$this->transferFactory->setCard($this->card);

		$this->transferFactory->create();
	}

	/**
	 * Создаем трансфер, но валидация не проходит
	 */
	public function SetCreateWithValidationError()
	{
		$transferAttributes = [
			'phone'  => '79671234567',
			'amount' => 100.5,
		];

		// получаем аттрибуты
		$this->transfer->shouldReceive('getAttributes')
			->andReturn($transferAttributes)
			->once()
			->ordered();

		// валидация не прошла - упал Exception
		$this->transferValidator->shouldReceive('doValidateForSend')
			->withArgs(array($transferAttributes))
			->andThrow(ValidatorException::class)
			->once()
			->ordered();

		// объект не сохранился
		$this->transfer->shouldReceive('save')
			->never();

		// задача в очередь не поставилась
		Queue::shouldReceive('push')
			->never();

		try {
			$this->transferFactory->create();
		} catch (Exception $e) {
			$this->assertInstanceOf(ValidatorException::class, $e);
		}
	}

}
 