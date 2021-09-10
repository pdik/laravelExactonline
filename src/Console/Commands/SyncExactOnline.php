<?php
namespace Pdik\Console\Commands;
use Illuminate\Console\Command;
use Pdik\laravelExactonline\Models\ExactSalesInvoices;
use Pdik\laravelExactonline\Models\TransactionLines;
use Pdik\laravelExactonline\Services\Exact;

class SyncExactOnline extends Command
{
 /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exact:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync exact online with mijn mobox';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * Sync exact online
     */
    public function handle()
    {
        $this->info('Sync started');
        foreach (Exact::getSalesInvoices() as $invoice){
          ExactSalesInvoices::ExactUpdate($invoice);
        }
        foreach (Exact::getSalesEntrys() as $entry){
          TransactionLines::ExactUpdate($invoice);
        }
        $this->info('Exact synced');
    }
}