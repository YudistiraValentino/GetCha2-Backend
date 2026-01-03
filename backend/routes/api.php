<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\FloorPlan;

// --- CONTROLLERS PUBLIC ---
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
| API Routes (FULL EMERGENCY OPEN ACCESS)
|--------------------------------------------------------------------------
*/

// ==========================================
// 🔓 1. PUBLIC ROUTES (Customer)
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

// ADMIN LOGIN (Tetap ada route-nya meski nanti gak dipake validasinya)
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
// 🔓 2. ADMIN ROUTES (SEMUA DIBUKA TOTAL)
// ==========================================
// Semua fitur Admin ditaruh disini TANPA Middleware Auth
// Biar data Orders, User, Promo, dll MUNCUL SEMUA.

Route::prefix('admin')->group(function () {
    
    // --- MAPS ---
    Route::get('/maps', [AdminMapController::class, 'index']);
    Route::post('/maps', [AdminMapController::class, 'store']); 
    Route::post('/maps/{id}/activate', [AdminMapController::class, 'activate']);
    Route::delete('/maps/{id}', [AdminMapController::class, 'destroy']);

    // --- PRODUCTS ---
    Route::apiResource('products', AdminProductController::class);

    // --- ORDERS (Biar list orderan muncul) ---
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
    Route::put('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
    
    // --- PROMOS (Biar bisa bikin kode promo) ---
    Route::apiResource('promos', AdminPromoController::class);

    // --- USERS (Biar list customer muncul) ---
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{id}/stats', [AdminUserController::class, 'getUserStats']);
    Route::post('/users/{id}/points', [AdminUserController::class, 'updatePoints']);
    
    // Cek status (Dummy)
    Route::get('/check', function() {
        return response()->json(['status' => 'OPEN MODE', 'message' => 'Admin Security Disabled']);
    });
});


// ==========================================
// 🔒 3. USER ROUTES (Customer Profile Tetap Aman)
// ==========================================
// Khusus Customer Profile tetap kita kunci biar user gak bisa edit profile orang lain
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/my-orders', [OrderController::class, 'index']);
    Route::put('/profile/update', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
});