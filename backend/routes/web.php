<?php

use Illuminate\Support\Facades\Route;
use App\Models\User; // 👈 PENTING: Tambahan biar bisa edit user

// Import Controller Admin
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PromoController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AuthController;
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
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// ==========================================
// 🔒 PROTECTED ROUTES (Harus Login Admin)
// ==========================================
Route::middleware(['auth', 'is_admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
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

// ==========================================
// 🛠️ EMERGENCY ROUTE: FIX ROLE ADMIN
// ==========================================
Route::get('/fix-role', function () {
    // 1. Cari User
    $email = 'yudis@getcha.com'; 
    $user = User::where('email', $email)->first();

    if (!$user) {
        return "User dengan email $email tidak ditemukan!";
    }

    // 2. Set Role jadi admin
    $user->role = 'admin'; 
    $user->save();

    return "SUKSES! User $email sekarang memiliki Role: " . $user->role . ". Silakan Login kembali di Frontend.";
});