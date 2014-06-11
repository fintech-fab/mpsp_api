<?php namespace FintechFab\MPSP\Tests\Queue\Jobs;

use FintechFab\MPSP\Tests\TestCase;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Queue\QueueInterface;
use FintechFab\MPSP\Entities\Transfer;
use FintechFab\MPSP\Queue\Jobs\TransferCheckResultJob;
use FintechFab\MPSP\Repositories\TransferRepository;
use FintechFab\MPSP\Services\TransferStatusSwitcher;
use Log;

/**
 * @property \Mockery\MockInterface         $transfers
 * @property \Mockery\MockInterface|mixed   $job
 * @property \Mockery\MockInterface         $transfer
 * @property \Mockery\MockInterface|mixed   $transferStatusSwitcher
 * @property \Mockery\MockInterface         $queue
 * @property TransferCheckResultJob         $checkResultJob
 */
class TransferCheckResultJobTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->queue = $this->mock(QueueInterface::class);
		$this->job = $this->mock(Job::class);
		$this->transfer = $this->mock(Transfer::class);
		$this->transfers = $this->mock(TransferRepository::class);
		$this->transferStatusSwitcher = $this->mock(TransferStatusSwitcher::class);
		$this->checkResultJob = new TransferCheckResultJob($this->transfers, $this->transferStatusSwitcher);

		$this->transfer
			->shouldReceive('toArray')
			->andReturn([]);
	}

	/**
	 * трансфер возможно перевести
	 */
	public function testSuccess()
	{
		$transferId = 11;
		$checkNumber = 'ca121231b';

		// ищем трансфер по id
		$this->transfers->shouldReceive('findById')
			->with($transferId)
			->andReturn($this->transfer)
			->once()
			->ordered();

		// устанавливаем статус
		$this->transferStatusSwitcher->shouldReceive('doCheckSuccess')
			->with($this->transfer)
			->once()
			->ordered();

		// удаляем задачу из очереди
		$this->job->shouldReceive('delete')
			->once()
			->ordered();

		Log::shouldReceive('info');

		$this->checkResultJob->fire($this->job, array(
			'transfer_id' => $transferId,
			'card'        => 'card_data',
			'checknumber' => $checkNumber
		));
	}

	/**
	 * трансфер нельзя перевести
	 */
	public function testError()
	{
		$transferId = 25;

		// ищем трансфер по id
		$this->transfers->shouldReceive('findById')
			->withArgs(array($transferId))
			->andReturn($this->transfer)
			->once()
			->ordered();

		// устанавливаем статус
		$this->transferStatusSwitcher->shouldReceive('doCheckFailure')
			->withArgs([$this->transfer])
			->once()
			->ordered();

		// удаляем задачу из очереди
		$this->job->shouldReceive('delete')
			->once()
			->ordered();

		$this->checkResultJob->fire($this->job, array(
			'transfer_id' => $transferId,
			'card'        => 'card_data',
			'error'       => array(
				'code'    => 22,
				'message' => 'error_message',
			),
		));
	}

	public function testCodeNotFound()
	{
		$transferId = 33;

		// ищем трансфер по id
		// не находим
		$this->transfers->shouldReceive('findById')
			->withArgs(array($transferId))
			->andReturnNull()
			->once()
			->ordered();

		// логируем ошибку
		Log::shouldReceive('error')
			->once()
			->ordered();

		Log::shouldReceive('info');

		// задача удалена
		$this->job->shouldReceive('delete')
			->once()
			->ordered();

		$this->transfer->shouldReceive('setStatus')->never();

		$this->checkResultJob->fire($this->job, array(
			'transfer_id' => $transferId,
		));

	}

} 