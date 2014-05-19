<?php

use Illuminate\Queue\QueueInterface;
use FintechFab\MPSP\Entities\Transfer;
use FintechFab\MPSP\Repositories\TransferRepository;

/**
 * @property \Mockery\MockInterface|mixed                                               $transfers
 * @property \Mockery\MockInterface|mixed                                               $transfer
 * @property \Mockery\MockInterface|mixed                                               $queue
 */
class AcquiringControllerTest extends TestCase
{

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

		$this->transfers->shouldReceive('findByPhoneAndCode')
			->with($phone, $code)
			->andReturn($this->transfer)
			->once();

		$this->transfer->shouldReceive('toArray')
			->andReturn(['transfer_array']);

		Queue::shouldReceive('connection')
			->withArgs(['gateway'])
			->andReturn($this->queue)
			->once();

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

		$response = $this->call('POST', 'acquiring/finish_3ds', [
			'phone'   => $phone,
			'code'    => $code,
			'MD'      => $MD,
			'TermUrl' => $TermUrl,
		]);

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