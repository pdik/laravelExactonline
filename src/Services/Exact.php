<?php

namespace Pdik\LaravelExactOnline\Services;

use Carbon\Carbon;
use DateTime;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pdik\LaravelExactOnline\Events\AccountsDeleted;
use Pdik\LaravelExactOnline\Events\AccountsUpdated;
use Pdik\LaravelExactOnline\Events\BankAccountsDeleted;
use Pdik\LaravelExactOnline\Events\BankAccountsUpdated;
use Pdik\LaravelExactOnline\Events\ContactsDeleted;
use Pdik\LaravelExactOnline\Events\ContactsUpdated;
use Pdik\LaravelExactOnline\Events\DocumentAttachmentsDeleted;
use Pdik\LaravelExactOnline\Events\DocumentAttachmentsUpdated;
use Pdik\LaravelExactOnline\Events\GLAccountsDeleted;
use Pdik\LaravelExactOnline\Events\GLAccountsUpdated;
use Pdik\LaravelExactOnline\Events\HostingOpportunitiesDeleted;
use Pdik\LaravelExactOnline\Events\HostingOpportunitiesUpdated;
use Pdik\LaravelExactOnline\Events\JournalStatusListDeleted;
use Pdik\LaravelExactOnline\Events\JournalStatusListUpdated;
use Pdik\LaravelExactOnline\Events\OpportunitiesDeleted;
use Pdik\LaravelExactOnline\Events\OpportunitiesUpdated;
use Pdik\LaravelExactOnline\Events\QuotationLinesDeleted;
use Pdik\LaravelExactOnline\Events\QuotationLinesUpdated;
use Pdik\LaravelExactOnline\Events\QuotationsDeleted;
use Pdik\LaravelExactOnline\Events\QuotationsUpdated;
use Pdik\LaravelExactOnline\Events\SalesInvoiceDeleted;
use Pdik\LaravelExactOnline\Events\SalesInvoiceUpdated;
use Pdik\LaravelExactOnline\Exceptions\CouldNotConnectException;
use Pdik\LaravelExactOnline\Exceptions\CouldNotFindWebhookException;
use Pdik\LaravelExactOnline\Models\ExactSalesInvoices;
use Pdik\LaravelExactOnline\Models\ExactSettings;
use Picqer\Financials\Exact\Account;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\Document;
use Picqer\Financials\Exact\DocumentAttachment;
use Picqer\Financials\Exact\SalesInvoice;
use Picqer\Financials\Exact\WebhookSubscription;

class Exact
{
    use HasFactory;


    /** @var string */
    private static $lockKey = 'exactonline.refreshLock';

    /**
     * @var int $lockTimeout
     * @comment The time (in seconds) the exact-lock will be locked before opening automatically
     */
    private static $lockTimeout = 300;

    /**
     * @var int $threadTimeout
     * @comment The time (in seconds) a thread will wait for it's turn to do oauth before failing
     */
    private static $threadTimeout = 3000;


    /**
     * @throws Exception
     */
    public static function connect(): Connection
    {
        $connection = new Connection();

        $connection->setRedirectUrl(config('exactonline.RedirectUrl')); // Same as entered online in the App Center
        $connection->setExactClientId(config('exactonline.ExactClientId'));
        $connection->setExactClientSecret(config('exactonline.ExactClientSecret'));

        self::setKeys($connection);
        // Set callback to save newly generated tokens
        $connection->setTokenUpdateCallback([self::class, 'tokenUpdateCallback']);

        // Set callbacks for locking/unlocking the token callback. This prevents multiple simultaneous requests
        // from messing up the stored tokens.
        $connection->setAcquireAccessTokenLockCallback([self::class, 'acquireLock']);
        $connection->setAcquireAccessTokenUnlockCallback([self::class, 'releaseLock']);

        //RefreshAccesTokensCallback
        $connection->setRefreshAccessTokenCallback([self::class, 'refreshAccessTokenCallback']);


        // Make the client connect and exchange tokens
        try {
            if ($connection->needsAuthentication()) {
                // Don't continue, because it will redirect every request to the Exact ouath page (and fail there)
                Logger()->error('Exact - ('.request()->fullUrl().') Could not connect: Authentication (/authenticate-exact) needed.');
                return $connection;
            }
            // If access token is not set or token has expired, acquire new token
            if (empty($connection->getAccessToken()) || ($connection->getTokenExpires() - 10) < time()) {
                Logger()->warning('Exact - ('.request()->fullUrl().') Attempt to do oauth.');
            }

            $connection->connect();
        } catch (Exception $e) {
            report($e);
            throw new Exception('Could not connect to Exact: '.$e->getMessage());
        }

        return $connection;
    }

