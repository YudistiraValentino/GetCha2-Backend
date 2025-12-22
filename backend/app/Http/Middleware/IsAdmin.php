<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Cek apakah sudah login & role-nya admin
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }

        // Kalau bukan admin, logout paksa & lempar balik
        Auth::logout();
        return redirect()->route('login')->withErrors(['email' => 'Anda bukan Admin!']);
    }
}