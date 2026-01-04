<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

class SimpleApiAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Cek Header (Cara Normal)
        $token = $request->bearerToken();

        // 2. Cek Body (Cara Tikus)
        if (!$token) $token = $request->input('token');

        // 3. 🔥 Cek URL Query Param (Cara NUKLIR - Anti Gagal)
        // Contoh: /api/admin/maps?token=12345xxxxx
        if (!$token) $token = $request->query('token');

        // Validasi
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal: Unauthenticated. Token tidak ditemukan di Header, Body, maupun URL.'
            ], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken || !$accessToken->tokenable) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Token Invalid.'
            ], 401);
        }

        auth()->login($accessToken->tokenable);

        return $next($request);
    }
}