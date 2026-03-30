<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AreaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Cek session Id_User untuk login
        if (!session()->has('Id_User')) {
            return redirect()->route('login')->withErrors(['accessDenied' => 'You must login first']);
        }

        // Cek tipe user harus Area (misal 4)
        if (session('Id_Type_User') != 4) {
            session()->forget('Id_User');
            session()->forget('Id_Type_User');
            return redirect()->route('login')->withErrors(['accessDenied' => 'You must login with Area account']);
        }

        return $next($request);
    }
}
