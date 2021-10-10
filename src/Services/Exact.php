<?php
namespace Pdik\LaravelExactOnline\Services;

use Carbon\Carbon;
use DateTime;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
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
use Pdik\LaravelExactOnline\Exceptions\CouldNotConnectException;
use Pdik\LaravelExactOnline\Exceptions\CouldNotFindWebhookException;
use Pdik\LaravelExactOnline\Models\ExactSettings;
use Picqer\Financials\Exact\Account;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\SalesInvoice;
use Picqer\Financials\Exact\WebhookSubscription;

class Exact
{
      /** @var string */
    private static $lockKey = 'exactonline.refreshLock';

    /** @var null|Lock */
    public static $lock = null;

    /**
     * @throws \Picqer\Financials\Exact\ApiException
     * @throws CouldNotConnectException
     */
    public static function connect(){
        $connection = new Connection();
        $connection->setRedirectUrl(ExactSettings::getValue('callback')); // Same as entered online in the App Center
        $connection->setExactClientId(ExactSettings::getValue('client_id'));
        $connection->setExactClientSecret(ExactSettings::getValue('client_secret'));

        if(ExactSettings::getValue('EXACT_AUTHORIZATION_CODE')){
            $connection->setAuthorizationCode(ExactSettings::getValue('EXACT_AUTHORIZATION_CODE'));
        }

        if(ExactSettings::getValue('EXACT_REFRESH_TOKEN')){
            $connection->setRefreshToken(ExactSettings::getValue('EXACT_REFRESH_TOKEN'));
        }

        if(ExactSettings::getValue('EXACT_EXPIRES_IN')){
            $connection->setTokenExpires(ExactSettings::getValue('EXACT_EXPIRES_IN'));
        }

        $connection->setAcquireAccessTokenLockCallback([Exact::class, 'acquireLock']);
        $connection->setAcquireAccessTokenUnlockCallback([Exact::class, 'releaseLock']);
        // Set callback to save newly generated tokens
        $connection->setTokenUpdateCallback('\Pdik\laravelExactonline\Services\Exact::tokenUpdateCallback');
        // Make the client connect and exchange tokens
        try {
            $connection->connect();
        } catch (CouldNotConnectException $e) {
            throw new CouldNotConnectException('Could not connect to Exact: ' . $e->getMessage());
        }

        return $connection;
    }

