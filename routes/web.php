<?php

use Illuminate\Support\Facades\Route;
use Pdik\LaravelExactOnline\Http\Controllers\ExactOnlineController;

Route::prefix('exactonline')->group(function () {
    Route::group(['middleware' => ['auth']], function () {
        Route::get('/settings',
            [ExactOnlineController::class, 'index'])->name('exact-online.index');
          Route::get('/settings/stats',
            [ExactOnlineController::class, 'test'])->name('exact-online.stats');
        Route::post('authorize', [ExactOnlineController::class, 'appAuthorize'])->name('exact-online.authorize');
        Route::post('webhook', [ExactOnlineController::class, 'setWebhook'])->name('exact-online.set-webhook');
    });
});
