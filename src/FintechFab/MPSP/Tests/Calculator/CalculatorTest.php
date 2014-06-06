<?php namespace FintechFab\MPSP\Tests\Calculator;

use DB;
use FintechFab\MPSP\Tests\TestCase;
use Illuminate\Database\Query\Builder;
use Illuminate\Queue\QueueInterface;
use Illuminate\Validation\Validator as Validation;
use FintechFab\MPSP\Calculator\Calculator;
use FintechFab\MPSP\Entities\Currency;
use FintechFab\MPSP\Exceptions\CalculatorException;
use FintechFab\MPSP\Exceptions\ValidatorException;
use Queue;
use Validator;

/**
 * @property \Mockery\MockInterface                  $transferCurrency
 * @property \Mockery\MockInterface                  $validator
 * @property \Mockery\MockInterface                  $queryBuilder
 * @property \Mockery\MockInterface                  $queue
 * @property \FintechFab\MPSP\Calculator\Calculator  $calculator
 */
class TransferCostCalculatorTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->queue = $this->mock(QueueInterface::class);
		$this->transferCurrency = $this->mock(Currency::class);
		$this->validator = $this->mock(Validation::class);
		$this->queryBuilder = $this->mock(Builder::class);

		$this->calculator = new Calculator($this->transferCurrency);
	}

	/**
	 * Считаем комиссию
	 */
	public function testDoCalculate()
	{
		$data = [
			'amount'   => 105,
			'currency' => 'RUR',
		];

		// правила валидации
		$rules = [
			'amount'   => [
				'required',
				'numeric',
				'min:1',
				'max:15000',
			],
			'currency' => [
				'required',
				'in:RUR',
			],
		];

		// получаем список валют
		$this->transferCurrency->shouldReceive('toArray')
			->andReturn([1 => 'RUR'])
			->once()
			->ordered();

		// создаем валидатор
		Validator::shouldReceive('make')
			->with($data, $rules)
			->andReturn($this->validator)
			->once()
			->ordered();

		// валидатор проходит
		$this->validator->shouldReceive('passes')
			->andReturn(true)
			->once()
			->ordered();

		// получаем код валюты
		$this->transferCurrency->shouldReceive('offsetGet')
			->with('RUR')
			->andReturn(1)
			->once()
			->ordered();

		// ищем в базе перевод
		DB::shouldReceive('table')
			->with('transfer_costs')
			->andReturn($this->queryBuilder)
			->twice();

		$this->queryBuilder->shouldReceive('where')
			->with('currency', 1)
			->andReturn($this->queryBuilder)
			->once()
			->ordered();

		$this->queryBuilder->shouldReceive('where')
			->with('sum_from', '<=', 105)
			->andReturn($this->queryBuilder)
			->once()
			->ordered();

		$this->queryBuilder->shouldReceive('where')
			->with('sum_to', '>=', 105)
			->andReturn($this->queryBuilder)
			->once()
			->ordered();

		$transferCostId = 7;

		$mockResult = (object)[
			'id'         => $transferCostId,
			'amount'     => 7.03,
			'flag_query' => 0,
			'updated_at' => date('Y-m-d H:i:s', time() - 365 * 86400), // Информация о комиссии есть, но она просрочена
		];

		$this->queryBuilder->shouldReceive('first')
			->andReturn($mockResult)
			->once()
			->ordered();

		$this->queryBuilder->shouldReceive('orderBy')
			->andReturn($this->queryBuilder)
			->once();

		$this->queryBuilder->shouldReceive('where')
			->with('id', '=', $transferCostId)
			->andReturn($this->queryBuilder)
			->once();

		$this->queryBuilder->shouldReceive('update')
			->with(['flag_query' => 1])
			->once();

		// кидаем задание в очередь на подсчет комиссии
		Queue::shouldReceive('connection')
			->withArgs(array('gateway'))
			->andReturn($this->queue)
			->once()
			->ordered();

		$this->queue->shouldReceive('push')
			->withArgs(array(
				'calculateFee',
				array(
					'cost_id'  => $transferCostId,
					'amount'   => 105,
					'currency' => 'RUR',
				)
			))
			->once()
			->ordered();

		$this->calculator->setAmount($data['amount']);
		$this->calculator->setCurrency($data['currency']);
		$result = $this->calculator->doCalculate();

		$this->assertEquals(7.03, $result);
	}

	/**
	 * Считаем комиссию, но в базе нет записи
	 */
	public function testDoCalculateNoDatabaseEntry()
	{
		$transferCostId = 7;

		// получаем список валют
		$this->transferCurrency->shouldReceive('toArray')
			->andReturn(array(1 => 'RUR'));

		// создаем валидатор
		Validator::shouldReceive('make')
			->andReturn($this->validator);

		// валидатор проходит
		$this->validator->shouldReceive('passes')
			->andReturn(true)
			->once()
			->ordered();

		// получаем код валюты
		$this->transferCurrency->shouldReceive('offsetGet');

		// ищем в базе перевод
		DB::shouldReceive('table')
			->andReturn($this->queryBuilder);

		$this->queryBuilder->shouldReceive('where')
			->andReturn($this->queryBuilder);

		$this->queryBuilder->shouldReceive('first')
			->andReturn(null);

		$this->queryBuilder->shouldReceive('orderBy')
			->andReturn($this->queryBuilder);

		$this->queryBuilder->shouldReceive('insertGetId')
			->andReturn($transferCostId);

		$dbResult = (object)['id' => $transferCostId, 'flag_query' => 1, 'amount' => 0];

		$this->queryBuilder->shouldReceive('find')
			->andReturn($dbResult);

		$this->calculator->setAmount(100);
		$this->calculator->setCurrency('RUR');

		// кидаем задание в очередь на подсчет комиссии
		Queue::shouldReceive('connection')
			->andReturn($this->queue);

		$this->queue->shouldReceive('push');

		$exception = null;
		try {
			$this->calculator->doCalculate();
			$this->fail('Ожидается исключительная ситуация');
		} catch (CalculatorException $e) {
			$exception = $e;
		}

		$this->assertNotNull($exception);
		$this->assertEquals('В таблице transfer_costs не задана комиссия для валюты  для суммы 100', $exception->getMessage());
	}

	/**
	 * Считаем комиссию, ошибка во входных данных
	 */
	public function testDoCalculateValidationError()
	{
		// получаем список валют
		$this->transferCurrency->shouldReceive('toArray')
			->andReturn(array(1 => 'RUB'));

		// создаем валидатор
		Validator::shouldReceive('make')
			->andReturn($this->validator);

		// валидатор не проходит
		$this->validator->shouldReceive('passes')
			->andReturn(false)
			->once()
			->ordered();

		$this->validator->shouldReceive('errors')
			->andReturn($this->validator)
			->once()
			->ordered();

		$this->validator->shouldReceive('getMessages')
			->andReturn($this->validator)
			->once()
			->ordered();

		$exception = null;
		try {
			$this->calculator->doCalculate();
		} catch (ValidatorException $e) {
			$exception = $e;
		}

		$this->assertNotNull($exception);
		$this->assertInstanceOf(ValidatorException::class, $exception);
	}

} 