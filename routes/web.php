<?php

use Illuminate\Support\Facades\Route;
use Pdik\LaravelExactOnline\Http\Controllers\ExactOnlineController;
Route::prefix('exactonline')->group(function () {
    Route::group(['middleware' => ['auth']], function () {
        Route::post('authorize', [ExactOnlineController::class, 'appAuthorize'])->name('exact.authorize');
        Route::post('webhook', [ExactOnlineController::class, 'setWebhook'])->name('exact.webhook');
    });
    Route::get('oauth', [ExactOnlineController::class, 'appCallback'])->name('exact.callback');
});
