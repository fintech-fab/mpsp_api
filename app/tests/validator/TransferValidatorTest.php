<?php

use Illuminate\Validation\Validator as Validation;
use FintechFab\MPSP\Exceptions\ValidatorException;
use FintechFab\MPSP\Validator\TransferValidator;

class TransferValidatorTest extends TestCase
{

	/**
	 * @var \Mockery\Expectation|\Mockery\MockInterface
	 */
	private $validator;

	/**
	 * @var TransferValidator
	 */
	private $transferValidator;

	public function setUp()
	{
		parent::setUp();

		$this->validator = $this->mock(Validation::class);

		$this->transferValidator = new TransferValidator($this->validator);
	}

	/**
	 * Валидация трансфера для отправки
	 *
	 * успех
	 */
	public function testDoValidate()
	{
		$input = [
			'currency' => 'RUR',
			'amount'   => 100,
			'fee'      => 30,
		];

		$rules = [
			'currency' => [
				'required',
				'currency_exists',
			],
			'amount'   => [
				'required',
				'numeric',
				'amount:RUR',
			],
			'fee'      => [
				'required',
				'numeric',
			],
		];

		// устанавливаем данные для валитора
		Validator::shouldReceive('make')
			->with($input, $rules, [])
			->andReturn($this->validator)
			->once()
			->ordered();

		// валидация прошла успешно
		$this->validator->shouldReceive('fails')
			->andReturn(false)
			->once()
			->ordered();

		$valid = $this->transferValidator->doValidate($input);

		$this->assertTrue($valid);
	}

	/**
	 * Валидация трансфера для отправки
	 *
	 * ошибка
	 */
	public function testFail()
	{
		$input = array(
			'phone'  => '79671234567',
			'amount' => 111.3,
		);

		// устанавливаем данные для валитора
		Validator::shouldReceive('make')
			->andReturn($this->validator);

		// валидация прошла не успешно
		$this->validator->shouldReceive('fails')
			->andReturn(true)
			->once()
			->ordered();

		$this->validator->shouldReceive('errors')
			->andReturn($this->validator)
			->once()
			->ordered();

		$this->validator->shouldReceive('getMessages')
			->andReturn(['errors_array'])
			->once()
			->ordered();

		$exception = null;
		try {
			$this->transferValidator->doValidate($input);
		} catch (ValidatorException $e) {
			$exception = $e;

			$this->assertEquals(['errors_array'], $e->getErrors());
		}

		$this->assertInstanceOf(ValidatorException::class, $exception);
	}


} 