    public static function setKeys(Connection $connection)
    {
        if (Settings::getValue('EXACT_AUTHORIZATION_CODE')) {
            $connection->setAuthorizationCode(Settings::getValue('EXACT_AUTHORIZATION_CODE'));
        }

        if (Settings::getValue('EXACT_REFRESH_TOKEN')) {
            $connection->setRefreshToken(Settings::getValue('EXACT_REFRESH_TOKEN'));
        }
        if (Settings::getValue('EXACT_ACCESS_TOKEN')) {
            $connection->setAccessToken(Settings::getValue('EXACT_ACCESS_TOKEN'));
        }
        if (Settings::getValue('EXACT_EXPIRES_IN')) {
            $connection->setTokenExpires(Settings::getValue('EXACT_EXPIRES_IN'));
        }
    }

    public static function refreshAccessTokenCallback(Connection $connection)
    {
        self::setKeys($connection);
    }

    /**
     * Handle the exact callback with new tokens, save them in the settings table
     *
     * @param  Connection  $connection
     *
     * @return void
     * @author Pascal Lieverse <P.Lieverse@brightness-group.com>
     *
     * @since 04/06/2021
     */
    public static function tokenUpdateCallback(Connection $connection)
    {
        Settings::setValue('EXACT_ACCESS_TOKEN', $connection->getAccessToken());
        Settings::setValue('EXACT_REFRESH_TOKEN', $connection->getRefreshToken());
        Settings::setValue('EXACT_EXPIRES_IN', $connection->getTokenExpires() - 60);
    }

    /**
     * Acquire refresh lock to avoid duplicate calls to exact.
     */
    public static function acquireLock()
    {
        $lock = Cache::lock(self::$lockKey, self::$lockTimeout);
        // If another thread is currently doing a token request
        if ($lock->get() === false) {
            Logger()->warning('Exact - ('.request()->fullUrl().') exact oauth call is locked. Waiting...');
            $startTime = now();

            // Wait for the other thread to unlock the exact-lock
            do {
                // Wait 100ms before testing the lock again
                sleep(0.1);

                // If the wait timeout was exceeded
                if ($startTime->diffInSeconds(now()) > self::$threadTimeout) {
                    // Fail this thread/request
                    throw new \Exception('Exact - ('.request()->fullUrl().') lock time exceeded');
                }
            } while ($lock->get() === false);
        } else {
            Logger()->warning('Exact - ('.request()->fullUrl().') locking exact oauth call.');
        }

        Logger()->warning('Exact - ('.request()->fullUrl().') passed lock check on exact oauth call.');
    }

    /**
     * Release lock that was set.
     */
    public static function releaseLock()
    {
        Logger()->warning('Exact - ('.request()->fullUrl().') releasing lock on exact oauth call');
        // Unlock the exact-lock
        Cache::lock(self::$lockKey)->forceRelease();
    }

    /**
     * Get the login url for exact to make a connection
     *
     * @return string url
     * @author Pascal Lieverse <P.Lieverse@brightness-group.com>
     *
     * @since 04/06/2021
     */
    public static function getLoginUrl()
    {
        $connection = new Connection();
        $connection->setRedirectUrl(config('exact.RedirectUrl')); // Same as entered in the App Center
        $connection->setExactClientId(config('exact.ExactClientId'));
        $connection->setExactClientSecret(config('exact.ExactClientSecret'));
        return $connection->getAuthUrl();
    }

