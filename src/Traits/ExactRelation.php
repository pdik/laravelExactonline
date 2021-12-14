<?php
namespace Pdik\LaravelExactOnline\Traits;
use Picqer\Financials\Exact\Account;
use Illuminate\Support\Facades\DB;
/**
 * HasExactConnection
 */
trait ExactRelation{

    /**
     * Get exact connection
     * @return mixed
     */
    public function subscriptions()
    {
        return $this->morphMany(Account::class, 'owner');
    }
        /**
     * Retrieve the Mollie customer ID for this model
     *
     * @return string
     */
    public function ExactAccountId()
    {
        if (empty($this->Exact_id)) {
            return $this->createAsExactCustomer()->id;
        }

        return $this->Exact_id;
    }
     /**
     * Create a Mollie customer for the billable model.
     *
     * @param array $override_options
     * @return Customer
     */
    public function createAsExactCustomer(array $override_options = [])
    {
        $options = array_merge($this->mollieCustomerFields(), $override_options);

        /** @var CreateMollieCustomer $createMollieCustomer */
        $createMollieCustomer = app()->make(CreateMollieCustomer::class);
        $customer = $createMollieCustomer->execute($options);

        $this->mollie_customer_id = $customer->id;
        $this->save();

        return $customer;
    }
}