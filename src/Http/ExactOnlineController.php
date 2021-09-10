<?php

namespace Pdik\laravelExactonline\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Request;
use Pdik\laravelExactonline\Services\Exact;
use Picqer\Financials\Exact\Webhook\Authenticatable;

class ExactOnlineController extends Controller
{
    public function index()
    {
        return view('exactonline::index', ['stats'=>     Exact::getStats() ]);
    }
      public function sync(){
        Artisan::call('exact:sync');
    }
      public function setWebhook(){
        Exact::setWebhooks();
        return redirect()->route('exact.index')->withStatus(__('Exact online webhooks verbonden'));
    }
          public function handleWebhook(Request $request){

                 if(isset($request->Content)){
                     Log::debug($request->Content);;
                  if($request->Content['Topic'] == 'SalesInvoices') {
                    if ($request->Content['Action'] == "Update") {
                        $salesinvoice = Exact::getSalesInvoice($request->Content['Key']);
                         ExactSalesInvoices::ExactUpdate($request->Content['Key'], $salesinvoice);
                    }
                    else if ($request->Content['Action'] == "Delete") {
                        ExactSalesInvoices::where('invoice_id', '=', $request->Content['Key'])->delete();
                    }
                    }
                    if($request->Content['Topic'] == 'FinancialTransactions') {
                    if ($request->Content['Action'] == "Update") {
                        $salesInoivce = Exact::getTransaction($request->Content['Key']);
                        TransactionLines::firstOrCreate($salesInoivce);
                    } else if ($request->Content['Action'] == "Delete") {
                        TransactionLines::where('invoice_id', '=', $request->Content['Key'])->delete();
                    }
                    }
                    if($request->Content['Topic'] == 'Account') {
                    if ($request->Content['Action'] == "Update") {
                        $account = Exact::GetAccount($request->Content['Key']);
                        Customer::saveExactModel($account);
                    } else if ($request->Content['Action'] == "Delete") {
                        Customer::where('Exact_ID', '=', $request->Content['Key'])->delete();
                    }
                }
            }

    }
}