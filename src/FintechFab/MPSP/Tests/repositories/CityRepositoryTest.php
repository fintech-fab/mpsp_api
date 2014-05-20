<?php namespace FintechFab\MPSP\Tests\Repositories;

use FintechFab\MPSP\Entities\City;
use FintechFab\MPSP\Repositories\CityRepository;
use FintechFab\MPSP\Tests\TestCase;

/**
 * @property \Mockery\MockInterface|mixed $city
 * @property \FintechFab\MPSP\Repositories\CityRepository               $cities
 */
class CityRepositoryTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->city = $this->mock(City::class);

		$this->cities = new CityRepository($this->city);
	}

	public function testFindById()
	{
		$id = 5;

		$this->city->shouldReceive('newInstance')->andReturn($this->city);
		$this->city->shouldReceive('find')->with($id)->andReturn($this->city);

		$result = $this->cities->findById($id);

		$this->assertInstanceOf(City::class, $result);
	}

} 