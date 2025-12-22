<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        // Data dari Frontend: items[], type ('dine_in'/'take_away'), seat_id (optional), guest_name (optional)
        
        try {
            DB::beginTransaction();

            // 1. Cek User Login (Otomatis dari Token Sanctum)
            $user = auth('sanctum')->user(); 
            
            // Nama Customer: Kalau login pakai nama user, kalau tidak pakai "Guest" atau inputan
            $customerName = $user ? $user->name : ($request->guest_name ?? 'Guest');

            // 2. Hitung Total Keseluruhan Server-Side (Biar aman)
            $totalPrice = 0;
            foreach($request->items as $item) {
                $totalPrice += ($item['price'] * $item['quantity']);
            }
            // Tambah pajak 11%
            $tax = $totalPrice * 0.11;
            $grandTotal = $totalPrice + $tax;

            // 3. Buat Order Header
            $order = Order::create([
                // 👇 PERBAIKAN UTAMA: Simpan User ID jika login
                'user_id' => $user ? $user->id : null, 

                'order_number' => 'ORD-' . date('YmdHis') . '-' . rand(100,999), 
                'customer_name' => $customerName, 
                'order_type' => $request->type, // 'dine_in' atau 'take_away'
                'table_number' => $request->type == 'dine_in' ? $request->seat_id : null, 
                'total_price' => $grandTotal,
                'status' => 'pending',
                'payment_status' => 'unpaid'
            ]);

            // 4. Buat Order Items (Looping)
            foreach($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'product_name' => $item['name'], // Pastikan di DB kolomnya 'product_name' (sesuai diskusi kita tadi)
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'], 
                    'subtotal' => $item['price'] * $item['quantity'],
                    'variants' => $item['selectedVariant'] ?? null, 
                    'modifiers' => $item['selectedModifiers'] ?? null, 
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat!',
                'order_number' => $order->order_number
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal checkout: ' . $e->getMessage()
            ], 500);
        }
    }
}