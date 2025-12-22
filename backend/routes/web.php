<?php

use Illuminate\Support\Facades\Route;
// Import Controller Admin
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PromoController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AuthController; // 👈 Controller Login Admin
use App\Http\Controllers\Admin\FloorPlanController;

/*
|--------------------------------------------------------------------------
| Web Routes (Admin Panel)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    // Redirect root ke login admin saja biar gak bingung
    return redirect()->route('login');
});

// ==========================================
// 🔓 GUEST ROUTES (Login Admin)
// ==========================================
// Route ini yang dicari Laravel saat error "Route [login] not defined"
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// ==========================================
// 🔒 PROTECTED ROUTES (Harus Login Admin)
// ==========================================
// Kita pakai middleware 'auth' (session biasa), bukan 'auth:sanctum'
Route::middleware(['auth', 'is_admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard (Bisa diarahkan ke orders atau products)
    Route::get('/', function () {
        return redirect()->route('admin.orders.index');
    })->name('dashboard');

    // 1. MODULE PRODUCTS
    Route::resource('products', ProductController::class);

    // 2. MODULE ORDERS
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::get('/orders/{id}/print', [OrderController::class, 'printStruk'])->name('orders.print');

    // 3. MODULE DEALS / PROMO
    Route::resource('promos', PromoController::class);

    // 4. MODULE CUSTOMERS / USERS
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users/{id}/points', [UserController::class, 'updatePoints'])->name('users.updatePoints');
    Route::get('/users/{id}/stats', [UserController::class, 'getUserStats'])->name('users.stats');

    // MODULE MAPS / FLOOR PLAN
    Route::resource('maps', FloorPlanController::class)->only(['index', 'store', 'destroy']);
    Route::post('/maps/{id}/activate', [FloorPlanController::class, 'activate'])->name('maps.activate');

});