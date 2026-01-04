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
        $token = null;

        // 1. Cek Header (Laravel Way)
        $token = $request->bearerToken();

        // 2. 🔥 Cek Header (PHP Native Way - Jaga-jaga kalau Laravel gagal baca)
        if (!$token && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                $token = $matches[1];
            }
        }

        // 3. 🔥 Cek URL Query String (PHP Native Way - ANTI GAGAL)
        // Kita ambil langsung dari $_GET['token']. Ini tidak mungkin salah.
        if (!$token && isset($_GET['token'])) {
            $token = $_GET['token'];
        }

        // 4. Cek Body (PHP Native Way)
        if (!$token && isset($_POST['token'])) {
            $token = $_POST['token'];
        }

        // 5. DIAGNOSA TERAKHIR: Kalau masih kosong, kita nyerah.
        if (!$token) {
            // Debugging: Kita kirim balik apa yang server terima biar kelihatan salahnya dimana
            return response()->json([
                'success' => false,
                'message' => 'Gagal: Unauthenticated. Token tidak ditemukan di Header, Body, maupun URL.',
                'debug_info' => [
                    'php_get' => $_GET, // Lihat apa isi URL query
                    'php_post_keys' => array_keys($_POST), // Lihat apa isi body
                    'auth_header' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'NULL'
                ]
            ], 401);
        }

        // 6. Cek Validitas Token di Database
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken || !$accessToken->tokenable) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Token Invalid (Tidak ada di database).',
                'sent_token' => $token // Balikin token yang dikirim biar kamu bisa cek
            ], 401);
        }

        // 7. LOLOS! Login user manual
        auth()->login($accessToken->tokenable);

        return $next($request);
    }
}