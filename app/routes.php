<?php

/**
 * Переводы
 */
Route::group(['prefix' => 'transfer'], function () {

	// получить комиссию за перевод
	Route::post('cost', TransferController::class . '@cost');

	// создать перевод
	Route::post('create', TransferController::class . '@create');

	// отправить перевод
	Route::post('send', TransferController::class . '@send');

	// получить статус
	Route::post('status', TransferController::class . '@status');

});

/**
 * Acquiring
 */
Route::group(['prefix' => 'acquiring'], function () {

	// результат 3DS
	Route::post('finish_3ds', AcquiringController::class . '@finish_3ds');

});

Route::post('city/search', CityController::class . '@search');
