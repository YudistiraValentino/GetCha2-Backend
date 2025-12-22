<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Kategori
        $coffee = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $pastry = Category::create(['name' => 'Pastry', 'slug' => 'pastry']);

        // 2. Buat Produk Contoh (Sesuai gambar kamu: TES1.png & TES2.png)
        Product::create([
            'category_id' => $coffee->id,
            'name' => 'GetCha Signature Latte',
            'slug' => 'getcha-signature-latte',
            'description' => 'Kopi susu gula aren dengan resep rahasia.',
            'price' => 25000,
            'image' => '/Image/TES1.png', // <--- MENGARAH KE PUBLIC FOLDER NEXT.JS
            'nutritional_info' => json_encode(['Calories' => '120 kcal']),
            'is_promo' => true,
        ]);

        Product::create([
            'category_id' => $pastry->id,
            'name' => 'Butter Croissant',
            'slug' => 'butter-croissant',
            'description' => 'Croissant renyah dengan full butter premium.',
            'price' => 18000,
            'image' => '/Image/TES2.png', // <--- MENGARAH KE PUBLIC FOLDER NEXT.JS
            'nutritional_info' => json_encode(['Calories' => '240 kcal']),
            'is_promo' => false,
        ]);
    }
}