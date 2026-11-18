<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            auth()->check() &&
            auth()->user()->must_change_password &&
            !$request->routeIs('account.change', 'account.update')
        ) {
            return redirect()->route('account.change');
        }

        return $next($request);
    }
}