    /**
     * Get the api status (usages, limits) to show that the connection is active.
     *
     * @param $connection
     * @return array
     * @since 04/06/2021
     * @author Pascal Lieverse <P.Lieverse@brightness-group.com>
     *
     */
    public static function getStats()
    {

        try {
            $connection = self::connect();
            $account = new \Picqer\Financials\Exact\Me($connection);
            $result = $account->get();
//          Just get a random thing to receive the limit headers

            $titles = new \Picqer\Financials\Exact\ReceivablesList($connection);
            $titles->get(['$top' => 1]);
            return [
                'UserName' => $result[0]->UserName,
                'dailyLimit' => $connection->getDailyLimit(),
                'dailyLimitRemaining' => $connection->getDailyLimitRemaining(),
                'minutelyLimit' => $connection->getMinutelyLimit(),
                'minutelyLimitRemaining' => $connection->getMinutelyLimitRemaining()
            ];
        } catch (Exception $e) {
            report($e);
            \Log::error($e->getMessage());
            return [
                'UserName' => '',
                'dailyLimit' => 0,
                'dailyLimitRemaining' => 0,
                'minutelyLimit' => 0,
                'minutelyLimitRemaining' => 0
            ];
        }
    }

//    public static function create_account($request, $customer = null, Connection $connection = null)
//    {
//        //When doining multiple things in the at the same time make sure to use the same connection
//        $con = null;
//        if (isset($connection)) {
//            $con = $connection;
//        } else {
//            $con = self::connect();
//        }
//        $account = new Account($con);
//        $account->Name = $request['first_name'].' '.$request['last_name'];
//        $account->Country = 'NL';
//        $account->IsSales = 'true';
//
//        if (isset($customer->debtornumber)) {
//            $account->Code = $customer->debtornumber;
//        } else {
//            $account->Code = Customer::max('debtornumber') + 1;
//        }
//        if (isset($customer)) {
//            $account->Email = $customer->Detials->where('type', 'Email')->first()->data;
//            $account->Phone = $customer->Detials->where('type', 'phone')->first()->data;
//        }
//        $account->Status = 'C';
//        if ($request['invoice_same_delivery'] == "1") {
//            $account->AddressLine1 = $request['adres'];
//            $account->City = $request['placename'];
//            $account->Postcode = $request['postalcode'];
//        } else {
//            $account->AddressLine1 = $customer->invoice_adres;
//            $account->City = $customer->invoice_placename;
//            $account->Postcode = $customer->invoice_postalcode;
//
//        }
//        try {
//            $account->save();
//            if (isset($customer)) {
//                $customer->Exact_ID = $account->ID;
//                $customer->save();
//            }
//            return $account;
//        } catch (Exception $e) {
//            $customer->delete();
//            return redirect(route('exact.authorize'))->withErrors('Exactonine relatie kon niet aangemaakt worden',);
//        }
//    }

    public static function create_subscription($to_id, $request, $lines)
    {
        $sub = new Subscription(self::connect());

        $sub->Created = now(); //Get current date time
        $sub->InvoiceTo = $to_id; //Subscription to
        $sub->StartDate = find_closest($request['start_rental'], date('Y-m-d'));
        $sub->InvoicingStartDate = find_closest($request['start_rental'], date('Y-m-d'));
        // $sub->StartDate =$request['start_rental'];
        // $sub->InvoicingStartDate =$request['start_rental'];
        $sub->SubscriptionType = $request['subscription_type']; //Guid ID of Sub type
        $sub->OrderedBy = $to_id;
        $sub->SubscriptionLines = $lines;
        try {
            $sub->save();
        } catch (\Exception $e) {
            report($e);
            throw new Exception('mm'.$e->getMessage());
        }
        return $sub;
    }

    public static function getSubscriptionLines($ID)
    {
        $sublines = new SubscriptionLine(self::connect());
        return $sublines->filter("EntryID eq Guid'$ID'");

    }

    public static function createSubscriptionLine($line, $sub)
    {
        // dd($line);
        $sub_lines = new SubscriptionLine(self::connect());
        $sub_lines->EntryID = $sub->EntryID;
        $sub_lines->Item = $line['Item'];
        $sub_lines->Discount = $line['Discount'];
        $sub_lines->FromDate = $line['FromDate'];
        $sub_lines->ToDate = $line['ToDate'];
        $sub_lines->save();
    }

