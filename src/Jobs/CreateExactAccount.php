<?php

namespace Pdik\LaravelExactOnline\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Pdik\LaravelExactOnline\Events\AccountsCreated;
use Pdik\LaravelExactOnline\Services\Exact;
use Picqer\Financials\Exact\Account;

class CreateExactAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customer;
    protected $data;

    public $tries = 3;
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
         try {
            Exact::create_account($this->data);
            Event::dispatch(new AccountsCreated($this->data));
        } catch (\Exception $e) {
            throw new \Exception('Exact Create invoice:'.$e->getMessage());
        }

    }

}
