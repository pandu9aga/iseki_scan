<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('Id_Member')) {
            session()->forget('Id_Member');
            session()->forget('NIK_Member');
            session()->forget('Name_Member');
            return redirect()->route('login')->withErrors(['accessDenied' => 'You must login first']);
        }

        return $next($request);
    }
}
