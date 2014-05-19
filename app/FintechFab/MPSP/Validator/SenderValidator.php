<?php namespace FintechFab\MPSP\Validator;

class SenderValidator extends Validator
{

	/**
	 * @return array
	 */
	public function rules()
	{
		return [
			'phone' => [
				'required',
			],
		];
	}

	/**
	 * @return array
	 */
	public function messages()
	{
		return [];
	}
}