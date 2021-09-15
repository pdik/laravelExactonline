<?php

namespace Pdik\laravelExactonline\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
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

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Pdik\laravelExactonline\Exceptions\CouldNotFindWebhookException
     */
    public function handleWebhook(Request $request){

        if(isset($request->Content)){
            Log::debug($request->Content);;
            foreach (config('exact.webhook_topics') as $topic){
                if($request->Content['Topic'] == $topic){
                  Exact::webhook($topic,$request->Content['Action'], $request->Content['Key']);
                }
            }
            if($request->Content['Topic'] == 'SalesInvoices') {
                if ($request->Content['Action'] == "Update") {
                    $salesinvoice = Exact::getSalesInvoice($request->Content['Key']);
                    ExactSalesInvoices::ExactUpdate($request->Content['Key'], $salesinvoice);
                }
                else if ($request->Content['Action'] == "Delete") {
                    ExactSalesInvoices::where('invoice_id', '=', $request->Content['Key'])->delete();
                }
            }

        }

    }
}