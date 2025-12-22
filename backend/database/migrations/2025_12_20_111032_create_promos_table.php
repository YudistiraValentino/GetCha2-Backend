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
    Schema::create('promos', function (Blueprint $table) {
        $table->id();
        $table->string('title'); // Judul Promo
        $table->string('code')->unique(); // Kode Unik (misal: DISC50)
        $table->enum('type', ['fixed', 'percent']); // Tipe: Potongan Harga Tetap / Persen
        $table->integer('discount_amount'); // Nilai diskon
        $table->text('description')->nullable();
        $table->string('image')->nullable(); // URL Gambar
        $table->string('color')->default('from-blue-500 to-cyan-500'); // Warna Gradient Tailwind
        $table->date('start_date');
        $table->date('end_date');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
