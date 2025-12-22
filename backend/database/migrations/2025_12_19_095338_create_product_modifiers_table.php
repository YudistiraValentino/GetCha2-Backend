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
    Schema::create('product_modifiers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->string('name'); // Contoh: "Sugar Level"
        $table->boolean('is_required')->default(false); // Wajib pilih atau tidak
        $table->json('options'); // Simpan opsi: [{"label":"Less", "price":0}, {"label":"Extra", "price":2000}]
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_modifiers');
    }
};
