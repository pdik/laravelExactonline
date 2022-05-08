<?php

namespace Pdik\LaravelExactOnline\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Pdik\LaravelExactOnline\Exceptions\CouldNotFindWebhookException;
use Pdik\LaravelExactOnline\Models\ExactSalesInvoices;
use Pdik\LaravelExactOnline\Services\Exact;
use Picqer\Financials\Exact\Webhook\Authenticatable;

class ExactOnlineController extends Controller
{
    public function index()
    {
        return view('exact-online::index');
    }

    public function test()
    {
        return view('exact-online::index', ['stats' => Exact::getStats()]);
    }

    public function setWebhook()
    {
        Exact::setWebhooks();
        return redirect()->route('exact.index')->withStatus(__('Exact online webhooks verbonden'));
    }
}