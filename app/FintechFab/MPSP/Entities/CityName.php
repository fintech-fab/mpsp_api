<?php namespace FintechFab\MPSP\Entities;

use Eloquent;

class CityName extends Eloquent
{

	protected $table = 'city_names';
	protected $visible = ['language', 'value'];

} 