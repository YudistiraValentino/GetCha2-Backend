<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Cek Login & Cek Role Admin
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }

        // 2. Kalau gagal, balikin JSON Error 403 (Bukan Redirect)
        return response()->json([
            'success' => false,
            'message' => 'Access Denied: You are not Admin!',
        ], 403);
    }
}