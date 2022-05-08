<?php
namespace Pdik\LaravelExactOnline\Events;
use Illuminate\Queue\SerializesModels;
use Picqer\Financials\Exact\Account;


class QuotationLinesUpdated extends BaseEvent
{
    use SerializesModels;


    public function __construct(string $key)
    {
        parent::__construct($key);
    }
}