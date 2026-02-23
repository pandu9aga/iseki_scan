<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Cek session Id_User
        if (!session()->has('Id_User')) {
            return redirect()->route('login')->withErrors(['accessDenied' => 'You must login first']);
        }

        // Cek tipe user harus Transit (misal 3)
        if (session('Id_Type_User') != 3) {
            session()->forget('Id_User');
            session()->forget('Id_Type_User');
            return redirect()->route('login')->withErrors(['accessDenied' => 'You must login with Transit account']);
        }

        return $next($request);
    }
}
