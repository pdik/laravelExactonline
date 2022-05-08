<?php

namespace Pdik\LaravelExactOnline\Http\Controllers;


use Illuminate\Http\Request;
use Pdik\LaravelExactOnline\Services\Exact;

class WebhookController
{
    /**
     * Handle the incoming requests.
     */
    public function handle(Request $request)
    {
        //Build the response
        $response = response()->json(null, 201);
        $response->setContent(null);
        //Handle the request via the Exact service
        Exact::webhook($request->Content['Topic'],$request->Content['Action'],$request->Content['Key']);
        return $response;
    }
}