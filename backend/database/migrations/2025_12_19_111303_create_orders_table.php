<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('order_number')->unique(); // ORD-20251219-0001
        $table->string('customer_name')->nullable(); // Opsional
        $table->enum('order_type', ['dine_in', 'take_away']);
        $table->string('table_number')->nullable(); // Null jika take away
        $table->decimal('total_price', 15, 2);
        
        // Status Pesanan
        $table->enum('status', ['pending', 'confirmed', 'processing', 'ready', 'completed', 'cancelled'])->default('pending');
        
        // Status Bayar
        $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
        $table->string('payment_method')->nullable(); // Cash, QRIS, dll
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
