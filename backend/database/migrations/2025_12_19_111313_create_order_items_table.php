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
    Schema::create('order_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained()->onDelete('cascade');
        $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null'); // Kalau produk dihapus, history aman
        
        // Kita simpan Nama & Harga sbg "Snapshot". 
        // Jadi kalau Admin ubah harga menu besok, order hari ini harganya gak berubah.
        $table->string('product_name');
        $table->integer('quantity');
        $table->decimal('unit_price', 15, 2); // Harga satuan setelah ditambah modifier
        $table->decimal('subtotal', 15, 2);   // unit_price * quantity
        
        // Kolom Sakti: JSON
        $table->json('variants')->nullable();  // Simpan: "Large"
        $table->json('modifiers')->nullable(); // Simpan: ["Less Sugar", "Extra Shot"]
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
