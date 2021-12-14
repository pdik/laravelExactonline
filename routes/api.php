<?php

use Illuminate\Http\Request;
Route::post('/exact/webhook', [\Modules\ExactOnline\Http\Controllers\ExactOnlineController::class,'handleWebhook']);