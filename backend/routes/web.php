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
Route::get('/fix-promos', function () {
    // 1. Cek apakah tabel 'promos' ada?
    if (!Schema::hasTable('promos')) {
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->nullable(); // Kita buat nullable dulu biar aman
            $table->string('code')->unique();
            $table->enum('type', ['fixed', 'percent']);
            $table->decimal('discount_amount', 10, 2);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        return "✅ SUKSES! Tabel 'promos' baru saja dibuat.";
    }

    // 2. Jika tabel ada, Cek apakah kolom 'slug' ada?
    if (!Schema::hasColumn('promos', 'slug')) {
        DB::statement("ALTER TABLE promos ADD COLUMN slug VARCHAR(255) NULL AFTER title");
        return "✅ SUKSES! Kolom 'slug' berhasil ditambahkan ke tabel promos.";
    }

    return "ℹ️ Tabel & Kolom Promo aman. Masalahnya mungkin di Controller.";
});