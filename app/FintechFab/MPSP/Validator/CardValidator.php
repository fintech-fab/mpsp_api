<?php namespace FintechFab\MPSP\Validator;

class CardValidator extends Validator
{

	/**
	 * @return array
	 */
	public function rules()
	{
		return [
			'number'       => [
				'required',
				'luhn',
			],
			'expire_month' => [
				'required',
				'integer',
				'max: 12',
			],
			'expire_year'  => [
				'required',
				'integer',
			],
			'cvv'          => [
				'required',
				'numeric',
				'max: 999',
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