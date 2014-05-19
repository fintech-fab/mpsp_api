<?php namespace FintechFab\MPSP\Validator;

class TransferValidator extends Validator
{

	/**
	 * @return array
	 */
	public function rules()
	{
		return [
			'currency' => [
				'required',
				'currency_exists',
			],
			'amount'   => [
				'required',
				'numeric',
				'amount:' . $this->getCurrency(),
			],
			'fee'      => [
				'required',
				'numeric',
			],
		];
	}

	private function getCurrency()
	{
		return isset($this->input['currency']) ? $this->input['currency'] : null;
	}

	/**
	 * @return array
	 */
	public function messages()
	{
		return [];
	}
}