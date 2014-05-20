<?php namespace FintechFab\MPSP\Commands\Support;

use FintechFab\MPSP\Commands\TransferCreateCommand;
use FintechFab\MPSP\Commands\TransferFindCommand;
use Illuminate\Support\ServiceProvider;

class CommandsServiceProvider extends ServiceProvider
{

	public function register()
	{
		$commands = [
			TransferCreateCommand::class,
			TransferFindCommand::class,
		];

		$this->commands($commands);
	}

}