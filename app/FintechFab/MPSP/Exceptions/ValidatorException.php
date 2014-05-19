<?php namespace FintechFab\MPSP\Exceptions;

use Exception;

class ValidatorException extends Exception
{

	private $errors;

	/**
	 * Получить ошибки валидации
	 *
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Задать ошибки валидации
	 *
	 * @param $errors
	 */
	public function setErrors($errors)
	{
		$this->errors = $errors;
	}

} 