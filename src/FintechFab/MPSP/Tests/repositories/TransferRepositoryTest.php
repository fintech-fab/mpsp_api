<?php namespace FintechFab\MPSP\Tests\Repositories;

use FintechFab\MPSP\Entities\Transfer;
use FintechFab\MPSP\Repositories\TransferRepository;
use FintechFab\MPSP\Tests\TestCase;

/**
 * @property \Mockery\MockInterface                                 $transfer
 * @property \FintechFab\MPSP\Repositories\TransferRepository                  $transfers
 */
class TransferRepositoryTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->transfer = $this->mock(Transfer::class);
		$this->transfers = new TransferRepository($this->transfer);
	}

	public function testFindById()
	{
		$id = 10;

		$this->transfer->shouldReceive('newInstance')
			->andReturn($this->transfer)
			->once()
			->ordered();

		$this->transfer->shouldReceive('find')
			->withArgs([$id])
			->andReturn($this->transfer)
			->once()
			->ordered();

		$result = $this->transfers->findById($id);

		$this->assertInstanceOf(Transfer::class, $result);
	}

	/**
	 * поиск трансфера по коду
	 */
	public function testFindByCode()
	{
		$code = 'ssszzz';

		$this->transfer->shouldReceive('newInstance')
			->andReturn($this->transfer)
			->once()
			->ordered();

		$this->transfer->shouldReceive('where')
			->withArgs(array('code', $code))
			->andReturn($this->transfer)
			->once()
			->ordered();

		$this->transfer->shouldReceive('first')
			->andReturn($this->transfer)
			->once()
			->ordered();

		$result = $this->transfers->findByCode($code);

		$this->assertInstanceOf(Transfer::class, $result);
	}

	/**
	 * поиск по номеру телефона и коду
	 */
	public function testFindByPhoneAndCode()
	{
		$code = 'ssszzz';
		$phone = '79671234567';

		$this->transfer->shouldReceive('newInstance')
			->andReturn($this->transfer)
			->once()
			->ordered();

		$this->transfer->shouldReceive('select')
			->with('transfers.*')
			->andReturn($this->transfer)
			->once()
			->ordered();

		$this->transfer->shouldReceive('from')
			->with('transfers')
			->andReturn($this->transfer)
			->once()
			->ordered();

		$this->transfer->shouldReceive('join')
			->andReturn($this->transfer)
			->twice();

		$this->transfer->shouldReceive('where')
			->withArgs(['code', $code])
			->andReturn($this->transfer)
			->once()
			->ordered();

		$this->transfer->shouldReceive('where')
			->withArgs(['phone', $phone])
			->andReturn($this->transfer)
			->once()
			->ordered();

		$this->transfer->shouldReceive('first')
			->andReturn($this->transfer)
			->once()
			->ordered();

		$result = $this->transfers->findByPhoneAndCode($phone, $code);

		$this->assertInstanceOf(Transfer::class, $result);
	}

} 