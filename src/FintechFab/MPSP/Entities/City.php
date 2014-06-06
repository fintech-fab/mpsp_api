<?php namespace FintechFab\MPSP\Entities;

use Eloquent;

/**
 * An Eloquent Model: '\Monemobo\Transfer\City'
 *
 * @property integer        $id
 * @property integer        $country
 * @property integer        $name
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @method City create() static
 */
class City extends Eloquent
{
	protected $table = 'cities';
	protected $visible = ['id', 'country', 'name'];
	protected $guarded = [];
}