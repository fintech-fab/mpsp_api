<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class Cards extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cards', function (Blueprint $table) {
			$table->increments('id');

			$table->integer('member_id')
				->default(0)
				->unsigned();

			$table->string('hash', 60)
				->default('');

			$table->string('card', 255);
			$table->string('expire_year', 255);
			$table->string('expire_month', 255);
			$table->string('cvv', 255);

			$table->timestamps();

			$table->index('hash');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('cards');
	}

}