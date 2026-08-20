<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnyAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('Id_Member') && ! session()->has('Id_User')) {
            return redirect()->route('login')->withErrors(['accessDenied' => 'You must login first']);
        }

        return $next($request);
    }
}