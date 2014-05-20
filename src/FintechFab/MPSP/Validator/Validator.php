<?php namespace FintechFab\MPSP\Validator;

use FintechFab\MPSP\Exceptions\ValidatorException;
use Validator as V;

abstract class Validator implements ValidatorInterface
{

	protected $input;

	/**
	 * @param array $input
	 *
	 * @return bool
	 * @throws ValidatorException
	 */
	public function doValidate(array $input = [])
	{
		$this->input = $input;

		$validator = V::make($input, $this->rules(), $this->messages());

		if ($validator->fails()) {
			$exception = new ValidatorException;

			$exception->setErrors($validator->errors()->getMessages());

			throw $exception;
		}

		return true;
	}

} 