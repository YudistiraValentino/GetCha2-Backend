<?php

use Illuminate\Support\Facades\Route;
use App\Models\User; 
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // 👈 KITA PAKAI INI SEKARANG (SQL MURNI)

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

    // MODULES
    Route::resource('products', ProductController::class);
    
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::get('/orders/{id}/print', [OrderController::class, 'printStruk'])->name('orders.print');

    Route::resource('promos', PromoController::class);

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users/{id}/points', [UserController::class, 'updatePoints'])->name('users.updatePoints');
    Route::get('/users/{id}/stats', [UserController::class, 'getUserStats'])->name('users.stats');

    Route::resource('maps', FloorPlanController::class)->only(['index', 'store', 'destroy']);
    Route::post('/maps/{id}/activate', [FloorPlanController::class, 'activate'])->name('maps.activate');

});

// ==========================================
// 🛠️ JURUS PAMUNGKAS: FIX ROLE (RAW SQL)
// ==========================================
Route::get('/fix-role', function () {
    
    // 1. Cek Kolom pakai Schema (aman)
    if (!Schema::hasColumn('users', 'role')) {
        // 2. Buat Kolom Pakai RAW SQL (Anti Error Type Hint)
        // Kita perintahkan MySQL langsung: "Woi, tambah kolom role dong!"
        DB::statement("ALTER TABLE users ADD COLUMN role VARCHAR(255) DEFAULT 'user' AFTER email");
        $status = "✅ Kolom 'role' BERHASIL dibuat manual via SQL.";
    } else {
        $status = "ℹ️ Kolom 'role' sudah ada.";
    }

    // 3. Cari User & Update
    $email = 'yudis@getcha.com'; 
    // Kita update pakai query builder biasa biar gak kena validasi model user
    $affected = DB::table('users')
        ->where('email', $email)
        ->update(['role' => 'admin']);

    if ($affected) {
        return "$status <br> ✅ SUKSES! User $email sekarang jadi ADMIN. <br><br> <a href='/login'>KLIK DISINI UNTUK LOGIN ADMIN</a>";
    } else {
        return "$status <br> ❌ User $email tidak ditemukan. Cek lagi emailnya.";
    }
});