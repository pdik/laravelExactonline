<?php

namespace Pdik\LaravelExactOnline\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\SerializesModels;
use Pdik\LaravelExactOnline\Exceptions\CouldNotConnectException;
use Pdik\LaravelExactOnline\Services\Exact;
use Picqer\Financials\Exact\Account;

class CreateExactAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;

    public $tries = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
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
     * @return Account
     * @throws \Exception
     */
    public function handle()
    {
        try {
            $connection = Exact::connect();
            $account = new Account($connection, $this->data);
            return $account;
        } catch (CouldNotConnectException $e) {
            throw new \Exception('Exact online :'.$e->getMessage());
        }

    }

}
