<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('Id_User')) {
            return redirect()->route('login')->withErrors(['accessDenied' => 'You must login first']);
        }

        if(session('Id_Type_User') != 2) {
            session()->forget('Id_User');
            session()->forget('Id_Type_User');
            return redirect()->route('login')->withErrors(['accessDenied' => 'You must login with admin account']);
        }

        return $next($request);
    }
}

