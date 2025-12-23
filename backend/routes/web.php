<?php

use Illuminate\Support\Facades\Route;
use App\Models\User; 
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; 
use Illuminate\Database\Schema\Blueprint;

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
// 🛠️ EMERGENCY TOOLS (DANGER ZONE)
// ==========================================

/**
 * 1. FIX USER TABLE
 */
Route::get('/fix-users-table', function () {
    $status = [];
    if (!Schema::hasColumn('users', 'username')) {
        DB::statement("ALTER TABLE users ADD COLUMN username VARCHAR(255) NULL AFTER name");
        DB::statement("UPDATE users SET username = CONCAT(SUBSTRING_INDEX(email, '@', 1), FLOOR(RAND() * 1000)) WHERE username IS NULL");
        $status[] = "✅ Kolom 'username' BERHASIL ditambahkan.";
    }
    if (!Schema::hasColumn('users', 'points')) {
        DB::statement("ALTER TABLE users ADD COLUMN points INT DEFAULT 0 AFTER role");
        $status[] = "✅ Kolom 'points' BERHASIL ditambahkan.";
    }
    if (!Schema::hasColumn('users', 'role')) {
        DB::statement("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'user' AFTER email");
        $status[] = "✅ Kolom 'role' BERHASIL ditambahkan.";
    }
    return count($status) > 0 ? implode('<br>', $status) : "ℹ️ Tabel User sudah up-to-date.";
});

/**
 * 2. MEGA RESET ORDERS (SOLUSI CHECKOUT ERROR)
 * Mengatasi: Unknown column 'user_id', 'order_number', dll.
 */
Route::get('/reset-orders-table', function () {
    try {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::enableForeignKeyConstraints();

        // 1. Tabel Orders (Sudah Oke)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->enum('order_type', ['dine_in', 'take_away']);
            $table->string('table_number')->nullable();
            $table->decimal('total_price', 15, 2);
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Tabel Order Items (Disesuaikan dengan kodingan checkout kamu)
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->integer('product_id'); // 👈 TAMBAHKAN INI
            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2); // 👈 UBAH DARI price KE unit_price
            $table->decimal('subtotal', 15, 2);   // 👈 TAMBAHKAN INI
            $table->string('variants')->nullable();
            $table->json('modifiers')->nullable(); // 👈 TAMBAHKAN INI (Gunakan JSON)
            $table->timestamps();
        });

        return "✅ MEGA RESET V2 SUKSES! Kolom product_id, unit_price, dll sudah ditambahkan.";
    } catch (\Exception $e) {
        return "❌ Gagal: " . $e->getMessage();
    }
});

/**
 * 3. FIX PROMOS (SLUG)
 */
Route::get('/fix-promos', function () {
    if (!Schema::hasColumn('promos', 'slug')) {
        DB::statement("ALTER TABLE promos ADD COLUMN slug VARCHAR(255) NULL AFTER title");
        return "✅ Kolom 'slug' berhasil ditambahkan ke promos.";
    }
    return "ℹ️ Kolom slug sudah ada.";
});

/**
 * 4. FIX MAPS
 */
Route::get('/fix-maps', function () {
    if (!Schema::hasTable('floor_plans')) {
        Schema::create('floor_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image_path');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
        return "✅ Tabel 'floor_plans' dibuat.";
    }
    return "ℹ️ Tabel floor_plans sudah ada.";
});