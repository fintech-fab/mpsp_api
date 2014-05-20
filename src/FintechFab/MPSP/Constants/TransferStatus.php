<?php namespace FintechFab\MPSP\Constants;

/**
 * Class TransferStatus
 *
 * Статусы перевода денег.
 *
 * @package Monemobo\Transfer\Status
 */
class TransferStatus
{
	/**
	 * Перевод удалён / не найден
	 */
	const C_DELETE = 0;

	/**
	 * Перевод создан
	 */
	const C_NEW = 1;

	/**
	 * Перевод отправлен
	 */
	const C_SEND = 2;

	/**
	 * Перевод получен адресатом
	 */
	const C_PAID = 3;
}
