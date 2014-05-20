<?php namespace FintechFab\MPSP\Controllers;

use Controller;
use Exception;

class BaseController extends Controller
{

	const C_CODE_SUCCESS = 0; // успех

	const C_CODE_VALIDATION_ERROR = -1; // ошибка в валидации
	const C_CODE_TRANSFER_DOES_NOT_EXIST = -2; // трансфер не существует
	const C_CODE_RETRY_THE_REQUEST_LATER = -3; // повторите запрос позже

	const C_CODE_SYSTEM_ERROR = -9999; // системная ошибка

	public static $messages = [
		self::C_CODE_SUCCESS                 => 'ok',
		self::C_CODE_VALIDATION_ERROR        => 'ошибка в валидации данных',
		self::C_CODE_TRANSFER_DOES_NOT_EXIST => 'трансфер не существует',
		self::C_CODE_SYSTEM_ERROR            => 'системная ошибка',
		self::C_CODE_RETRY_THE_REQUEST_LATER => 'повторите запрос позже',
	];

	private $currentCode = null;
	private $currentMessage = null;

	/**
	 * Создать данные для успешного результата
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function createSuccessResponseData(array $data)
	{
		$this->checkCodeExists($this->currentCode);

		return $this->getCompiledResponseData($data);
	}

	/**
	 * Проверить существует ли код ответа
	 *
	 * @param $code
	 *
	 * @throws Exception
	 */
	private function checkCodeExists($code)
	{
		if (!array_key_exists($code, self::$messages)) {
			throw new Exception('кода ответа ' . $code . ' не существует');
		}
	}

	/**
	 * Получить собранный ответ
	 *
	 * @param $response
	 *
	 * @return array
	 */
	private function getCompiledResponseData($response)
	{
		return [
			'code'     => $this->currentCode,
			'message'  => $this->currentMessage,
			'response' => $response,
		];
	}

	/**
	 * Создать данные для неудачного результата
	 *
	 * @param array $data
	 * @param null  $message
	 *
	 * @return array
	 */
	protected function createErrorResponseData(array $data = [], $message = null)
	{
		$this->checkCodeExists($this->currentCode);

		if (!is_null($message)) {
			$this->setResponseMessage($message);
		}

		return $this->getCompiledResponseData($data);
	}

	/**
	 * Задать сообщение ответа
	 *
	 * @param $message
	 */
	protected function setResponseMessage($message)
	{
		$this->currentMessage = $message;
	}

	/**
	 * Установить код ответа
	 *
	 * @param $code
	 *
	 * @return int
	 */
	protected function setResponseCode($code)
	{
		$this->checkCodeExists($code);

		$this->setResponseMessage($this->getCodeMessage($code));

		return $this->currentCode = $code;
	}

	/**
	 * Получить сообщение ответа
	 *
	 * @param $code
	 *
	 * @return string
	 */
	private function getCodeMessage($code)
	{
		return self::$messages[$code];
	}

} 