<?php namespace FintechFab\MPSP\Tests;

use FintechFab\MPSP\Calculator\Calculator;
use FintechFab\MPSP\Entities\City;
use FintechFab\MPSP\Entities\Sender;
use FintechFab\MPSP\Services\Code;
use FintechFab\MPSP\Services\TransferFactory;
use FintechFab\MPSP\Validator\CardValidator;
use FintechFab\MPSP\Validator\ReceiverValidator;
use FintechFab\MPSP\Validator\SenderValidator;
use FintechFab\MPSP\Validator\TransferValidator;
use Mockery as m;
use Queue;

/**
 * @method void setExpectedException($sExceptionName, $sExceptionMessage = '', $iExceptionCode = null)
 * @method \Illuminate\Http\Response call($method, $uri, array $parameters = array(), array $files = array(), array $server = array(), $content = null, $changeHistory = true)
 */
class TestCase extends \Illuminate\Foundation\Testing\TestCase
{

	public function setUp()
	{
		parent::setUp();

		Queue::shouldReceive();
	}

	public function tearDown()
	{
		m::close();

		parent::tearDown();
	}

	/**
	 * Creates the application.
	 *
	 * @return \Symfony\Component\HttpKernel\HttpKernelInterface
	 */
	public function createApplication()
	{
		$unitTesting = true;

		$testEnvironment = 'testing';

		return require 'bootstrap/start.php';
	}

	/**
	 * @param $class_name
	 *
	 * @return \Mockery\MockInterface|\Mockery\Expectation|\FintechFab\MPSP\Entities\Transfer|TransferFactory|TransferValidator|\FintechFab\MPSP\Entities\Card|Calculator|\FintechFab\MPSP\Entities\Currency|\Illuminate\Validation\Validator|\Carbon\Carbon|\FintechFab\MPSP\Repositories\TransferRepository|\FintechFab\MPSP\Entities\Receiver|\FintechFab\MPSP\Services\TransferStatusSwitcher|\FintechFab\MPSP\Entities\Member|ReceiverValidator|CardValidator|SenderValidator|Code|City|Sender
	 */
	protected function mock($class_name)
	{
		$mock = m::mock($class_name);

		$this->app->instance($class_name, $mock);

		return $mock;
	}

}
