<?php namespace FintechFab\MPSP\Services;

/**
 * Class NumberGenerator
 * Генератор случайных чисел
 *
 */
class Code
{
	/**
	 * Длина кода по-умолчанию
	 *
	 * @var int
	 */
	public $length = 5;

	/**
	 * Если установлена, то метод generate будет возвращать эту строку
	 *
	 * @var string
	 */
	public $prefetch = null;

	/**
	 * Минимальная длина кода. который будет сгенерирован
	 *
	 * @var int
	 */
	public $minLength = 4;

	/**
	 * Сгенирировать случайное число.
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	public function generateNumber($length = null)
	{
		if ($this->prefetch) {
			return $this->prefetch;
		}

		if (is_null($length)) {
			$length = $this->length;
		}

		if ($length < $this->minLength) {
			$length = $this->minLength;
		}

		$start = (int)str_repeat('1', $length);
		$end = (int)str_repeat('9', $length);

		$code = mt_rand($start, $end);

		return (string)$code;
	}

	/**
	 * Генерирует пароль длинной $iLength символов
	 *
	 * Пароль обязательно содержит одну-три цифры и одну-две буквы в верхнем регистре (для пароля в восемь символов)
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	public function generatePassword($length = 8)
	{
		if ($this->prefetch) {
			return $this->prefetch;
		}

		$length = max($length, $this->minLength);

		$decimal = '3456789';
		$upper = 'ABCDEFGHJKLMNPQRTVWXY';

		$password = "";

		$decimals = mt_rand(1, ceil(0.6 * $length)); // От одной до трёх цифр

		while (strlen($password) < $length) {

			$char = false;

			if ($decimals && $char === false) {
				$char = $decimal[mt_rand(0, strlen($decimal) - 1)];
				$decimals--;
			}

			if ($char === false) {
				$char = $upper[mt_rand(0, strlen($upper) - 1)];
			}

			$password .= $char;
		}

		return str_shuffle($password);
	}
}
