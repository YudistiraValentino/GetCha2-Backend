<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // --- REGISTER ---
    public function register(Request $request)
    {
        // 1. Validasi Input
        // HAPUS 'username' dan 'confirmed' agar tidak rewel
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Auto-Generate Username
        // Ambil nama depan email, tambah angka acak 3 digit
        $usernameBase = explode('@', $request->email)[0];
        $username = $usernameBase . rand(100, 999);

        // 3. Buat User Baru
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $username, // 👈 Username dibuat otomatis disini
            'password' => Hash::make($request->password),
            'role' => 'user', // 👈 Default Role Customer
            'points' => 0,    // 👈 Default Points 0
        ]);

        // 4. Buat Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    // --- LOGIN ---
    public function login(Request $request)
    {
        // 1. Cek apakah user login pakai Email atau Username
        // Input dari frontend tetap ditangkap sebagai 'username' (sesuai kodingan frontend mu)
        $loginField = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // 2. Cek Kredensial (Password benar/salah)
        if (!Auth::attempt([$loginField => $request->username, 'password' => $request->password])) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau Password salah.'
            ], 401);
        }

        // 3. Ambil Data User & Buat Token
        $user = User::where($loginField, $request->username)->firstOrFail();
        
        // Hapus token lama biar gak numpuk (opsional)
        $user->tokens()->delete();
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login success',
            'user' => $user,
            'token' => $token
        ]);
    }

    // --- LOGOUT ---
    public function logout(Request $request)
    {
        // Hapus token yang sedang dipakai
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}