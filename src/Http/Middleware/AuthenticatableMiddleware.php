<?php

namespace Pdik\LaravelExactOnline\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticatableMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $matches = [];
        $matched = preg_match('/^{"Content":(.*),"HashCode":"(.*)"}$/', $request->getContent(), $matches);
        if ($matched === 1 && isset($matches[1]) && isset($matches[2])) {;
            if(!$matches[2] === strtoupper(hash_hmac('sha256', $matches[1], config('exact.webhook_secret')))){
                $response = response()->json(null, 401);
                $response->setContent(null);
                return $response;
            }
        }
        return $next($request);
    }
}