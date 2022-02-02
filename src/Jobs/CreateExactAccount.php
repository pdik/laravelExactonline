<?php

namespace Pdik\LaravelExactOnline\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\SerializesModels;
use Pdik\LaravelExactOnline\Services\Exact;
use Picqer\Financials\Exact\Account;

class CreateExactAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customer;
    protected $data;

    public $tries = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Customer $customer, $data)
    {
        $this->customer = $customer;
        $this->data = $data;
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
     */
    public function handle()
    {
        try {
            Exact::create_account($this->data, $this->customer);
        } catch (\Exception $e) {
            throw new \Exception('Exact Create invoice:'.$e->getMessage());
        }

    }

}
