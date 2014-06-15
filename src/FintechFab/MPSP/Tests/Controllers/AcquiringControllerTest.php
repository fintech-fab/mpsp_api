<?php namespace FintechFab\MPSP\Tests\Controllers;

use FintechFab\MPSP\Entities\Transfer;
use FintechFab\MPSP\Repositories\TransferRepository;
use FintechFab\MPSP\Tests\TestCase;
use Illuminate\Queue\QueueInterface;
use Queue;

/**
 * @property \Mockery\MockInterface|mixed                                               $transfers
 * @property \Mockery\MockInterface|mixed                                               $transfer
 * @property \Mockery\MockInterface|mixed                                               $queue
 */
class AcquiringControllerTest extends TestCase
{

	/**
	 * Перед запуском теста, заменяются те инстансы в IoC, которые мы не планируем здесь тестировать
	 *
	 * @see method mock
	 */
	public function setUp()
	{
		parent::setUp();

		Queue::shouldReceive('connected');

		$this->transfers = $this->mock(TransferRepository::class);
		$this->transfer = $this->mock(Transfer::class);
		$this->queue = $this->mock(QueueInterface::class);
	}

	public function testFinish_3DS_Success()
	{
		$phone = '32132113';
		$code = 'klasdklsad;lkasdl;ka';

		$MD = 'dzsd';
		$TermUrl = 'dsadasdas';

		// ищем трансфер по номеру телефона и коду
		$this->transfers->shouldReceive('findByPhoneAndCode')
			->with($phone, $code)
			->andReturn($this->transfer)
			->once();

		// превращаем полученный трансфер в массив
		$this->transfer->shouldReceive('toArray')
			->andReturn(['transfer_array']);

		// было установлено подключение к серверу очередей к очереди gateway
		Queue::shouldReceive('connection')
			->withArgs(['gateway'])
			->andReturn($this->queue)
			->once();

		// задача ушла в сервер очередей
		$this->queue->shouldReceive('push')
			->withArgs([
				'acquiringFinish3DS', [
					'transfer' => ['transfer_array'],
					'3ds_data' => [
						'MD'      => $MD,
						'TermUrl' => $TermUrl,
					],
				]
			]);

		// Все подмены действуют во время этого “как бы” http-запроса
		// Цикл приложения (route -> filters -> controller -> action -> component -> view) работает, не подозревая о подменах и тестовом окружении
		$response = $this->call('POST', 'acquiring/finish_3ds', [
			'phone'   => $phone,
			'code'    => $code,
			'MD'      => $MD,
			'TermUrl' => $TermUrl,
		]);

		// запрос успешен
		$this->assertResponseOk();

		$response = $response->getOriginalContent();

		$this->assertEquals(0, $response['code']);
		$this->assertEquals(['transfer_array'], $response['response']['transfer']);
	}

	public function testFinish_3DS_Input_Null()
	{
		$response = $this->call('POST', 'acquiring/finish_3ds', [
			'phone' => null,
			'code'  => null,
		]);

		$this->assertResponseOk();

		$response = $response->getOriginalContent();

		$this->assertEquals(-1, $response['code']);
		$this->assertEquals('phone или code некорректны', $response['message']);
	}

	public function testFinish_Transfer_Not_Found()
	{
		$phone = '32132112';
		$code = 'dsadsada';

		$this->transfers->shouldReceive('findByPhoneAndCode')
			->withArgs([$phone, $code])
			->andReturn(null)
			->once();

		$response = $this->call('POST', 'acquiring/finish_3ds', [
			'phone' => $phone,
			'code'  => $code,
		]);

		$this->assertResponseOk();

		$response = $response->getOriginalContent();

		$this->assertEquals(-2, $response['code']);
		$this->assertEquals('трансфер не существует', $response['message']);
	}

}