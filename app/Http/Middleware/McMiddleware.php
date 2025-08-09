<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class McMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Cek session Id_User untuk admin
        if (!session()->has('Id_User')) {
            return redirect()->route('login')->withErrors(['accessDenied' => 'You must login first']);
        }

        // Cek tipe user harus admin (misal 1)
        if (session('Id_Type_User') != 1) {
            session()->forget('Id_User');
            session()->forget('Id_Type_User');
            return redirect()->route('login')->withErrors(['accessDenied' => 'You must login with admin account']);
        }

        return $next($request);
    }
}