    /**
     * Get webhook topic classes
     * @throws CouldNotFindWebhookException
     */
    public static function webhook($topic,$action,$id){
        //use events so every one could listen to these events and do something by there self
        switch ($topic){
            case "Accounts":
                if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "BankAccounts":
                //Bankaccount
                 if($action == "Update"){
                  Event::dispatch(new BankAccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new BankAccountsDeleted($id, Account::find($id)));
                }
                break;
            case "Contacts":
                //Contacts
                 if($action == "Update"){
                  Event::dispatch(new ContactsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new ContactsDeleted($id, Account::find($id)));
                }
                break;
            case "HostingOpportunities":
                //HostingOpportunities
                 if($action == "Update"){
                  Event::dispatch(new HostingOpportunitiesUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new HostingOpportunitiesDeleted($id, Account::find($id)));
                }
                break;
            case "Opportunities":
                //Opportunities
                 if($action == "Update"){
                  Event::dispatch(new OpportunitiesUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new OpportunitiesDeleted($id, Account::find($id)));
                }
                break;
            case "QuotationLines":
                //QuotationLines
                 if($action == "Update"){
                  Event::dispatch(new QuotationLinesUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new QuotationLinesDeleted($id, Account::find($id)));
                }
                break;
            case "Quotations":
                //Quotations
                 if($action == "Update"){
                  Event::dispatch(new QuotationsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new QuotationsDeleted($id, Account::find($id)));
                }
                break;
            case "DocumentAttachments":
                //DocumentAttachments
                 if($action == "Update"){
                  Event::dispatch(new DocumentAttachmentsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new DocumentAttachmentsDeleted($id, Account::find($id)));
                }
                break;
            case "Documents":
                //Documents
                 if($action == "Update"){
                  Event::dispatch(new DocumentAttachmentsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new DocumentAttachmentsDeleted($id, Account::find($id)));
                }
                break;
            case "GLAccounts":
                //GLAccounts
                 if($action == "Update"){
                  Event::dispatch(new GLAccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new GLAccountsDeleted($id, Account::find($id)));
                }
                break;
            case "JournalStatusList":
                //JournalStatusList
                 if($action == "Update"){
                  Event::dispatch(new JournalStatusListUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new JournalStatusListDeleted($id, Account::find($id)));
                }
                break;
            case "BankEntries":
                //BankEntries
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "BankEntryLines":
                //BankEntryLines
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "CashEntries":
                //CashEntries
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "CashEntryLines":
                //CashEntryLines
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "TransactionLines":
                //TransactionLines
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "GeneralJournalEntries":
                //GeneralJournalEntries
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "GeneralJournalEntryLines":
                //GeneralJournalEntryLines
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "Items":
                //Items
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "StockPosition":
                //StockPosition
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "MailMessageAttachments":
                //MailMessageAttachments
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "MailMessagesSent":
                //MailMessagesSent
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "CostTransactions":
                //CostTransactions
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "ProjectPlanning":
                //ProjectPlanning
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "TimeTransactions":
                //TimeTransactions
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "PurchaseEntries":
                //PurchaseEntries
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "PurchaseEntryLines":
                //PurchaseEntryLines
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "PurchaseOrderLines":
                //PurchaseOrderLines
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "PurchaseOrders":
                //PurchaseOrders
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "SalesEntries":
                //SalesEntries
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "SalesEntryLines":
                //SalesEntryLines
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "SalesInvoiceLines":
                //SalesInvoiceLines
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "SalesInvoices":
                //SalesInvoices
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "GoodsDeliveries":
                //GoodsDeliveries
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "GoodsDeliveryLines":
                //GoodsDeliveryLines
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "SalesOrderLines":
                //SalesOrderLines
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            case "SalesOrders":
                //SalesOrders
                 if($action == "Update"){
                  Event::dispatch(new AccountsUpdated($id, Account::find($id)));
                }elseif($action == "Delete"){
                  Event::dispatch(new AccountsDeleted($id, Account::find($id)));
                }
                break;
            default:
                throw new CouldNotFindWebhookException('Webhook do not exist');
                break;
        }
    }

     /**
     * Acquire refresh lock to avoid duplicate calls to exact.
     */
    public static function acquireLock(): bool
    {
        /** @var Repository $cache */
        $cache = app()->make(Repository::class);
        $store = $cache->getStore();

        if (!$store instanceof LockProvider) {
            return false;
        }

        self::$lock = $store->lock(self::$lockKey, 60);
        return self::$lock->block(30);
    }

    /**
     * Release lock that was set.
     */
    public static function releaseLock()
    {
        return optional(self::$lock)->release();
    }

    /**
     * Handle the exact callback with new tokens, save them in the settings table
     *
     * @param Connection $connection
     *
     * @since 04/06/2021
     * @version 1.2
     * @author Pascal Lieverse <P.Lieverse@brightness-group.com>
     * @author Pepijn dik
     *
     * @return void
     */
    public static function tokenUpdateCallback(Connection $connection){
        ExactSettings::setValue('EXACT_ACCESS_TOKEN', $connection->getAccessToken());
        ExactSettings::setValue('EXACT_REFRESH_TOKEN', $connection->getRefreshToken());
        ExactSettings::setValue('EXACT_EXPIRES_IN', $connection->getTokenExpires() -60);
    }


