<?php
namespace Pdik\LaravelExactOnline\Traits;
use Picqer\Financials\Exact\Account;

Trait PopulatesExactAccountFields
{
    public function ExactAccountFields()
    {
        return Account::class;
    }
}