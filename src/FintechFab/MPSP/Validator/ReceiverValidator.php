<?php namespace FintechFab\MPSP\Validator;

class ReceiverValidator extends Validator
{

	/**
	 * @return array
	 */
	public function rules()
	{
		return [
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
	}

	/**
	 * @return array
	 */
	public function messages()
	{
		return [];
	}
}