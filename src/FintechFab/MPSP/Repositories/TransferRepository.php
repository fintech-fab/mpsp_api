<?php namespace FintechFab\MPSP\Repositories;

use FintechFab\MPSP\Entities\Transfer;

class TransferRepository
{

	public function __construct(Transfer $transfer)
	{
		$this->transfer = $transfer;
	}

	/**
	 * поиск по id
	 *
	 * @param $id
	 *
	 * @return \FintechFab\MPSP\Entities\Transfer|null
	 */
	public function findById($id)
	{
		return $this->transfer->newInstance()
			->find($id);
	}

	/**
	 * Поиск по коду
	 *
	 * @param string $code
	 *
	 * @return \FintechFab\MPSP\Entities\Transfer|null
	 */
	public function findByCode($code)
	{
		return $this->transfer->newInstance()
			->where('code', $code)
			->first();
	}

	/**
	 * Поиск по номеру телефона и коду
	 *
	 * @param $phone
	 * @param $code
	 *
	 * @return Transfer|null
	 */
	public function findByPhoneAndCode($phone, $code)
	{
		return $this->transfer->newInstance()
			->select('transfers.*')
			->from('transfers')
			->join('transfer_member_rel', 'transfers.id', '=', 'transfer_member_rel.transfer_id')
			->join('members', 'members.id', '=', 'transfer_member_rel.member_id')
			->where('code', $code)
			->where('phone', $phone)
			->first();
	}

} 