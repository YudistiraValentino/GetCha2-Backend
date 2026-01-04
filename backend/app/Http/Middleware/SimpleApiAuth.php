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
        // 1. Cek Header 'Authorization: Bearer ...' (Cara Normal)
        $token = $request->bearerToken();

        // 2. 🔥 JALUR TIKUS (Cara Darurat buat Upload File)
        // Kalau header kosong (dibuang server), cek apakah ada 'token' yang diselipkan di form-data
        if (!$token) {
            $token = $request->input('token');
        }

        // 3. Kalau masih gak nemu juga, baru tolak
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Header Token tidak ditemukan.'
            ], 401);
        }

        // 4. Cek Token di Database
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken || !$accessToken->tokenable) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Token Invalid atau Kadaluarsa.'
            ], 401);
        }

        // 5. LOLOS! Login user
        auth()->login($accessToken->tokenable);

        return $next($request);
    }
}