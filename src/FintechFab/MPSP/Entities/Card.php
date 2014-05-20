<?php namespace FintechFab\MPSP\Entities;

use Config;
use Crypt;
use Eloquent;
use Hash;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;

/**
 * @property string $number
 * @property int    $expire_month
 * @property int    $expire_year
 * @property int    $cvv
 *
 * @property mixed  member_id
 * @property mixed  hash
 * @property mixed  card
 * @property int    id
 *
 * @method static Card find($id, $columns = array('*'))
 */
class Card extends Eloquent implements ArrayableInterface, JsonableInterface
{

	/**
	 * @var string
	 */
	protected $table = 'cards';
	/**
	 * @var array
	 */
	protected $visible = [
		'card',
		'expire_year',
		'expire_month',
		'cvv',
	];

	public function setNumberAttribute($number)
	{
		$number = preg_replace('/[^\d]+/', '', $number);
		$salt = Config::get('transfer.salt');
		$hash = Hash::make($number, ['salt' => $salt]);

		$this->attributes['hash'] = $hash;
		$this->attributes['card'] = Crypt::encrypt($number);
	}

	public function setExpireYearAttribute($expireYear)
	{
		$this->attributes['expire_year'] = Crypt::encrypt($expireYear);
	}

	public function setExpireMonthAttribute($expireMonth)
	{
		$this->attributes['expire_month'] = Crypt::encrypt($expireMonth);
	}

	public function setCvvAttribute($CVV)
	{
		$this->attributes['cvv'] = Crypt::encrypt($CVV);
	}

	public function getDecryptedAttributes()
	{
		return [
			'number'       => Crypt::decrypt($this->card),
			'expire_year'  => Crypt::decrypt($this->expire_year),
			'expire_month' => Crypt::decrypt($this->expire_month),
			'cvv'          => Crypt::decrypt($this->cvv),
		];
	}

	/**
	 * Очистить информацию о карте (номер, cvv)
	 */
	public function clean()
	{
		$this->attributes['card'] = '';
		$this->attributes['expire_year'] = '';
		$this->attributes['expire_month'] = '';
		$this->attributes['cvv'] = '';

		$this->save();
	}
}