<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductModifier extends Model
{
    use HasFactory;
    protected $guarded = [];

    // Ubah JSON di database jadi Array PHP otomatis
    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
    ];
}