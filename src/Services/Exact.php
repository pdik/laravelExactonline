<?php

namespace Pdik\LaravelExactOnline\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Pdik\LaravelExactOnline\Events\AccountsDeleted;
use Pdik\LaravelExactOnline\Events\AccountsUpdated;
use Pdik\LaravelExactOnline\Events\BankAccountsDeleted;
use Pdik\LaravelExactOnline\Events\BankAccountsUpdated;
use Pdik\LaravelExactOnline\Events\FinancialTransactionDeleted;
use Pdik\LaravelExactOnline\Events\FinancialTransactionUpdated;
use Pdik\LaravelExactOnline\Events\SalesInvoiceDeleted;
use Pdik\LaravelExactOnline\Events\SalesInvoiceUpdated;
use Pdik\LaravelExactOnline\Exceptions\CouldNotConnectException;
use Pdik\LaravelExactOnline\Models\ExactSalesInvoices;
use Pdik\LaravelExactOnline\Models\ExactSettings;
use Pdik\LaravelExactOnline\Traits\PopulatesExactAccountFields;
use phpDocumentor\Reflection\Types\Boolean;
use Picqer\Financials\Exact\Account;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\Document;
use Picqer\Financials\Exact\DocumentAttachment;
use Picqer\Financials\Exact\Subscription;
use Picqer\Financials\Exact\SubscriptionLine;
use Picqer\Financials\Exact\SalesInvoice;
use Picqer\Financials\Exact\WebhookSubscription;

class Exact
{

    public static string $version = 'v1';

    /** @var string */
    private static string $lockKey = 'exactonline.refreshLock';
    /**
     * @var int $lockTimeout
     * @comment The time (in seconds) the exact-lock will be locked before opening automatically
     */
    private static int $lockTimeout = 570; // 9 and 30 seconds before the lock is automatically opened

    /**
     * @var int $threadTimeout
     * @comment The time (in seconds) a thread will wait for it's turn to do oauth before failing
     */
    private static int $threadTimeout = 3000;


