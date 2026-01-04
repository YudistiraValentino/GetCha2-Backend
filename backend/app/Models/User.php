<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // ✅ Wajib ada

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // ✅ Wajib dipanggil

    protected $fillable = [
        'name',
        'username', // ✅ Kolom baru
        'email',
        'password',
        'role',    
        'points',
        'otp',              // 👈 Tambah
        'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}