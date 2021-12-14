<?php

use Illuminate\Support\Facades\Route;
use Pdik\LaravelExactOnline\Http\Controllers\ExactOnlineController;

Route::prefix('exactonline')->group(function() {
      Route::group(['middleware' => ['auth']], function () {
          Route::get('/settings', [\Modules\ExactOnline\Http\Controllers\ExactOnlineController::class, 'index'])->name('exact.index');
          Route::post('authorize', [\Modules\ExactOnline\Http\Controllers\ExactOnlineController::class, 'appAuthorize'])->name('exact.authorize');
          Route::post('webhook', [\Modules\ExactOnline\Http\Controllers\ExactOnlineController::class, 'setWebhook'])->name('exact.webhook');
          Route::post('sync', [\Modules\ExactOnline\Http\Controllers\ExactOnlineController::class, 'sync'])->name('exact.sync');
      });
      Route::get('oauth',[\Modules\ExactOnline\Http\Controllers\ExactOnlineController::class, 'appCallback'])->name('exact.callback');
      Route::get('/api/callback', function (Request $request){
           Settings::setValue('EXACT_AUTHORIZATION_CODE', $request->get('code'));
           Settings::setValue('EXACT_ACCESS_TOKEN', "");
           Settings::setValue('EXACT_REFRESH_TOKEN', "");
           Settings::setValue('EXACT_EXPIRES_IN', "");
           \Modules\ExactOnline\Entities\Exact::connect();
           return redirect()->route('exact.index')->withStatus(__('Exact is succesvol verbonden'));
      });

});
