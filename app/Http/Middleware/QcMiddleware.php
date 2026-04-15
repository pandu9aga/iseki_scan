<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QcMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Cek session Id_User
        if (!session()->has('Id_User')) {
            return redirect()->route('login')->withErrors(['accessDenied' => 'You must login first']);
        }

        // Cek tipe user harus QC (6)
        if (session('Id_Type_User') != 6) {
            session()->forget('Id_User');
            session()->forget('Id_Type_User');
            return redirect()->route('login')->withErrors(['accessDenied' => 'You must login with QC account']);
        }

        return $next($request);
    }
}
