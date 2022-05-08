<?php
namespace Pdik\LaravelExactOnline\Traits;
use Picqer\Financials\Exact\Account;

Trait PopulatesExactAccountFields
{
    public function exactCustomerFields()
    {
        return Account::class;
    }
}