     /**
     * Get the login url for exact to make a connection
     *
     * @since 04/06/2021
     * @author Pascal Lieverse <P.Lieverse@brightness-group.com>
     *
     * @return string url
     */
    public static function getLoginUrl(){
        $connection = new Connection();
        $connection->setRedirectUrl(ExactSettings::getValue('callback'));
        $connection->setExactClientId(ExactSettings::getValue('client_id'));
        $connection->setExactClientSecret(ExactSettings::getValue('client_secret'));
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
    public static function getStats(){

        try {
            $connection = self::connect();
            $account = new \Picqer\Financials\Exact\Me($connection);
            $result = $account->get();

//          Just get a random thing to receive the limit headers
            $titles = new \Picqer\Financials\Exact\JobTitle($connection);
            $titles->get(['$top'=> 1]);

            return [
                'UserName' => $result[0]->UserName,
                'dailyLimit' => $connection->getDailyLimit(),
                'dailyLimitRemaining' => $connection->getDailyLimitRemaining(),
                'minutelyLimit' => $connection->getMinutelyLimit(),
                'minutelyLimitRemaining' => $connection->getMinutelyLimitRemaining()
            ];
        }
        catch (CouldNotConnectException $e){
            Log::error($e->getMessage());
            return [
                'UserName' => '',
                'dailyLimit' => 0,
                'dailyLimitRemaining' => 0,
                'minutelyLimit' => 0,
                'minutelyLimitRemaining' => 0
            ];
        }
    }
    /**
     * Get path from url.
     *
     * @param string $url
     * @return string
     */
    protected static function pathFromUrl($url)
    {
        $url_parts = parse_url($url);

        return preg_replace('/^\//', '', $url_parts['path']);
    }

    /**
     * Get webhook url
     * @return string
     */
    public static function webhookUrl()
    {
        return self::pathFromUrl(config('exact.webhook_url'));
    }

    /**
     * Subscripe to the webhook
     * @throws CouldNotConnectException
     * @throws \Picqer\Financials\Exact\ApiException
     */
    public static function setWebhooks(){
        $connection = self::connect();
            $subscriptions = new WebhookSubscription($connection);
            foreach ($subscriptions->get() as $subscription) {
                $subscription->delete();
            }
         foreach (config('exact.webhook_topics') as $topic) {
             $subscription = new WebhookSubscription($connection);
             $subscription->deleteSubscriptions();
             $subscription->CallbackURL = Exact::webhookUrl();
             $subscription->Topic = $topic;
             $subscription->save();
         }
    }
      public static function AccountExist($id){
           $account = new Account(self::connect());
           if(!count($account->filter("ID eq guid'{$id}'")) == 0){
               return true;
           }
           return false;
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
    public static function getSalesInvoices(): array
    {
        $salesInvoices = new \Picqer\Financials\Exact\SalesInvoice(self::connect());
        return $salesInvoices->get();
    }
    /**
     * @param $key
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function getSalesInvoice($key)
    {
        $salesInvoices = new \Picqer\Financials\Exact\SalesInvoice(self::connect());
        return $salesInvoices->filter("InvoiceID eq guid'{$key}'")[0];
    }
    public static function  getSalesEntrys(){
        $salesEntry = new \Picqer\Financials\Exact\SalesEntry(self::connect());
         return $salesEntry->filter('', '', '', ['$top'=> 1]);
    }
    public static function getSalesInvoiceByNumber($key)
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
        return $transactionline->filter("ID eq guid'{$key}'",'','',['$top'=> 1])[0];
    }
    /**
     * @param $key
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function getTransactions()
    {
        $transactionlines = new \Picqer\Financials\Exact\TransactionLine(self::connect());
        return $transactionlines->filter('', '', '', ['$top'=> 1]);
    }
       /**
     * @param $key
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function getBankEntryLines()
    {
        $transactionlines = new \Picqer\Financials\Exact\SalesEntryLine(self::connect());
        return $transactionlines->filter('', '', '', ['$top'=> 1]);
    }
    public static function toDateTime($exact) {
        $timestamp = substr($exact, 6, 10);
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        return $date;
    }


}