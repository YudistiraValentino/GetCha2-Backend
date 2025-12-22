<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@getcha.com',       // Email untuk login
            'password' => Hash::make('password'), // Password: password
            'role' => 'admin',                    // 👈 Set sebagai ADMIN
            'points' => 0
        ]);

        echo "Akun Admin Berhasil Dibuat!\n";
        echo "Email: admin@getcha.com\n";
        echo "Pass: password\n";
    }
}