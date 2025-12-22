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
    Schema::create('floor_plans', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Nama denah, misal "Lantai 1"
        $table->string('image_path'); // Lokasi file SVG
        $table->boolean('is_active')->default(false); // Map mana yang dipakai sekarang
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('floor_plans');
}
};
