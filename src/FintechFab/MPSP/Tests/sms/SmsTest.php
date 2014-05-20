<?php namespace FintechFab\MPSP\Tests\Sms;

use FintechFab\MPSP\Tests\TestCase;
use Illuminate\Queue\QueueInterface;
use Mockery;
use Mockery\MockInterface;
use FintechFab\MPSP\Entities\Member;
use FintechFab\MPSP\Entities\Transfer;
use FintechFab\MPSP\Sms\Sms;
use Queue;

class SmsTest extends TestCase
{

	/**
	 * @var MockInterface
	 */
	private $member;

	public function setUp()
	{
		parent::setUp();

		$this->member = Mockery::mock(Member::class);
	}

	public function testCode()
	{
		// Входные параметры
		$phone = '9173185';
		$code = '111';

		// Инициализация
		$queue = $this->mock(QueueInterface::class);
		Queue::shouldReceive('connection')
			->with('gateway')
			->andReturn($queue)
			->once();

		$queue->shouldReceive('push')
			->with('sms', ['phone' => $phone, 'message' => "Confirmation code: $code"])
			->once();

		$this->member->shouldReceive('getAttribute')
			->with('phone')
			->andReturn($phone)
			->once();

		$transfer = $this->mock(Transfer::class);
		$transfer->shouldReceive('getAttribute')
			->with('code')
			->andReturn($code);

		$transfer->shouldReceive('getAttribute')
			->with('sender')
			->andReturn($this->member)
			->once();

		// Выполнение
		$sms = new Sms;
		$sms->code($transfer);
	}

} 