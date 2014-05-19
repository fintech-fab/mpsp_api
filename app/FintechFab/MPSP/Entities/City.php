<?php namespace FintechFab\MPSP\Entities;

use Eloquent;
use FintechFab\MPSP\Entities\CityName;

/**
 * An Eloquent Model: '\Monemobo\Transfer\City'
 *
 * @property integer        $id
 * @property integer        $country_id
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @method City create() static
 */
class City extends Eloquent
{
	protected $table = 'cities';
	protected $visible = ['id', 'country_id', 'names'];

	/**
	 * @return string
	 */
	public function getRussianName()
	{
		return $this->names()->where('language', 'ru')->first()->value;
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Query\Builder
	 */
	public function names()
	{
		return $this->hasMany(CityName::class, 'city_id');
	}

}