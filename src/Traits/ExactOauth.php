<?php
namespace Pdik\LaravelExactOnline\Traits;
use Picqer\Financials\Exact\Account;

Trait ExactOauth
{
    public function exactCustomerFields()
    {
        return
        [
          'exact_accesToken' =>  $this->exact_accesToken,
          'client_id' => $this->exact_client_id,
            'client_id' => $this->exact_client_id,
            'client_id' => $this->exact_client_id,
        ];
    }

}