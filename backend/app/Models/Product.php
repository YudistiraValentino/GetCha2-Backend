<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str; // 👈 Import Library String

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    // 👇 Fungsi 'boot' ini akan jalan otomatis setiap kali Model dipanggil
    protected static function boot()
    {
        parent::boot();

        // Event 'creating': Sebelum data disimpan ke DB, jalankan ini:
        static::creating(function ($product) {
            // Jika slug belum diisi manual, buatkan otomatis dari name
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });

        // (Opsional) Event 'updating': Kalau nama diubah, slug ikut berubah
        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    // Relasi ke Kategori
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relasi ke Varian
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Relasi ke Modifier
    public function modifiers()
    {
        return $this->hasMany(ProductModifier::class);
    }
}