    /**
     * @param $id
     * @param $line
     * @param $account
     * @return SubscriptionLine
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function updateSubScriptionLine($id, $line, $account)
    {
        //First check if exist
        $sublines = new SubscriptionLine(self::connect());
        $salesInvoice = new SalesInvoice(self::connect());
        $sublines->ID = $id;
        $sublines->Item = $line['Item'];
        $sublines->Discount = $line['Discount'];
        $invoices = $salesInvoice->filter("InvoiceTo eq guid'$account'",);
        if (is_array($invoices) == false) {
            $sublines->FromDate = $line['FromDate'];
        }
        $sublines->ToDate = $line['ToDate'];
        try {
            $sublines->save();
        } catch (ModelNotFoundException $exception) {
            report($exception);
            return back()->withError($exception->getMessage())->withInput();
        }

        return $sublines;
    }

    /**
     * Get all Openpayments
     * @param $connection
     * @return array
     */
    public static function getReceivablesList($connection)
    {
        $receiveable = new \Picqer\Financials\Exact\ReceivablesList($connection);
        return $receiveable->filter('', '', '', ['$top' => 1]);
    }

    /**
     * @param $key
     * @return \Picqer\Financials\Exact\SalesInvoice
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws Exception
     */
    public static function getSalesInvoice($key): SalesInvoice
    {
        $salesInvoices = new \Picqer\Financials\Exact\SalesInvoice(self::connect());
        return $salesInvoices->filter("InvoiceID eq guid'{$key}'")[0];
    }

