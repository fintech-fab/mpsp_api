<?php namespace FintechFab\MPSP\Entities;

use Eloquent;
use FintechFab\MPSP\Entities\Card;
use FintechFab\MPSP\Entities\Member;
use Log;
use FintechFab\MPSP\Constants\MemberType;

/**
 * An Eloquent Model: '\Monemobo\Transfer\Transfer'
 *
 * @property integer          $id
 * @property string           $code
 * @property float            $amount
 * @property float            $fee
 * @property string           $currency
 * @property integer          $transfer_card_id
 * @property string           $checknumber
 * @property string           $receiver_surname
 * @property string           $receiver_name
 * @property string           $receiver_thirdname
 * @property string           $receiver_city
 * @property string           $3ds_url
 * @property string           $3ds_post_data
 * @property boolean          $status
 * @property boolean          $decline_reason
 * @property \Carbon\Carbon   $updated_at
 * @property \Carbon\Carbon   $created_at
 * @property-read Member|null $sender
 * @property-read Member|null $receiver
 * @property Card     $card
 *
 * @method static Transfer find($id, $columns = array('*'))
 */
class Transfer extends Eloquent
{

	protected $visible = [
		'id',
		'code',
		'amount',
		'fee',
		'currency',
		'3ds_url',
		'3ds_post_data',
		'status',
		'updated_at',
		'created_at',
	];

	/**
	 * Установить статус
	 *
	 * @param int $status
	 */
	public function setStatus($status)
	{
		Log::info("Transfer::setStatus()", ['id' => (int)$this->id, 'status' => (int)$status]);
		$this->status = $status;

		$this->save();
	}

	/**
	 * @return \FintechFab\MPSP\Entities\Member|null
	 */
	public function getSenderAttribute()
	{
		return $this->getMember(MemberType::C_TYPE_SENDER);
	}

	/**
	 * @param $memberType
	 *
	 * @return Member
	 */
	private function getMember($memberType)
	{
		return $this->members()->where('transfer_member_rel.type', '=', $memberType)->first();
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\Illuminate\Database\Query\Builder
	 */
	public function members()
	{
		return $this->belongsToMany(Member::class, 'transfer_member_rel', 'transfer_id', 'member_id');
	}

	/**
	 * @return \FintechFab\MPSP\Entities\Member|null
	 */
	public function getReceiverAttribute()
	{
		return $this->getMember(MemberType::C_TYPE_RECEIVER);
	}

	public function card()
	{
		return $this->hasOne(Card::class, 'id', 'transfer_card_id');
	}

}