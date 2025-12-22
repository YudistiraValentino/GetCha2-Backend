<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Fix Error 500: Menangani jika user mengakses /login via Browser (GET)
     */
    public function showLoginForm()
    {
        return response()->json([
            'status' => 'running',
            'message' => 'Backend Server Berjalan! Gunakan POST request untuk Login.',
            'timestamp' => now()
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 1. Debug: Cari User berdasarkan Email saja dulu
        $user = User::where('email', $request->email)->first();

        // Jika user tidak ketemu sama sekali
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak terdaftar di database.'
            ], 401);
        }

        // 2. Debug: Cek Password Manual pakai Hash::check
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email ketemu, TAPI Password Salah! Cek Hash.'
            ], 401);
        }

        // 3. Cek Role
        if ($user->role !== 'admin') {
             return response()->json([
                'success' => false, 
                'message' => 'Login berhasil tapi Anda bukan Admin (Role: ' . $user->role . ')'
            ], 403);
        }

        // 4. Buat Token
        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ]);
    }
    
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        return response()->json(['success' => true, 'message' => 'Logout berhasil']);
    }
}
