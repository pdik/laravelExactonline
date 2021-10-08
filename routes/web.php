<?php

use Illuminate\Support\Facades\Route;
use Pdik\laravelExactonline\Http\Controllers\ExactOnlineController;
Route::namespace('\Pdik\laravelExactonline\Http\Controllers')->group(function () {
    Route::prefix('exactonline')->group(function () {
        Route::group(['middleware' => ['auth']], function () {
            Route::get('settings', [ExactOnlineController::class, 'index'])->name('exact.index');
            Route::post('authorize', [ExactOnlineController::class, 'appAuthorize'])->name('exact.authorize');
            Route::post('webhook', [ExactOnlineController::class, 'setWebhook'])->name('exact.webhook');
            Route::post('sync', [ExactOnlineController::class, 'sync'])->name('exact.sync');
        });
    });
});