<?php namespace FintechFab\MPSP\Tests\Entities;

use Crypt;
use FintechFab\MPSP\Entities\Card;
use FintechFab\MPSP\Tests\TestCase;
use Hash;

/**
 * @property Card $card
 */
class TransferCardTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->card = new Card;
	}

	public function testSetNumber()
	{
		Crypt::shouldReceive('encrypt')
			->with(1234567)
			->andReturn('Encrypted card')
			->once();

		$this->card->number = 1234567;

		$this->assertEquals('Encrypted card', $this->card->card);
	}

	public function testSetHash()
	{
		Hash::shouldReceive('make')
			->with(1234567, ['salt' => ''])
			->andReturn('hashed')
			->once();

		$this->card->number = 1234567;

		$this->assertEquals('hashed', $this->card->hash);
	}

	public function testToArray()
	{
		Crypt::shouldReceive('encrypt')
			->andReturn('R1', 'R2', 'R3', 'R4')
			->atLeast()->times(4);

		$this->card->number = 1234567;
		$this->card->expire_year = 2014;
		$this->card->expire_month = 12;
		$this->card->cvv = 727;

		$this->card->id = 7;
		$this->card->member_id = 212;

		$export = $this->card->toArray();
		$expected = ['card' => 'R1', 'expire_year' => 'R2', 'expire_month' => 'R3', 'cvv' => 'R4'];
		$this->assertEquals($expected, $export);
	}
}