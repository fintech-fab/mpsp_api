<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTableTransferCosts extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('transfer_costs', function (Blueprint $table) {
			$table->increments('id');

			$table->integer('city_id')->unsigned();
			$table->tinyInteger('currency')->unsigned();
			$table->decimal('sum_from')->unsigned();
			$table->decimal('sum_to')->unsigned();
			$table->decimal('amount')->unsigned()->default(0);

			$table->tinyInteger('flag_query')->unsigned();

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('transfer_costs');
	}

}
