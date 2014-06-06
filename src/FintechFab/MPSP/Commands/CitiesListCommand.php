<?php namespace FintechFab\MPSP\Commands;

use Illuminate\Console\Command;
use Queue;

class CitiesListCommand extends Command
{

	protected $name = 'cities:list';
	protected $description = 'Получить список городов в Gateway';

	public function __construct()
	{
		parent::__construct();
	}

	public function fire()
	{
		Queue::connection('gateway')->push('citiesList', []);
	}

}

