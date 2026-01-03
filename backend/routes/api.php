<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// --- CONTROLLERS PUBLIC ---
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\AuthController; // Auth User
use App\Http\Controllers\Api\OrderController; 
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PromoController;

// --- CONTROLLERS ADMIN ---
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PromoController as AdminPromoController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\FloorPlanController as AdminMapController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController; // Auth Admin

use App\Models\FloorPlan;

/*
|--------------------------------------------------------------------------
| API Routes (FULL SECURE VERSION)
|--------------------------------------------------------------------------
| URL Prefix: /api
*/

// ==========================================
// 🔓 1. PUBLIC ROUTES (Bebas Akses)
// ==========================================

// Menu & Checkout
Route::get('/menu', [MenuController::class, 'index']);
Route::get('/menu/{id}', [MenuController::class, 'show']);
Route::get('/new-arrivals', [MenuController::class, 'getNewArrivals']);
Route::post('/checkout', [CheckoutController::class, 'store']);

// Promos (Public view)
Route::get('/promos', [PromoController::class, 'index']); 
Route::post('/promos/apply', [PromoController::class, 'apply']); 

// Kategori (Buat Dropdown Product)
Route::get('/categories', function () {
    return response()->json(DB::table('categories')->select('id', 'name')->get());
});

// AUTH
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']); // Login User

// 🔥 LOGIN ADMIN (PENTING: Frontend Admin nembak kesini)
Route::post('/admin/login', [AdminAuthController::class, 'login']); 

// Active Map (Booking - Public)
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
// 🔒 2. USER ROUTES (Wajib Login User)
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
// 👑 3. ADMIN ROUTES (Wajib Login + Wajib Admin)
// ==========================================
// Middleware 'auth:sanctum' memastikan Token Valid.
// Middleware 'is_admin' memastikan Role = admin.

Route::middleware(['auth:sanctum', 'is_admin'])->prefix('admin')->group(function () {
    
    // Cek Token (Ping)
    Route::get('/check', function() {
        return response()->json(['message' => 'Admin Token Valid', 'user' => auth()->user()]);
    });

    Route::post('/logout', [AdminAuthController::class, 'logout']);

    // --- PRODUK ---
    // Menggunakan apiResource (index, store, show, update, destroy)
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

    // --- MAPS (SOLUSI UPLOAD ERROR) ---
    // Endpoint: /api/admin/maps
    Route::get('/maps', [AdminMapController::class, 'index']);
    Route::post('/maps', [AdminMapController::class, 'store']); // 👈 Upload masuk sini
    Route::post('/maps/{id}/activate', [AdminMapController::class, 'activate']);
    Route::delete('/maps/{id}', [AdminMapController::class, 'destroy']);

});