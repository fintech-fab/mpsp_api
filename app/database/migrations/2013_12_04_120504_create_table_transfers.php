<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTableTransfers extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('transfers', function (Blueprint $table) {
			$table->increments('id');

			$table->string('code', 32);

			$table->decimal('amount')
				->default(0)
				->unsigned();

			$table->decimal('fee')
				->default(0)
				->unsigned();

			$table->string('currency', 3);

			$table->integer('transfer_card_id')
				->default(0)
				->unsigned();

			$table->string('checknumber')->nullable();

			$table->string('receiver_surname');
			$table->string('receiver_name');
			$table->string('receiver_thirdname');
			$table->string('receiver_city');
			$table->string('rrn')->nullable();
			$table->string('irn')->nullable();

			$table->string('3ds_url')->nullable();
			$table->text('3ds_post_data')->nullable();

			$table->tinyInteger('status')
				->default(0)
				->unsigned();

			$table->tinyInteger('decline_reason')
				->default(0)
				->unsigned();

			$table->timestamps();

			$table->index('code');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('transfers');
	}

}
