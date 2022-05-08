<?php

namespace Pdik\LaravelExactOnline\Http\Controllers;


use Illuminate\Http\Request;
use Pdik\LaravelExactOnline\Exceptions\CouldNotConnectException;
use Pdik\LaravelExactOnline\Models\ExactSettings;
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
        Exact::webhook($request->Content['Topic'], $request->Content['Action'], $request->Content['Key']);
        return $response; //always return a success 200/ 201 response
    }

    /**
     * @throws CouldNotConnectException
     */
    public function callback(Request $request)
    {
        ExactSettings::setValue('EXACT_AUTHORIZATION_CODE', $request->get('code'));
        ExactSettings::setValue('EXACT_ACCESS_TOKEN', "");
        ExactSettings::setValue('EXACT_REFRESH_TOKEN', "");
        ExactSettings::setValue('EXACT_EXPIRES_IN', "");
        Exact::connect();
        return redirect()->route('exact-online.index');
    }
}