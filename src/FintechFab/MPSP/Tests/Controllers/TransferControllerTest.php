<?php namespace FintechFab\MPSP\Tests\Controllers;

use Exception;
use FintechFab\MPSP\Calculator\Calculator;
use FintechFab\MPSP\Entities\City;
use FintechFab\MPSP\Entities\Member;
use FintechFab\MPSP\Entities\Transfer;
use FintechFab\MPSP\Entities\Card;
use FintechFab\MPSP\Entities\Receiver;
use FintechFab\MPSP\Entities\Sender;
use FintechFab\MPSP\Exceptions\ValidatorException;
use FintechFab\MPSP\Repositories\CityRepository;
use FintechFab\MPSP\Services\TransferFactory;
use FintechFab\MPSP\Sms\Sms;
use FintechFab\MPSP\Tests\TestCase;
use Log;
use Queue;

/**
 * @property \Mockery\MockInterface                                                                                      $transferCostCalculator
 * @property \Mockery\MockInterface                                                                                      $transfer
 * @property \Mockery\MockInterface                                                                                      $transferCard
 * @property \Mockery\MockInterface                                                                                      $transferFactory
 * @property \Mockery\MockInterface                                                                                      $receiver
 * @property \Mockery\MockInterface                                                                                      $sender
 * @property \Mockery\MockInterface                                                                                      $cities
 * @property \Mockery\MockInterface                                                                                      $city
 */
class TransferControllerTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();

		Queue::shouldReceive('connected');

		$this->transfer = $this->mock(Transfer::class);
		$this->transferCard = $this->mock(Card::class);
		$this->receiver = $this->mock(Receiver::class);
		$this->sender = $this->mock(Sender::class);
		$this->transferFactory = $this->mock(TransferFactory::class);
		$this->transferCostCalculator = $this->mock(Calculator::class);
		$this->cities = $this->mock(CityRepository::class);
		$this->city = $this->mock(City::class);
	}

	/**
	 * стоимость транзакции
	 *
	 * все прошло успешно
	 */
	public function testCost()
	{
		$this->transferCostCalculator->shouldReceive('setAmount')
			->with(100)
			->once()
			->ordered();

		$this->transferCostCalculator->shouldReceive('setCurrency')
			->with('RUR')
			->once()
			->ordered();

		$this->transferCostCalculator->shouldReceive('doCalculate')
			->andReturn(30.5)
			->once()
			->ordered();

		$this->call('POST', 'transfer/cost', [
			'amount'   => 100,
			'currency' => 'RUR',
		]);
	}

	/**
	 * стоимость транзакции
	 *
	 * ошибка валидации
	 */
	public function testCostValidationError()
	{
		$this->transferCostCalculator->shouldReceive('setAmount');
		$this->transferCostCalculator->shouldReceive('setCurrency');

		$this->transferCostCalculator->shouldReceive('doCalculate')
			->andThrow(ValidatorException::class)
			->once()
			->ordered();

		$response = $this->call('POST', 'transfer/cost', [
			'amount'   => 100,
			'currency' => 'ZZZ',
		]);

		$response = $response->getOriginalContent();

		$this->assertEquals(-1, $response['code']);
		$this->assertEquals('ошибка в валидации данных', $response['message']);
		$this->arrayHasKey('errors', $response['response']);
	}

	/**
	 * стоимость транзакции
	 *
	 * выпал exception
	 */
	public function testCostException()
	{
		$this->transferCostCalculator->shouldReceive('setAmount');
		$this->transferCostCalculator->shouldReceive('setCurrency');

		$this->transferCostCalculator->shouldReceive('doCalculate')
			->andThrow(Exception::class)
			->once()
			->ordered();

		// записались данные в лог
		Log::shouldReceive('critical')
			->once()
			->ordered();

		$response = $this->call('POST', 'transfer/cost', [
			'amount'   => 100,
			'currency' => 'zzz',
		]);

		$response = $response->getOriginalContent();

		$this->assertEquals(-9999, $response['code']);
		$this->assertEquals('системная ошибка', $response['message']);
	}

	/**
	 * Успешный перевод
	 */
	public function testCreateSuccess()
	{
		$transferAttributes = [
			'sender_phone' => '321321',
			'amount'       => 100,
			'fee'          => 30,
			'currency'     => 'RUR',
		];

		$cardNumber = '32132131221321';
		$cardExpireMonth = 2;
		$cardExpireYear = 15;
		$cardCVV = 123;

		$transferCardAttributes = [
			'card_number'       => $cardNumber,
			'card_expire_month' => $cardExpireMonth,
			'card_expire_year'  => $cardExpireYear,
			'card_cvv'          => $cardCVV,
		];

		$transferReceiverAttributes = [
			'receiver_surname'   => 'Пупкин',
			'receiver_name'      => 'Вася',
			'receiver_thirdname' => 'Иванович',
			'receiver_city_id'   => 8,
		];

		$post = array_merge($transferAttributes, $transferCardAttributes, $transferReceiverAttributes);

		// ищем город
		$this->cities->shouldReceive('findById')
			->with($transferReceiverAttributes['receiver_city_id'])
			->andReturn($this->city)
			->once();

		// задаем данные для карты
		$this->transferCard->shouldReceive('setAttribute')
			->with('number', $cardNumber)
			->once()
			->ordered();

		$this->transferCard->shouldReceive('setAttribute')
			->with('expire_month', $cardExpireMonth)
			->once()
			->ordered();

		$this->transferCard->shouldReceive('setAttribute')
			->with('expire_year', $cardExpireYear)
			->once()
			->ordered();

		$this->transferCard->shouldReceive('setAttribute')
			->with('cvv', $cardCVV)
			->once()
			->ordered();

		// задаем данные отправителя
		$this->sender->shouldReceive('offsetSet')
			->with('phone', $transferAttributes['sender_phone'])
			->once();

		// задаем данные получателя
		$this->receiver->shouldReceive('offsetSet')
			->with('surname', 'Пупкин')
			->once()
			->ordered();

		$this->receiver->shouldReceive('offsetSet')
			->with('name', 'Вася')
			->once()
			->ordered();

		$this->receiver->shouldReceive('offsetSet')
			->with('thirdname', 'Иванович')
			->once()
			->ordered();

		$this->city->shouldReceive('getAttribute')->with('id')->andReturn(8);

		$this->receiver->shouldReceive('offsetSet')
			->with('city', 8)
			->once()
			->ordered();

		$this->receiver->shouldReceive('offsetSet')
			->with('phone', null)
			->once()
			->ordered();

		// задаем банк. карту
		$this->transferFactory->shouldReceive('setCard')
			->with($this->transferCard)
			->once()
			->ordered();

		// задаем получателя
		$this->transferFactory->shouldReceive('setReceiver')
			->with($this->receiver)
			->once()
			->ordered();

		// задаем отправителя
		$this->transferFactory->shouldReceive('setSender')
			->with($this->sender)
			->once()
			->ordered();

		// задаем сумму
		$this->transferFactory->shouldReceive('setAmount')
			->with($transferAttributes['amount'])
			->once()
			->ordered();

		// задаем комиссию
		$this->transferFactory->shouldReceive('setFee')
			->withArgs(array($transferAttributes['fee']))
			->once()
			->ordered();

		// задаем валюту
		$this->transferFactory->shouldReceive('setCurrency')
			->withArgs(array($transferAttributes['currency']))
			->once()
			->ordered();

		// создаем трансфер
		$this->transferFactory->shouldReceive('create')
			->andReturn($this->transfer)
			->once()
			->ordered();

		$sender = $this->mock(Member::class);

		$this->transfer->shouldReceive('getAttribute')
			->with('sender')
			->andReturn($sender)
			->once();

		$sender->shouldReceive('getAttribute')
			->with('phone')
			->andReturn($transferAttributes['sender_phone'])
			->once();

		$sms = $this->mock(Sms::class);
		$sms->shouldReceive('code')
			->with($this->transfer)
			->once();

		$response = $this->call('POST', 'transfer/create', $post)
			->getOriginalContent();

		$expectedResponse = array(
			'transfer' => ['phone' => $transferAttributes['sender_phone']],
		);

		$this->assertEquals(0, $response['code']);
		$this->assertEquals('ok', $response['message']);
		$this->assertEquals($expectedResponse, $response['response']);
	}

	/**
	 * Перевод не удался
	 *
	 * ошибка в валидации
	 */
	public function testCreateValidationError()
	{
		$transferAttributes = [
			'phone'  => '321321',
			'amount' => 100,
		];

		$cardNumber = '32132131221321';
		$cardExpireMonth = 2;
		$cardExpireYear = 15;
		$cardCVV = 123;

		$transferCardAttributes = [
			'card_number'       => $cardNumber,
			'card_expire_month' => $cardExpireMonth,
			'card_expire_year'  => $cardExpireYear,
			'card_cvv'          => $cardCVV,
		];

		$post = array_merge($transferAttributes, $transferCardAttributes);

		$this->cities->shouldReceive('findById');
		$this->transferCard->shouldReceive('setAttribute');
		$this->receiver->shouldReceive('offsetSet');
		$this->sender->shouldReceive('offsetSet');
		$this->transferFactory->shouldReceive('setCard');
		$this->transferFactory->shouldReceive('setPhone');
		$this->transferFactory->shouldReceive('setAmount');
		$this->transferFactory->shouldReceive('setReceiver');
		$this->transferFactory->shouldReceive('setSender');
		$this->transferFactory->shouldReceive('setFee');
		$this->transferFactory->shouldReceive('setCurrency');

		// создаем трансфер и получаем ошибку валидации
		$this->transferFactory->shouldReceive('create')
			->andThrow(ValidatorException::class)
			->once()
			->ordered();

		$response = $this->call('POST', 'transfer/create', $post)
			->getOriginalContent();

		$this->assertEquals(-1, $response['code']);
		$this->assertEquals('ошибка в валидации данных', $response['message']);
		$this->assertArrayHasKey('errors', $response['response']);
	}

} 