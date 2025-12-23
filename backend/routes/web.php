<?php

use Illuminate\Support\Facades\Route;
use App\Models\User; 
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; 
use Illuminate\Database\Schema\Blueprint; // 👈 Import ini penting buat bikin tabel

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

// 🔓 GUEST ROUTES
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 🔒 PROTECTED ROUTES
Route::middleware(['auth', 'is_admin'])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/', function () {
        return redirect()->route('admin.orders.index');
    })->name('dashboard');

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
// 🛠️ EMERGENCY ROUTE: CREATE FLOOR PLANS TABLE
// ==========================================
Route::get('/fix-users-table', function () {
    $status = [];

    // 1. Cek & Buat Kolom 'username'
    if (!Schema::hasColumn('users', 'username')) {
        DB::statement("ALTER TABLE users ADD COLUMN username VARCHAR(255) NULL AFTER name");
        // Isi username kosong dengan data dari email supaya tidak error
        DB::statement("UPDATE users SET username = CONCAT(SUBSTRING_INDEX(email, '@', 1), FLOOR(RAND() * 1000)) WHERE username IS NULL");
        $status[] = "✅ Kolom 'username' BERHASIL ditambahkan.";
    } else {
        $status[] = "ℹ️ Kolom 'username' sudah ada.";
    }

    // 2. Cek & Buat Kolom 'points'
    if (!Schema::hasColumn('users', 'points')) {
        DB::statement("ALTER TABLE users ADD COLUMN points INT DEFAULT 0 AFTER role");
        $status[] = "✅ Kolom 'points' BERHASIL ditambahkan.";
    } else {
        $status[] = "ℹ️ Kolom 'points' sudah ada.";
    }

    // 3. Cek & Buat Kolom 'role' (Just in case)
    if (!Schema::hasColumn('users', 'role')) {
        DB::statement("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'user' AFTER email");
        $status[] = "✅ Kolom 'role' BERHASIL ditambahkan.";
    } else {
        $status[] = "ℹ️ Kolom 'role' sudah ada.";
    }

    return implode('<br>', $status) . "<br><br>👉 <b>Selesai! Sekarang coba Add User lagi di Admin Panel.</b>";
});


// Route Darurat buat maksa kolom user_id muncul
Route::get('/force-migrate-user-id', function () {
    try {
        if (!Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('set null');
            });
            return "✅ Kolom 'user_id' BERHASIL dipaksa masuk ke tabel orders.";
        }
        return "ℹ️ Kolom 'user_id' sebenarnya sudah ada di database.";
    } catch (\Exception $e) {
        return "❌ Error: " . $e->getMessage();
    }
});