    /**
     * Download Exact salesinvoice document
     * @param $salesInvoiceID
     * @param  null  $connection
     * @return mixed|void
     */
    public static function downloadDocument($salesInvoiceID, $connection = null)
    {
        $con = null;
        if (isset($connection)) {
            $con = $connection;
        } else {
            $con = self::connect();
        }
        $document = new Document($con);
        //Get first document
        $document = $document->filter("FinancialTransactionEntryID eq guid'{$salesInvoiceID}'")[0];
        $documentAttachment = new DocumentAttachment($con);

        $attachments = $documentAttachment->filter("Document eq guid'".$document->ID."'");
        foreach ($attachments as $invoice_attachment) {
            if (Str::contains($invoice_attachment->FileName, 'PDF')) {

                return $invoice_attachment;
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function getSalesEntrys()
    {
        $salesEntry = new \Picqer\Financials\Exact\SalesEntry(self::connect());
        return $salesEntry->filter('', '', '', ['$top' => 1]);
    }

    /**
     * @param $key
     * @return \Picqer\Financials\Exact\SalesInvoice
     * @throws Exception
     */
    public static function getSalesInvoiceByNumber($key): SalesInvoice
    {
        $salesInvoices = new \Picqer\Financials\Exact\SalesInvoice(self::connect());
        return $salesInvoices->filter("InvoiceNumber eq int'{$key}'")[0];
    }

    /**
     * @param $key
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function getTransaction($key)
    {
        $transactionline = new \Picqer\Financials\Exact\TransactionLine(self::connect());
        try {
            return $transactionline->filter("ID eq guid'{$key}'", '', '', ['$top' => 1]);
        } catch (\Exception $e) {
            report($e);
            throw new Exception("Exact error: ".$e->getMessage());
        }

    }

    /**
     * @param $key
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function getTransactions()
    {
        $transactionlines = new \Picqer\Financials\Exact\TransactionLine(self::connect());
        return $transactionlines->filter('', '', '', ['$top' => 1]);
    }

    /**
     * @param $key
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function getBankEntryLines()
    {
        $transactionlines = new \Picqer\Financials\Exact\SalesEntryLine(self::connect());
        return $transactionlines->filter('', '', '', ['$top' => 1]);
    }

    /**
     * @param $key
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function getAccount($key)
    {
        $account = new Account(self::connect());
        return $account->filter("ID eq guid'{$key}'")[0];
    }

    /**
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function getSalesInvoices($connection = null): array
    {
        $con = null;
        if (isset($connection)) {
            $con = $connection;
        } else {
            $con = self::connect();
        }
        $salesInvoices = new \Picqer\Financials\Exact\SalesInvoice($con);
        return $salesInvoices->get();
    }

    /**
     * Create a Exact online Sales Invoice
     * @param $customer_id
     * @param $lines
     * @param $orderId
     * @return SalesInvoice
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws Exception
     */
    public static function create_invoice(object $order, array $lines)
    {
        $connection = self::connect();
        if (!isset($order->customer->Exact_id)) {
            $data = [
                'first_name' => $order->customer->first_name,
                'last_name' => $order->customer->last_name,
                'invoice_same_delivery' => '1',
                'adres' => $order->getDelivery()['adres'],
                'placename' => $order->getDelivery()['placename'],
                'postalcode' => $order->getDelivery()['postalcode']
            ];
            $account = self::create_account($data, $order->customer, $connection);
            //Refresh the model so the Exact id is present this time
            $order->refresh();
        }

        if (self::AccountExist($order->customer->Exact_id, $connection)) {
            $salesInvoice = new SalesInvoice($connection);
            $salesInvoice->Description = 'Periode '.$order->next_invoice->monthName.' '.$order->next_invoice->year;
            $salesInvoice->InvoiceTo = $order->customer->Exact_id;
            $salesInvoice->OrderedBy = $order->customer->Exact_id;
            $salesInvoice->OrderDate = now();
            $salesInvoice->YourRef = ''.$order->number;
            $salesInvoice->SalesInvoiceLines = $lines;
            $salesInvoice->save();
            //Save Exact invoice local
            ExactSalesInvoices::ExactUpdate($salesInvoice);
            return $salesInvoice;
        } else {
            throw new Exception('Exact relation does not exist');
        }
    }

    public static function AccountExist($id, $connection)
    {
        $account = new Account($connection);
        if (!count($account->filter("ID eq guid'{$id}'")) == 0) {
            return true;
        }
        return false;
    }


    public static function setWebhooks()
    {
        $connection = self::connect();
        $subscriptions = new WebhookSubscription($connection);
        foreach ($subscriptions->get() as $subscription) {
            $subscription->delete();
        }
//
//        $topics = [
//            'FinancialTransactions',
//            'SalesInvoices',
////        'CostTransactions',
//            'Documents',
//            'Accounts'
//        ];
        $topics = config('exact.webhook_topics');
        foreach ($topics as $topic) {
            $subscription = new WebhookSubscription($connection);
            $subscription->deleteSubscriptions();
            $subscription->CallbackURL = config('app.url').config('exact.webhook_url');
            $subscription->Topic = $topic;
            $subscription->save();
        }
    }

    public function getTopicModel($topic,$key ,$con = null)
    {
        $con = null;
        if (isset($connection)) {
            $con = $connection;
        } else {
            $con = self::connect();
        }
        $model_string = "\Picqer\Financials\Exact\\".$topic;
        $model = new $model_string(self::connect());
        return $model->filter("ID eq guid'{$key}'")[0];
    }

    /**
     *
     */
    public function webhook($topic, $action, $key)
    {
        $model = $this->getTopicModel($topic,$key);
        switch ($topic) {
            case "Accounts":
                //Update action will also be fired when a new item is created
                if ($action == "Update") {
                    Event::dispatch(new AccountsUpdated($model));
                } elseif ($action == "Delete") {
                    Event::dispatch(new AccountsDeleted($model));
                }
                break;
            case "BankAccounts":
                //Update action will also be fired when a new item is created
                if ($action == "Update") {
                    Event::dispatch(new BankAccountsUpdated($model));
                } elseif ($action == "Delete") {
                    Event::dispatch(new BankAccountsDeleted($model));
                }
                break;
            case "SalesInvoices":
                //Update action will also be fired when a new item is created
                if ($action == "Update") {
                    Event::dispatch(new SalesInvoiceUpdated($model));
                } elseif ($action == "Delete") {
                    Event::dispatch(new SalesInvoiceDeleted($model));
                }
                break;
        }
    }

    public static function toDateTime($exact)
    {
        $timestamp = substr($exact, 6, 10);
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        return $date;
    }
}
