<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. BUAT TABEL ORDERS DULU (INDUK)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name')->default('Guest');
            $table->decimal('total_price', 12, 2);
            $table->enum('status', ['pending', 'paid', 'completed'])->default('pending');
            $table->timestamps();
        });

        // 2. BARU BUAT TABEL ITEMS (ANAK)
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            // Sekarang aman karena tabel orders pasti sudah ada di atas
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            
            $table->foreignId('product_id'); 
            $table->string('product_name');
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};