<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Cek dulu, kalau kolom 'role' BELUM ada, baru kita buat
            if (!Schema::hasColumn('users', 'role')) {
                // Kita pakai string biasa dulu biar aman & kompatibel
                $table->string('role')->default('user')->after('email'); 
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};