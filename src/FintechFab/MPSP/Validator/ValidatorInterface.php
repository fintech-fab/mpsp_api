<?php namespace FintechFab\MPSP\Validator;

interface ValidatorInterface
{

	/**
	 * @return array
	 */
	public function rules();

	/**
	 * @return array
	 */
	public function messages();

} 