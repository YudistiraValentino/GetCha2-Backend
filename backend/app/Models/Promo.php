<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use HasFactory;

    // 👇 TAMBAHKAN INI (Daftar kolom yang boleh diisi)
    protected $fillable = [
        'title',
        'code',
        'type',
        'discount_amount',
        'description',
        'image',
        'start_date',
        'end_date',
        'is_active',
        'color' // Opsional, jika ada di database
    ];
}