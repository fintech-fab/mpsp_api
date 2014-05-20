<?php namespace FintechFab\MPSP\Tests\Validator;

use FintechFab\MPSP\Tests\TestCase;
use Illuminate\Validation\Validator as Validation;
use FintechFab\MPSP\Validator\ReceiverValidator;
use Validator;

/**
 * @property mixed             $validator
 * @property ReceiverValidator $receiverValidator
 */
class ReceiverValidatorTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->validator = $this->mock(Validation::class);

		$this->receiverValidator = new ReceiverValidator($this->validator);
	}

	/**
	 * Валидация трансфера для отправки
	 *
	 * успех
	 */
	public function testDoValidate()
	{
		$input = [
			'number'       => '3213213',
			'expire_month' => 11,
			'expire_year'  => 18,
			'cvv'          => 323,
		];

		$rules = [
			'surname'   => [
				'required',
			],
			'name'      => [
				'required',
			],
			'thirdname' => [
				'required',
			],
			'city'      => [
				'required',
				'exists:cities,id',
			],
			'phone'     => [
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

		$valid = $this->receiverValidator->doValidate($input);

		$this->assertTrue($valid);
	}


} 