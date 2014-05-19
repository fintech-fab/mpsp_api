<?php

use FintechFab\MPSP\Entities\Currency;

/**
 * @property \FintechFab\MPSP\Entities\Currency $currency
 */
class TransferCurrencyTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->currency = new Currency;
	}

	public function testOffsetExists()
	{
		// существующие данные
		$currencies = ['RUR', 'USD', 'EUR', 'BYR'];

		foreach ($currencies as $currency) {
			$exists = $this->currency->offsetExists($currency);
			$this->assertTrue($exists);
		}

		// не существует
		$exists = $this->currency->offsetExists('zzz');
		$this->assertFalse($exists);
	}

	public function testOffsetGet()
	{
		// существующие данные
		$collection = [
			1 => 'RUR',
			2 => 'USD',
			3 => 'EUR',
			4 => 'BYR',
		];

		foreach ($collection as $key => $name) {
			$result = $this->currency->offsetGet($name);

			$this->assertEquals($key, $result);
		}

		// не существует
		$result = $this->currency->offsetGet('zzz');

		$this->assertFalse($result);
	}

	public function testOffsetSet()
	{
		$exception = null;

		try {
			/** @noinspection PhpDeprecationInspection */
			$this->currency->offsetSet('offset', 'value');
		} catch (Exception $e) {
			$exception = $e;
		}

		$this->assertEquals('offsetSet for ' . get_class($this->currency) . ' is not supported', $exception->getMessage());
	}

	public function testOffsetUnSet()
	{
		$exception = null;

		try {
			/** @noinspection PhpDeprecationInspection */
			$this->currency->offsetUnset('offset', 'value');
		} catch (Exception $e) {
			$exception = $e;
		}

		$this->assertEquals('offsetUnset for ' . get_class($this->currency) . ' is not supported', $exception->getMessage());
	}

	public function testToArray()
	{
		$expected = [
			1 => 'RUR',
			2 => 'USD',
			3 => 'EUR',
			4 => 'BYR',
		];

		$this->assertEquals($expected, $this->currency->toArray());
	}

	public function testToJson()
	{
		$expected = [
			1 => 'RUR',
			2 => 'USD',
			3 => 'EUR',
			4 => 'BYR',
		];

		$expected = json_encode($expected);

		$this->assertEquals($expected, $this->currency->toJson());
	}

} 