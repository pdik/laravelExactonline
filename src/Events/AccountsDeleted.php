<?php
namespace Pdik\LaravelExactOnline\Events;
use Illuminate\Queue\SerializesModels;
use Picqer\Financials\Exact\Account;


class AccountsDeleted
{
     use SerializesModels;

    public $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
    }
}