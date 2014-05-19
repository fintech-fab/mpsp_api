<?php

use Illuminate\Validation\Validator as Validation;
use FintechFab\MPSP\Validator\SenderValidator;

/**
 * @property mixed             $validator
 * @property SenderValidator   $senderValidator
 */
class SenderValidatorTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->validator = $this->mock(Validation::class);

		$this->senderValidator = new SenderValidator($this->validator);
	}

	/**
	 * Валидация трансфера для отправки
	 *
	 * успех
	 */
	public function testDoValidate()
	{
		$input = [
			'phone' => '3213213',
		];

		$rules = [
			'phone' => [
				'required',
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

		$valid = $this->senderValidator->doValidate($input);

		$this->assertTrue($valid);
	}


} 