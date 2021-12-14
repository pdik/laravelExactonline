<?php

namespace Modules\ExactOnline\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\ExactOnline\Entities\Exact;
use Modules\ExactOnline\Entities\ExactSalesInvoices;

class UpdateExactInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uniqueFor = 120;
    protected $id;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $salesinvoice = Exact::getSalesInvoice($this->id);
            ExactSalesInvoices::ExactUpdate($salesinvoice);
        } catch (\Exception $e) {
            throw new \Exception('Exact Update Invoice:'.$e->getMessage());
        }

    }

}
