<?php namespace FintechFab\MPSP\Queue\Jobs;

use Exception;
use Illuminate\Queue\Jobs\Job;
use Log;

abstract class AbstractJob
{
	protected $delayOnExceptionSeconds = 20;

	public function fire(Job $job, array $data)
	{
		try {
			Log::info(get_class($this) . ":run() Begin", $data);
			$this->run($data);
			Log::info(get_class($this) . ":run() end", $data);

			$job->delete();
		} catch (Exception $e) {
			Log::error($e);
			$job->release($this->delayOnExceptionSeconds);
		}
	}

	abstract protected function run($data);
}