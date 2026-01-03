<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\FloorPlan;
use Laravel\Sanctum\PersonalAccessToken; // 👈 Penting buat Manual Check

// --- CONTROLLERS PUBLIC / USER ---
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController; 
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PromoController;

// --- CONTROLLERS ADMIN ---
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PromoController as AdminPromoController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\FloorPlanController as AdminMapController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;

/*
|--------------------------------------------------------------------------
| API Routes (KOMPLIT & AMAN)
|--------------------------------------------------------------------------
*/

// ==========================================
// 🔓 1. PUBLIC ROUTES (Bebas Akses)
// ==========================================

Route::get('/menu', [MenuController::class, 'index']);
Route::get('/menu/{id}', [MenuController::class, 'show']);
Route::get('/new-arrivals', [MenuController::class, 'getNewArrivals']);
Route::post('/checkout', [CheckoutController::class, 'store']);
Route::get('/promos', [PromoController::class, 'index']); 
Route::post('/promos/apply', [PromoController::class, 'apply']); 

// AUTH Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 🔥 ADMIN LOGIN (Penting buat Frontend Admin)
Route::post('/admin/login', [AdminAuthController::class, 'login']);

// Active Map (Booking - Public View)
Route::get('/active-map', function () {
    $map = FloorPlan::where('is_active', true)->first();
    if (!$map) return response()->json(['success' => false, 'message' => 'No active map']);
    
    // Logic ambil gambar/SVG
    $relativePath = ltrim($map->image_path, '/'); 
    $fullPath = public_path($relativePath);
    $svgContent = file_exists($fullPath) ? file_get_contents($fullPath) : null;

    return response()->json([
        'success' => true,
        'data' => [
            'name' => $map->name,
            'url' => asset($map->image_path), 
            'svg_content' => $svgContent 
        ]
    ]);
});

// ==========================================
// 🔒 2. USER ROUTES (Customer Biasa)
// ==========================================
// Customer tetap pakai auth:sanctum standar
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/my-orders', [OrderController::class, 'index']);
    Route::put('/profile/update', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
});


// ==========================================
// 👑 3. ADMIN ROUTES (MANUAL GUARD)
// ==========================================
// Kita pakai Logic Manual khusus Admin biar gak kena error 401 Gaib

Route::middleware(function ($request, $next) {
    
    // A. Ambil Token dari Header
    $token = $request->bearerToken();
    
    if (!$token) {
        return response()->json(['message' => 'Token Tidak Terbaca di Server'], 401);
    }

    // B. Cari Token di Database Manual
    $accessToken = PersonalAccessToken::findToken($token);

    if (!$accessToken || !$accessToken->tokenable) {
        return response()->json(['message' => 'Token Invalid / Sesi Kadaluarsa'], 401);
    }

    // C. Paksa Login User Terkait
    auth()->login($accessToken->tokenable);

    // D. Cek Apakah Dia Admin?
    if (auth()->user()->role !== 'admin') {
        return response()->json(['message' => 'Akses Ditolak. Anda bukan Admin.'], 403);
    }

    // E. Lanjut ke Controller
    return $next($request);

})->prefix('admin')->group(function () {
    
    // Cek Auth (Ping)
    Route::get('/check', function() {
        return response()->json(['status' => 'OK', 'user' => auth()->user()]);
    });

    Route::post('/logout', [AdminAuthController::class, 'logout']);

    // --- PRODUK ---
    Route::apiResource('products', AdminProductController::class);

    // --- ORDERS ---
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
    Route::put('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
    
    // --- PROMOS ---
    Route::apiResource('promos', AdminPromoController::class);

    // --- USERS ---
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{id}/stats', [AdminUserController::class, 'getUserStats']);
    Route::post('/users/{id}/points', [AdminUserController::class, 'updatePoints']);

    // --- MAPS (SOLUSI FIX UPLOAD) ---
    Route::get('/maps', [AdminMapController::class, 'index']);
    Route::post('/maps', [AdminMapController::class, 'store']); 
    Route::post('/maps/{id}/activate', [AdminMapController::class, 'activate']);
    Route::delete('/maps/{id}', [AdminMapController::class, 'destroy']);

});