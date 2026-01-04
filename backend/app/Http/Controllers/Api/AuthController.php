<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AuthController extends Controller
{
    // =================================================================
    // 1. REGISTER (STEP 1: Buat User & Kirim OTP)
    // =================================================================
    public function register(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'nullable|string|max:255|unique:users', 
            'password' => 'required|string|min:8', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Tentukan Username
        // Gunakan input user jika ada, jika tidak generate otomatis
        if ($request->filled('username')) {
            $username = $request->username;
        } else {
            $usernameBase = explode('@', $request->email)[0];
            $username = $usernameBase . rand(100, 999);
        }

        // 3. Generate Kode OTP 6 Digit
        $otp = rand(100000, 999999);

        // 4. Buat User Baru
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $username, 
            'password' => Hash::make($request->password),
            'role' => 'user', 
            'points' => 0,    
            'otp' => $otp,    
            'otp_expires_at' => Carbon::now()->addMinutes(10), 
            'email_verified_at' => null 
        ]);

        // 5. Kirim Email OTP
        try {
            Mail::raw("Halo {$user->name}, Kode verifikasi GetCha kamu adalah: {$otp}. Berlaku 10 menit.", function ($message) use ($user) {
                $message->to($user->email)->subject('Kode Verifikasi GetCha Coffee');
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'message' => 'Akun dibuat, tapi email gagal terkirim.',
                'debug_error' => $e->getMessage()
            ], 201);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode verifikasi telah dikirim ke email anda.',
            'email' => $user->email 
        ], 201);
    }

    // =================================================================
    // 2. VERIFY OTP (STEP 2: Cek Kode & Login Otomatis)
    // =================================================================
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Data tidak lengkap.'], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Email tidak ditemukan.'], 404);
        }

        // Cek Kode OTP
        if ($user->otp !== $request->otp) {
            return response()->json(['success' => false, 'message' => 'Kode verifikasi salah.'], 400);
        }

        // Cek Kadaluarsa
        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Kode sudah kadaluarsa.'], 400);
        }

        // SUKSES: Verifikasi & Bersihkan OTP
        $user->update([
            'email_verified_at' => Carbon::now(),
            'otp' => null,
            'otp_expires_at' => null
        ]);

        // Auto Login (Buat Token)
        $user->tokens()->delete(); 
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil diverifikasi!',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ]);
    }

    // =================================================================
    // 3. LOGIN (DIPERBAIKI: Bisa Email ATAU Username)
    // =================================================================
    public function login(Request $request)
    {
        // 1. TANGKAP INPUT (Bisa dari key 'username' atau 'email')
        $inputLogin = $request->input('username') ?? $request->input('email');
        $inputPassword = $request->input('password');

        if (!$inputLogin || !$inputPassword) {
             return response()->json([
                 'success' => false, 
                 'message' => 'Username/Email dan Password wajib diisi.'
             ], 400);
        }

        // 2. DETEKSI TIPE: Apakah yang diketik itu Email atau Username?
        // Kalau formatnya email valid, kita anggap dia login pakai email.
        // Kalau bukan format email, kita anggap dia login pakai username.
        $loginType = filter_var($inputLogin, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // 3. COBA LOGIN
        // Auth::attempt akan otomatis mencocokkan password yang di-hash
        if (!Auth::attempt([$loginType => $inputLogin, 'password' => $inputPassword])) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau Password salah.'
            ], 401);
        }

        // 4. AMBIL DATA USER
        $user = User::where($loginType, $inputLogin)->firstOrFail();
        
        // 5. BUAT TOKEN
        $user->tokens()->delete(); // Hapus token lama biar bersih
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login success',
            'user' => $user,
            'token' => $token
        ]);
    }

    // =================================================================
    // 4. LOGOUT
    // =================================================================
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}