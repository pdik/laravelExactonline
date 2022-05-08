<?php

namespace Pdik\LaravelExactOnline\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Picqer\Financials\Exact\Webhook\Authenticatable;

class AuthenticatableMiddleware
{
    use Authenticatable;

    public function handle(Request $request, Closure $next)
    {
        // If the body is empty we assume it's a webhook validation request, we wouldn't do anything just return 200
        if (empty($request->getContent())) {
            return response('');
        }
        if (
            $this->authenticate($request->getContent(), config('exact-online-client-laravel.webhook_secret'))
        ) {
            return $next($request);
        }

        return abort(403, 'Verification failed.');
    }
}