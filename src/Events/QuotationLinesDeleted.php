<?php
namespace Pdik\src\Events;
use Illuminate\Queue\SerializesModels;
use Picqer\Financials\Exact\Account;


class QuotationLinesDeleted
{
  use SerializesModels;
}