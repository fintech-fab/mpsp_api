<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class TransferMemberRel extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('transfer_member_rel', function (Blueprint $table) {
			$table->increments('id');

			$table->integer('transfer_id')
				->default(0)
				->unsigned();

			$table->integer('member_id')
				->default(0)
				->unsigned();

			$table->tinyInteger('type')
				->default(0)
				->unsigned();

			$table->timestamp('created_at');

			$table->unique(array('transfer_id', 'member_id', 'type'));
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('transfer_member_rel');
	}

}