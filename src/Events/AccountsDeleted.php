<?php
namespace Pdik\laravelExactonline\Events;
use Illuminate\Queue\SerializesModels;
use Picqer\Financials\Exact\Account;


class AccountsDeleted
{
    use Dispatchable, SerializesModels;

    public $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
    }
}