<?php

namespace Pdik\LaravelExactOnline\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Picqer\Financials\Exact\Account;

class AccountsUpdated extends BaseEvent
{
    use SerializesModels;


    public function __construct(string $key)
    {
        parent::__construct($key);
    }
}