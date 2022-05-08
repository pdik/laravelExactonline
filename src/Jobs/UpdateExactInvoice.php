<?php

namespace Pdik\LaravelExactOnline\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\SerializesModels;
use Picqer\Financials\Exact\SalesInvoice;

class UpdateExactInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    protected SalesInvoice $invoice;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SalesInvoice $invoice)
    {
        $this->invoice = $invoice;

    }

    public function middleware()
    {
        return [
            // If the job fails two times in five minutes, wait five minutes before retrying
            // If the job fails before the threshold has been reached, wait 0 to 5 minutes before retrying
            (new ThrottlesExceptions(2, 5))->backoff(rand(0, 5))
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        try {
            $this->invoice->save();
        } catch (\Exception $e) {
            throw new \Exception('Exact Update Invoice:'.$e->getMessage());
        }
    }
}
