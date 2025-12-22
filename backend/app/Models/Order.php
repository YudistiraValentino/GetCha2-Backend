<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // 👇 KITA GANTI GUARDED JADI FILLABLE (BIAR LEBIH AMAN & JELAS)
    protected $fillable = [
        'user_id',        // 👈 Penting: Supaya ID user bisa disimpan
        'order_number',
        'customer_name',
        'order_type',     // dine_in / take_away
        'table_number',
        'total_price',
        'status',         // pending, processing, completed, cancelled
        'payment_status', // unpaid, paid
        'payment_method'  // qris, cash, dll
    ];

    // Relasi: Order milik 1 User (Opsional, bisa null kalau guest)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: Order punya banyak Item
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // 🔥 LOGIC OTOMATIS NOMOR ORDER
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            // Jika order_number sudah diisi manual (misal dari Controller), skip logic ini
            if (!empty($order->order_number)) {
                return;
            }

            // Format: ORD-20251219-0001
            $prefix = 'ORD-' . date('Ymd') . '-';
            
            // Cari nomor terakhir hari ini
            $lastOrder = self::where('order_number', 'like', $prefix . '%')
                             ->orderBy('id', 'desc')
                             ->first();

            if ($lastOrder) {
                // Ambil 4 angka belakang, tambah 1
                // Contoh: ORD-20251219-0005 -> ambil 0005 -> jadi 6
                $lastNumber = intval(substr($lastOrder->order_number, -4));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            // Pad dengan nol (misal 1 jadi 0001)
            $order->order_number = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        });
    }
}