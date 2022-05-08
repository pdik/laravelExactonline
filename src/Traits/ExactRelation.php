<?php
namespace Pdik\LaravelExactOnline\Traits;
use Picqer\Financials\Exact\Account;
use Illuminate\Support\Facades\DB;
/**
 * HasExactConnection
 */
trait ExactRelation{

    /**
     * Get exact Subscription
     * @return mixed
     */
    public function subscriptions()
    {
        return $this->morphMany(Account::class, 'owner');
    }
    /**
     * Retrieve the Exact customer ID for this model
     *
     * @return string
     */
    public function ExactAccountId()
    {
        if (empty($this->exact_customer_id)) {
            return $this->createAsExactCustomer()->id;
        }

        return $this->exact_customer_id;
    }
    /**
     * Create a Exact customer for the billable model.
     *
     * @param array $override_options
     * @return Customer
     */
    public function createAsExactCustomer(array $override_options = [])
    {
        $options = array_merge($this->exactCustomerFields(), $override_options);

        /** @var CreateExactCustomer $createExactCustomer */
        $createExactCustomer = app()->make(CreateMollieCustomer::class);
        $customer = $createExactCustomer->execute($options);
        $this->exact_customer_id = $customer->ID;
        $this->save();
        return $customer;
    }
}