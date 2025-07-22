<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('Id_User')) {
            return redirect()->route('login')->withErrors(['accessDenied' => 'You must login first']);
        }

        if(session('Id_Type_User') != 1) {
            session()->forget('Id_User');
            session()->forget('Id_Type_User');
            return redirect()->route('login')->withErrors(['accessDenied' => 'You must login with user account']);
        }

        return $next($request);
    }
}

