<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str; // 👈 1. Wajib Import ini

class Promo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug', // 👈 2. Tambahkan ini biar aman
        'code',
        'type',
        'discount_amount',
        'description',
        'image',
        'start_date',
        'end_date',
        'is_active',
        'color'
    ];

    // 👇 3. Fungsi Ajaib Generate Slug Otomatis
    protected static function boot()
    {
        parent::boot();

        // Saat promo akan dibuat (creating)
        static::creating(function ($promo) {
            // Jika slug kosong, ambil dari title
            if (empty($promo->slug)) {
                $promo->slug = Str::slug($promo->title);
            }
        });

        // (Opsional) Saat promo diupdate, jika title berubah, slug ikut berubah
        static::updating(function ($promo) {
            if ($promo->isDirty('title') && empty($promo->slug)) {
                $promo->slug = Str::slug($promo->title);
            }
        });
    }
}