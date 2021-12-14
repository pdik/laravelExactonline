<?php

namespace Modules\ExactOnline\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\ExactOnline\Entities\Exact;

class CreateExactInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order, $invoiceLines;

    public $tries = 3;
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order, $invoiceLines)
    {
        $this->order = $order;
        $this->invoiceLines = $invoiceLines;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Exact::create_invoice($this->order, $this->invoiceLines);
        } catch (\Exception $e) {
            throw new \Exception('Exact Create invoice:'.$e->getMessage());
        }
    }
}