    /**
     * @throws CouldNotConnectException
     */
    public static function connect(): Connection
    {
        $connection = new Connection();

        if (self::exactConfigKeysValid() === false) {
            return $connection;
        }
        $connection->setRedirectUrl(config('exact.callback')); // Same as entered online in the App Center
        $connection->setExactClientId(config('exact.client_id'));
        $connection->setExactClientSecret(config('exact.client_secret'));

        self::setKeys($connection);
        // Set callback to save newly generated tokens
        $connection->setTokenUpdateCallback([self::class, 'tokenUpdateCallback']);
        //RefreshAccesTokensCallback
        $connection->setRefreshAccessTokenCallback([self::class, 'refreshAccessTokenCallback']);
        // Set callbacks for locking/unlocking the token callback. This prevents multiple simultaneous requests
        // from messing up the stored tokens.
        $connection->setAcquireAccessTokenLockCallback([self::class, 'acquireLock']);
        $connection->setAcquireAccessTokenUnlockCallback([self::class, 'releaseLock']);


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
        } catch (\Exception $e) {
            report($e);
            throw new CouldNotConnectException('Could not connect to Exact: '.$e->getMessage());
        }
        // Always return a Connection, even if it didn't authenticate successfully
        return $connection;
    }


    /**
     * Checks if Keys are set
     * @return bool
     */
    private static function exactConfigKeysValid()
    {
        return
            !is_null(config('exact.callback')) &&
            !is_null(config('exact.client_id')) &&
            !is_null(config('exact.client_secret'));
    }

    /**
     * @param  Connection  $connection
     * @return void
     */
    public static function setKeys(Connection $connection)
    {
        if (ExactSettings::getValue('EXACT_AUTHORIZATION_CODE')) {
            $connection->setAuthorizationCode(ExactSettings::getValue('EXACT_AUTHORIZATION_CODE'));
        }

        if (ExactSettings::getValue('EXACT_REFRESH_TOKEN')) {
            $connection->setRefreshToken(ExactSettings::getValue('EXACT_REFRESH_TOKEN'));
        }
        if (ExactSettings::getValue('EXACT_ACCESS_TOKEN')) {
            $connection->setAccessToken(ExactSettings::getValue('EXACT_ACCESS_TOKEN'));
        }
        if (ExactSettings::getValue('EXACT_EXPIRES_IN')) {
            $connection->setTokenExpires(ExactSettings::getValue('EXACT_EXPIRES_IN'));
        }

    }

    /**
     * @param  Connection  $connection
     * @return void
     */
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
        ExactSettings::setValue('EXACT_ACCESS_TOKEN', $connection->getAccessToken());
        ExactSettings::setValue('EXACT_REFRESH_TOKEN', $connection->getRefreshToken());
        ExactSettings::setValue('EXACT_EXPIRES_IN', $connection->getTokenExpires());
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
    public static function getLoginUrl(): string
    {

        $connection = new Connection();
        $connection->setRedirectUrl(config('exact.callback')); // Same as entered in the App Center
        $connection->setExactClientId(config('exact.client_id'));
        $connection->setExactClientSecret(config('exact.client_secret'));
        return $connection->getAuthUrl();
    }

    /**
     * Get the api status (usages, limits) to show that the connection is active.
     *
     * @param $connection
     * @return array
     * @since 04/06/2021
     * @author Pascal Lieverse <P.Lieverse@brightness-group.com>
     * @author Pepijn dik <pepijn@pdik.nl>
     */
    public static function getStats($connection = null)
    {

       try {
        $connection = self::switchConnections($connection);
//      dd($connection);
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
            return [
                'UserName' => '',
                'dailyLimit' => 0,
                'dailyLimitRemaining' => 0,
                'minutelyLimit' => 0,
                'minutelyLimitRemaining' => 0
            ];
        }

    }


    public static function create_subscription($to_id, $request, $lines, $connection = null)
    {
        $sub = new Subscription(Exact::switchConnections($connection));

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

    /**
     * @throws CouldNotConnectException
     */
    public static function getSubscriptionLines($ID, $connection = null)
    {
        $sublines = new SubscriptionLine(Exact::switchConnections($connection));
        return $sublines->filter("EntryID eq Guid'$ID'");

    }

    /**
     * @throws CouldNotConnectException
     * @throws \Picqer\Financials\Exact\ApiException
     */
    public static function createSubscriptionLine($line, $sub, $connection = null)
    {
        // dd($line);
        $sub_lines = new SubscriptionLine(Exact::switchConnections($connection));
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
    public static function updateSubScriptionLine($id, $line, $account, $connection = null)
    {
        //First check if exist
        $connection = Exact::switchConnections($connection);
        $sublines = new SubscriptionLine($connection);
        $salesInvoice = new SalesInvoice($connection);
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
     * @throws CouldNotConnectException
     */
    public static function getReceivablesList($connection = null)
    {
        $receiveable = new \Picqer\Financials\Exact\ReceivablesList(Exact::switchConnections($connection));
        return $receiveable->filter('', '', '', ['$top' => 1]);
    }

    /**
     * @param $key
     * @return \Picqer\Financials\Exact\SalesInvoice
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws Exception
     */
    public static function getSalesInvoice($key, $connection = null): SalesInvoice
    {

        $salesInvoices = new \Picqer\Financials\Exact\SalesInvoice(Exact::switchConnections($connection));
        return $salesInvoices->filter("InvoiceID eq guid'{$key}'")[0];
    }

    /**
     * Download document from Exact
     * @throws CouldNotConnectException
     */
    public static function downloadDocument($salesInvoiceID, $connection = null)
    {
        $con = Exact::switchConnections($connection);
        if ($con->needsAuthentication()) {
            return;
        }

        $document = new Document($con);
        //Get first document
        $document = $document->filter("FinancialTransactionEntryID eq guid'{$salesInvoiceID}'")[0];
        $documentAttachment = new DocumentAttachment($con);

        $attachments = $documentAttachment->filter("Document eq guid'".$document->ID."'");
        foreach ($attachments as $invoice_attachment) {
            if (\Str::contains($invoice_attachment->FileName, 'PDF')) {
                return $invoice_attachment;
            }
        }
        return null;
    }

    /**
     * @throws Exception
     */
    public static function getSalesEntrys($connection = null)
    {
        $salesEntry = new \Picqer\Financials\Exact\SalesEntry(Exact::switchConnections($connection));
        return $salesEntry->filter('', '', '', ['$top' => 1]);
    }

    /**
     * @param $key
     * @return \Picqer\Financials\Exact\SalesInvoice
     * @throws Exception
     */
    public static function getSalesInvoiceByNumber($key, $connection = null): SalesInvoice
    {
        $salesInvoices = new \Picqer\Financials\Exact\SalesInvoice(Exact::switchConnections($connection));
        return $salesInvoices->filter("InvoiceNumber eq int'{$key}'")[0];
    }

    /**
     * @param $key
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws CouldNotConnectException
     */
    public static function getTransaction($key, $connection = null)
    {
        $transactionline = new \Picqer\Financials\Exact\TransactionLine(Exact::switchConnections($connection));
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
     * @throws CouldNotConnectException
     */
    public static function getTransactions($connection = null)
    {
        $transactionlines = new \Picqer\Financials\Exact\TransactionLine(Exact::switchConnections($connection));
        return $transactionlines->filter('', '', '', ['$top' => 1]);
    }

    /**
     * @param $key
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException|CouldNotConnectException
     */
    public static function getBankEntryLines($connection = null)
    {
        $transactionlines = new \Picqer\Financials\Exact\SalesEntryLine(Exact::switchConnections($connection));
        return $transactionlines->filter('', '', '', ['$top' => 1]);
    }

    /**
     * @param $key
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function getAccount($key, $connection = null)
    {
        $account = new Account(Exact::switchConnections($connection));
        return $account->filter("ID eq guid'{$key}'")[0];
    }

    /**
     * @param  null  $connection  use connection, when doing multiple calls
     * @return array
     * @throws CouldNotConnectException
     */
    public static function getSalesInvoices($connection = null): array
    {
        $salesInvoices = new \Picqer\Financials\Exact\SalesInvoice(Exact::switchConnections($connection));
        return $salesInvoices->get();
    }

    /**
     * @param $id
     * @param  Null  $connection  use when doing multiple calls
     * @return bool
     */
    public static function AccountExist($id, $connection = null): Boolean
    {

        $account = new Account(Exact::switchConnections($connection));
        if (!count($account->filter("ID eq guid'{$id}'")) == 0) {
            return true;
        }
        return false;
    }

    /**
     * @param  Null  $connection
     * @return Connection
     * @throws CouldNotConnectException
     */
    public static function switchConnections($connection = null): Connection
    {
        if ($connection != null) {
            return $connection;
        } else {
            return self::connect();
        }

    }

    public static function setWebhooks()
    {
        $connection = self::connect();
        //Get all webhooks
        $subscriptions = new WebhookSubscription($connection);

        //Delete existing webhooks
        foreach ($subscriptions->get() as $subscription) {
            $subscription->delete();
        }
        $topics = config('exact.webhook_topics');
        foreach ($topics as $topic) {
            $webhookSubscription = new WebhookSubscription($connection);
            $webhookSubscription->deleteSubscriptions();
            $webhookSubscription->CallbackURL = config('app.url').config('exact.webhook_url');
            $webhookSubscription->Topic = $topic;
            $webhookSubscription->save();
        }
    }

    public function getTopicModel($topic, $key, $con = null)
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
        switch ($topic) {
            case "Accounts":
                //Update action will also be fired when a new item is created
                if ($action == "Update") {
                    Event::dispatch(new AccountsUpdated($key));
                } elseif ($action == "Delete") {
                    Event::dispatch(new AccountsDeleted($key));
                }
                break;
            case "BankAccounts":
                //Update action will also be fired when a new item is created
                if ($action == "Update") {
                    Event::dispatch(new BankAccountsUpdated($key));
                } elseif ($action == "Delete") {
                    Event::dispatch(new BankAccountsDeleted($key));
                }
                break;
            case "SalesInvoices":
                //Update action will also be fired when a new item is created
                if ($action == "Update") {
                    Event::dispatch(new SalesInvoiceUpdated($key));
                } elseif ($action == "Delete") {
                    Event::dispatch(new SalesInvoiceDeleted($key));
                }
                break;
            case "FinancialTransactions":
                //Update action will also be fired when a new item is created
                if ($action == "Update") {
                    Event::dispatch(new FinancialTransactionUpdated($key));
                } elseif ($action == "Delete") {
                    Event::dispatch(new FinancialTransactionDeleted($key));
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
