<?php

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;
use Pdik\LaravelExactOnline\Http\Controllers\WebhookController;
//Api Endpoints always start with the /api and then the version e.g. /api/exactonline/v1
Route::post(config('exact.webhook_url'), [WebhookController::class,'handleWebhook'])->middleware('exact.webhook');

Route::get('callback' , [WebhookController::class,'callback']);
