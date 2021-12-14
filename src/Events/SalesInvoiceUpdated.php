<?php
namespace Pdik\LaravelExactOnline\Events;
use Illuminate\Queue\SerializesModels;
use Picqer\Financials\Exact\Account;
use Picqer\Financials\Exact\SalesInvoice;


class SalesInvoiceUpdated
{
    use SerializesModels;

    public $salesInvoice;

    public function __construct(SalesInvoice $invoice)
    {
        $this->salesInvoice = $invoice;
    }
}