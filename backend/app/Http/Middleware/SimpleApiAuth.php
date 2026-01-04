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
        // 1. Ambil Token dari Header 'Authorization: Bearer ...'
        $token = $request->bearerToken();

        // 2. Kalau gak bawa token, tolak
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Header Token tidak ditemukan.'
            ], 401);
        }

        // 3. Cek Token di Database Manual
        $accessToken = PersonalAccessToken::findToken($token);

        // 4. Kalau token gak ada di DB atau user-nya udah dihapus
        if (!$accessToken || !$accessToken->tokenable) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Token Invalid atau Kadaluarsa.'
            ], 401);
        }

        // 5. LOLOS! Paksa User Login berdasarkan token tadi
        // (Bypass semua logic session/cookie laravel yang ribet)
        auth()->login($accessToken->tokenable);

        return $next($request);
    }
}