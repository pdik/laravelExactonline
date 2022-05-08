<?php

use Illuminate\Support\Facades\Route;
use Pdik\LaravelExactOnline\Http\Controllers\ExactOnlineController;

Route::prefix('exact-online')->group(function () {
    Route::group(['middleware' => ['auth']], function () {
        Route::post('authorize', [ExactOnlineController::class, 'appAuthorize'])->name('exact-online.authorize');
        Route::post('webhook', [ExactOnlineController::class, 'setWebhook'])->name('exact-online.set-webhook');
    });
});
