<?php

namespace Pdik\LaravelExactOnline\Events;

use Illuminate\Queue\SerializesModels;
use Picqer\Financials\Exact\Account;
use Picqer\Financials\Exact\SalesInvoice;


class BaseEvent
{
    use SerializesModels;

    public $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }
}