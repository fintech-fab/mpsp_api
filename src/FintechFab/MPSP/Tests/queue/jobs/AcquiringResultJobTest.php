<?php namespace FintechFab\Tests\Queue\Jobs;

use FintechFab\MPSP\Tests\TestCase;
use Illuminate\Queue\Jobs\Job;
use FintechFab\MPSP\Entities\Transfer;
use FintechFab\MPSP\Queue\Jobs\AcquiringResultJob;
use FintechFab\MPSP\Repositories\TransferRepository;
use FintechFab\MPSP\Services\TransferStatusSwitcher;
use Log;

/**
 * @property \Mockery\MockInterface|mixed                                               $transfers
 * @property \Mockery\MockInterface|mixed                                               $transferStatusSwitcher
 * @property \Mockery\MockInterface|mixed                                               $job
 * @property \Mockery\MockInterface|mixed                                               $transfer
 * @property AcquiringResultJob                                                         $acquiringResultJob
 */
class AcquiringResultJobTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->transfer = $this->mock(Transfer::class);
		$this->transfers = $this->mock(TransferRepository::class);
		$this->transferStatusSwitcher = $this->mock(TransferStatusSwitcher::class);
		$this->job = $this->mock(Job::class);

		$this->acquiringResultJob = new AcquiringResultJob($this->transfers, $this->transferStatusSwitcher);
	}

	public function testSuccess()
	{
		$irn = 'dsakldsada';
		$rrn = 'dszlkdjkpd1';
		$transferId = 11;

		$this->transfers->shouldReceive('findById')
			->withArgs([$transferId])
			->andReturn($this->transfer)
			->once();

		$this->transfer->shouldReceive('setAttribute')
			->with('irn', $irn)
			->once();

		$this->transfer->shouldReceive('setAttribute')
			->with('rrn', $rrn)
			->once();

		$this->transfer->shouldReceive('save')->once();

		$this->transferStatusSwitcher->shouldReceive('doToSend')
			->withArgs([$this->transfer])
			->once();

		$this->job->shouldReceive('delete')->once();

		$this->acquiringResultJob->fire($this->job, [
			'transfer_id' => $transferId,
			'need_3ds'    => false,
			'irn'         => $irn,
			'rrn'         => $rrn,
		]);
	}

	public function testError()
	{
		$irn = 'dsakldsada';
		$rrn = 'dszlkdjkpd1';
		$transferId = 11;

		$this->transfers->shouldReceive('findById')
			->withArgs([$transferId])
			->andReturn($this->transfer)
			->once();

		$this->transfer->shouldReceive('setAttribute')
			->with('irn', $irn)
			->once();

		$this->transfer->shouldReceive('setAttribute')
			->with('rrn', $rrn)
			->once();

		$this->transfer->shouldReceive('save')->once();

		$this->transferStatusSwitcher->shouldReceive('doAcquiringError')
			->withArgs([$this->transfer])
			->once();

		$this->job->shouldReceive('delete')->once();

		$this->acquiringResultJob->fire($this->job, [
			'transfer_id' => $transferId,
			'need_3ds'    => false,
			'error'       => 'error_data',
			'irn'         => $irn,
			'rrn'         => $rrn,
		]);
	}

	public function test3DS()
	{
		$irn = 'dsakldsada';
		$rrn = 'dszlkdjkpd1';
		$transferId = 11;

		$this->transfers->shouldReceive('findById')
			->withArgs([$transferId])
			->andReturn($this->transfer)
			->once();

		$this->transfer->shouldReceive('setAttribute')
			->with('irn', $irn)
			->once();

		$this->transfer->shouldReceive('setAttribute')
			->with('rrn', $rrn)
			->once();

		$this->transfer->shouldReceive('save')->once();

		$this->transferStatusSwitcher->shouldReceive('do3DS')
			->withArgs([$this->transfer, 'some_url', ['post']])
			->once();

		$this->job->shouldReceive('delete')->once();

		$this->acquiringResultJob->fire($this->job, [
			'transfer_id'   => $transferId,
			'need_3ds'      => true,
			'3ds_url'       => 'some_url',
			'3ds_post_data' => ['post'],
			'irn'           => $irn,
			'rrn'           => $rrn,
		]);
	}

	public function testTransferNotFound()
	{
		$transferId = 11;

		$this->transfers->shouldReceive('findById')
			->withArgs([$transferId])
			->andReturnNull()
			->once();

		Log::shouldReceive('debug');
		Log::shouldReceive('error')->once();

		$this->job->shouldReceive('delete')->once();

		$this->acquiringResultJob->fire($this->job, [
			'transfer_id' => $transferId,
		]);
	}

} 