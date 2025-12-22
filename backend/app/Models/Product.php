<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relasi ke Kategori
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relasi ke Varian (Size, dll)
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Relasi ke Modifier (Gula, Toping, dll)
    public function modifiers()
    {
        return $this->hasMany(ProductModifier::class);
    }
}