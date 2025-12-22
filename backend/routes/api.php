<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- CONTROLLERS UNTUK USER / PUBLIC ---
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController; 
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PromoController;

// --- CONTROLLERS UNTUK ADMIN (Diberi Alias agar tidak bentrok) ---
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PromoController as AdminPromoController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\FloorPlanController as AdminMapController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController; // 👈 NEW: Import Auth Admin

// Import Model
use App\Models\FloorPlan;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==========================================
// 🔓 1. PUBLIC ROUTES (User & Guest)
// ==========================================

Route::get('/menu', [MenuController::class, 'index']);
Route::get('/menu/{id}', [MenuController::class, 'show']);
Route::get('/new-arrivals', [MenuController::class, 'getNewArrivals']);

Route::post('/checkout', [CheckoutController::class, 'store']);

// Auth User Biasa
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 👇 NEW: Auth Admin (Login Khusus Admin)
Route::post('/admin/login', [AdminAuthController::class, 'login']);

Route::get('/promos', [PromoController::class, 'index']); 
Route::post('/promos/apply', [PromoController::class, 'apply']); 

// Active Map (Untuk Booking Customer)
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
// 🔒 2. PROTECTED ROUTES (Harus Login - User & Admin)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // Get User Data (Dipakai User & Admin untuk cek validitas token)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Logout User Biasa
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // 👇 NEW: Logout Admin
    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);

    // User Features
    Route::get('/my-orders', [OrderController::class, 'index']);
    Route::put('/profile/update', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
});


// ==========================================
// 👑 3. ADMIN API ROUTES (New Next.js Admin)
// ==========================================
// Semua route ini otomatis ada prefix: /api/admin/...

Route::prefix('admin')->group(function () {
    
    // --- PRODUK (CRUD Lengkap) ---
    Route::apiResource('products', AdminProductController::class);

    // --- ORDERS ---
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
    Route::put('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
    
    // --- PROMOS / DEALS ---
    Route::apiResource('promos', AdminPromoController::class);

    // --- CUSTOMERS / USERS ---
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{id}/stats', [AdminUserController::class, 'getUserStats']);
    Route::post('/users/{id}/points', [AdminUserController::class, 'updatePoints']);

    // --- MAP MANAGER ---
    Route::get('/maps', [AdminMapController::class, 'index']);
    Route::post('/maps', [AdminMapController::class, 'store']);
    Route::post('/maps/{id}/activate', [AdminMapController::class, 'activate']);
    Route::delete('/maps/{id}', [AdminMapController::class, 'destroy']);

});