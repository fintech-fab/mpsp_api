<?php namespace FintechFab\MPSP\Tests\Services;

use FintechFab\MPSP\Entities\Sender;
use FintechFab\MPSP\Tests\TestCase;
use Illuminate\Queue\QueueInterface;
use Mockery;
use Mockery\MockInterface;
use FintechFab\MPSP\Entities\City;
use FintechFab\MPSP\Entities\Transfer;
use FintechFab\MPSP\Entities\Card;
use FintechFab\MPSP\Entities\Receiver;
use FintechFab\MPSP\Queue\Support\QueueServiceProvider;
use FintechFab\MPSP\Repositories\CityRepository;
use FintechFab\MPSP\Services\TransferStatusSwitcher;
use Queue;

/**
 * @property \Mockery\MockInterface|mixed                         $transfer
 * @property MockInterface                                        $city
 * @property MockInterface                                        $cities
 * @property MockInterface                                        $transferSender
 * @property MockInterface                                        transferReceiver
 * @property TransferStatusSwitcher                               $transferStatusSwitcher
 * @property MockInterface                                        $queue
 */
class TransferStatusSwitchTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->transfer = $this->mock(Transfer::class);

		$this->city = $this->mock(City::class);
		$this->cities = $this->mock(CityRepository::class);

		$this->transferSender = $this->mock(Sender::class);
		$this->transferReceiver = $this->mock(Receiver::class);
		$this->transferStatusSwitcher = new TransferStatusSwitcher($this->transferSender, $this->transferReceiver);

		$this->queue = $this->mock(QueueInterface::class);
	}

	public function testDoNew()
	{
		$this->transfer->shouldReceive('setStatus')
			->withArgs([1])
			->once();

		$card = $this->mock(Card::class);

		$card->shouldReceive('getAttribute')
			->with('id')
			->once();

		Queue::shouldReceive('connection')
			->with('api')
			->andReturn($this->queue)
			->once();

		$this->queue
			->shouldReceive('later')
			->with(TransferStatusSwitcher::C_CLEAN_CARD_DELAY, QueueServiceProvider::C_CARD_CLEAN, Mockery::any())
			->once();

		$this->transfer->shouldReceive('getAttribute')
			->with('card')
			->andReturn($card)
			->once();

		$this->transferStatusSwitcher->doNew($this->transfer);
	}

	public function testDoToSend()
	{
		$this->transfer->shouldReceive('getAttribute')->with('checknumber');
		$this->transfer->shouldReceive('toArray');
		$this->transfer->shouldReceive('getAttribute')->with('receiver_name');
		$this->transfer->shouldReceive('getAttribute')->with('receiver_surname');
		$this->transfer->shouldReceive('getAttribute')->with('receiver_thirdname');
		$this->transfer->shouldReceive('getAttribute')->with('receiver_city')->andReturn(888);

		$this->transfer->shouldReceive('setStatus')
			->withArgs([8])
			->once()
			->ordered();

		$this->transfer->shouldReceive('setStatus')
			->withArgs([9])
			->once()
			->ordered();

		$this->transferReceiver->shouldReceive('loadFromTransfer')
			->with($this->transfer)
			->andReturn($this->transferReceiver)
			->once();

		$this->transferReceiver->shouldReceive('toArray')->once();

		$this->transferSender->shouldReceive('loadFromTransfer')
			->with($this->transfer)
			->andReturn($this->transferSender)
			->once();

		$this->transferSender->shouldReceive('toArray')->once();

		Queue::shouldReceive('connection')
			->with('gateway')
			->andReturn($this->queue)
			->once();

		$this->queue
			->shouldReceive('push')
			->with('transferSend', Mockery::any())
			->once();

		$this->transferStatusSwitcher->doToSend($this->transfer);
	}

	public function testDoAcquiringError()
	{
		$this->transfer->shouldReceive('setStatus')
			->withArgs([7])
			->once();

		$this->transferStatusSwitcher->doAcquiringError($this->transfer);
	}

	public function testDo3DS()
	{
		$this->transfer->shouldReceive('setStatus')
			->with(6)
			->once()
			->ordered();

		$this->transfer->shouldReceive('setAttribute')
			->with('3ds_url', 'url')
			->once()
			->ordered();

		$this->transfer->shouldReceive('setAttribute')
			->with('3ds_post_data', json_encode(['array'], JSON_UNESCAPED_UNICODE))
			->once()
			->ordered();

		$this->transfer->shouldReceive('save')
			->once()
			->ordered();

		$this->transferStatusSwitcher->do3DS($this->transfer, 'url', ['array']);
	}

	public function testDoCheckSuccess()
	{
		$this->transfer->shouldReceive('setStatus')
			->with(4)
			->once();

		// ставим задачу на снятие средств
		$this->transfer->shouldReceive('toArray')
			->andReturn(['transfer_array'])
			->once()
			->ordered();

		$oQueueInterface = $this->mock(QueueInterface::class);

		Queue::shouldReceive('connection')
			->with('gateway')
			->andReturn($oQueueInterface)
			->once()
			->ordered();

		$oQueueInterface->shouldReceive('push')
			->with(
				'acquiring',
				[
					'transfer' => ['transfer_array'],
					'card'     => 'encrypted_card',
				]
			)
			->once()
			->ordered();

		$transferCard = $this->mock(Card::class);

		$this->transfer
			->shouldReceive('getAttribute')
			->with('card')
			->andReturn($transferCard)
			->twice();

		$transferCard
			->shouldReceive('toArray')
			->with()
			->andReturn('encrypted_card')
			->once();

		$this->transfer
			->shouldReceive('setStatus')
			->with(5)
			->once();

		$transferCard
			->shouldReceive('clean')
			->once();

		$this->transferStatusSwitcher->doCheckSuccess($this->transfer, 'checknumber');
	}

	public function testDoCheckFailure()
	{
		$this->transfer->shouldReceive('setStatus')
			->with(3)
			->once()
			->ordered();

		$this->transferStatusSwitcher->doCheckFailure($this->transfer);
	}

} 