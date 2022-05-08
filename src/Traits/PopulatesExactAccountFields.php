<?php
namespace Pdik\LaravelExactOnline\Traits;
use Picqer\Financials\Exact\Account;

Trait PopulatesExactAccountFields
{
    public function exactCustomerFields()
    {
        return
        [
          'exact_id' =>  $this->exact_id,
          'name' => $this->name,
        ];
    }

}