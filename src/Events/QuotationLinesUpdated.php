<?php
namespace Pdik\LaravelExactOnline\Events;
use Illuminate\Queue\SerializesModels;
use Picqer\Financials\Exact\Account;


class QuotationLinesUpdated
{
  use SerializesModels;
}