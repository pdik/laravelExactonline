<?php
namespace Pdik\laravelExactonline\Events;
use Illuminate\Queue\SerializesModels;
use Picqer\Financials\Exact\Account;


class JournalStatusListDeleted
{
  use SerializesModels;
}