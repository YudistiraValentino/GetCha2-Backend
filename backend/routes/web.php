<?php

use Illuminate\Support\Facades\Route;
use App\Models\User; 
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Hash; 
use Illuminate\Database\Schema\Blueprint;

/*
|--------------------------------------------------------------------------
| Web Routes (Emergency Tools Only)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json([
        'status' => 'Backend API is Running', 
        'info' => 'Use /api endpoints for application data.'
    ]);
});

// ==========================================
// 🛡️ JARING PENGAMAN (SOLUSI ERROR 500 / Route Not Found)
// ==========================================
// Laravel butuh rute bernama 'login' untuk melempar user yang belum auth.
// Kita buat rute ini mengembalikan JSON 401 supaya tidak crash.

Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'Unauthenticated. Token Invalid or Expired. Please login via API.'
    ], 401);
})->name('login'); // 👈 NAMA INI WAJIB ADA


// ==========================================
// 🛠️ EMERGENCY TOOLS (DANGER ZONE)
// ==========================================

/**
 * 0. 🔥 FORCE CREATE ADMIN
 */
Route::get('/force-create-admin', function () {
    $email = 'yudis@getcha.com';
    $pass = 'password123';
    
    try {
        User::where('email', $email)->delete();

        $user = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => $email,
            'password' => Hash::make($pass),
            'role' => 'admin',
            'points' => 0
        ]);
        
        $token = $user->createToken('emergency-token')->plainTextToken;

        return response()->json([
            'message' => '✅ Admin User Direset!',
            'credentials' => ['email' => $email, 'password' => $pass],
            'test_token' => $token
        ]);
    } catch (\Exception $e) {
        return "❌ Error: " . $e->getMessage();
    }
});

/**
 * 1. FIX USER TABLE
 */
Route::get('/fix-users-table', function () {
    $status = [];
    if (!Schema::hasColumn('users', 'username')) {
        DB::statement("ALTER TABLE users ADD COLUMN username VARCHAR(255) NULL AFTER name");
        DB::statement("UPDATE users SET username = CONCAT(SUBSTRING_INDEX(email, '@', 1), FLOOR(RAND() * 1000)) WHERE username IS NULL");
        $status[] = "✅ Kolom 'username' ditambahkan.";
    }
    if (!Schema::hasColumn('users', 'points')) {
        DB::statement("ALTER TABLE users ADD COLUMN points INT DEFAULT 0 AFTER role");
        $status[] = "✅ Kolom 'points' ditambahkan.";
    }
    if (!Schema::hasColumn('users', 'role')) {
        DB::statement("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'user' AFTER email");
        $status[] = "✅ Kolom 'role' ditambahkan.";
    }
    return count($status) > 0 ? implode('<br>', $status) : "ℹ️ Tabel User sudah up-to-date.";
});

/**
 * 2. MEGA RESET ORDERS
 */
Route::get('/reset-orders-table', function () {
    try {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::enableForeignKeyConstraints();

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

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->integer('product_id'); 
            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2); 
            $table->decimal('subtotal', 15, 2);   
            $table->string('variants')->nullable();
            $table->json('modifiers')->nullable(); 
            $table->timestamps();
        });

        return "✅ MEGA RESET V2 SUKSES!";
    } catch (\Exception $e) {
        return "❌ Gagal: " . $e->getMessage();
    }
});

/**
 * 3. FIX PROMOS
 */
Route::get('/fix-promos', function () {
    if (!Schema::hasColumn('promos', 'slug')) {
        DB::statement("ALTER TABLE promos ADD COLUMN slug VARCHAR(255) NULL AFTER title");
        return "✅ Kolom 'slug' ditambahkan.";
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

/**
 * 5. FIX STORAGE
 */
Route::get('/fix-storage', function () {
    try {
        if (is_link(public_path('storage'))) {
            app('files')->delete(public_path('storage'));
        }
        app('files')->link(storage_path('app/public'), public_path('storage'));
        return "✅ Storage Link Fixed!";
    } catch (\Exception $e) {
        return "❌ Error: " . $e->getMessage();
    }
});

/**
 * 6. FIX CATEGORIES
 */
Route::get('/fix-categories-db', function () {
    if (!Schema::hasTable('categories')) return "❌ Tabel categories belum ada.";
    if (DB::table('categories')->count() == 0) {
        $now = now();
        DB::table('categories')->insert([
            ['name' => 'Coffee', 'slug' => 'coffee', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Non-Coffee', 'slug' => 'non-coffee', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Food', 'slug' => 'food', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Snack', 'slug' => 'snack', 'created_at' => $now, 'updated_at' => $now],
        ]);
        return "✅ 4 Kategori Default ditambahkan.";
    }
    return response()->json(DB::table('categories')->get());
});