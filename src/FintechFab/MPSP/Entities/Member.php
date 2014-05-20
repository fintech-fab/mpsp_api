<?php namespace FintechFab\MPSP\Entities;

use Eloquent;

/**
 * Class Member
 *
 * @property string phone
 * @property int    id
 *
 * @package Monemobo\Transfer
 */
class Member extends Eloquent
{

	protected $table = 'members';

	protected $visible = [
		'id',
		'phone',
		'dt_update',
		'dt_add',
	];

	protected $fillable = [
		'phone',
	];

}