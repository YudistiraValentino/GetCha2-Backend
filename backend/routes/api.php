<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\FloorPlan;

// --- CONTROLLERS ---
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
| API Routes (SECURE ADMIN VERSION)
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

// 🔥 ADMIN LOGIN (WAJIB DITARUH DILUAR MIDDLEWARE)
// Ini pintunya. Kalau ini dikunci, admin gak bisa masuk buat ambil token.
Route::post('/admin/login', [AdminAuthController::class, 'login']);

// Active Map (Public View)
Route::get('/active-map', function () {
    $map = FloorPlan::where('is_active', true)->first();
    if (!$map) return response()->json(['success' => false, 'message' => 'No active map']);
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
// 🔒 2. USER ROUTES (Wajib Login)
// ==========================================
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
// 👑 3. ADMIN ROUTES (DIKUNCI / WAJIB LOGIN)
// ==========================================
// Semua route di bawah ini butuh Token 'Bearer ...'
// Kalau token salah/kosong, server akan menolak (401).

Route::middleware(['auth:sanctum', 'is_admin'])->prefix('admin')->group(function () {
    
    // Cek Token
    Route::get('/check', function() {
        return response()->json(['status' => 'OK', 'user' => auth()->user()]);
    });

    Route::post('/logout', [AdminAuthController::class, 'logout']);

    // --- PRODUCTS ---
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

    // --- MAPS ---
    Route::get('/maps', [AdminMapController::class, 'index']);
    Route::post('/maps', [AdminMapController::class, 'store']); 
    Route::post('/maps/{id}/activate', [AdminMapController::class, 'activate']);
    Route::delete('/maps/{id}', [AdminMapController::class, 'destroy']);
});