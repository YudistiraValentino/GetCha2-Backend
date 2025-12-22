<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PromoController extends Controller
{
    // 1. Ambil Semua Promo Aktif (Untuk Halaman Deals)
    public function index()
    {
        $promos = Promo::where('is_active', true)
            ->whereDate('end_date', '>=', Carbon::today())
            ->get();

        return response()->json([
            'success' => true,
            'data' => $promos
        ]);
    }

    // 2. Validasi Kode Promo (Untuk Halaman Payment)
    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'total_amount' => 'required|numeric'
        ]);

        $promo = Promo::where('code', $request->code)
            ->where('is_active', true)
            ->first();

        // Cek Exist
        if (!$promo) {
            return response()->json(['success' => false, 'message' => 'Kode promo tidak ditemukan.'], 404);
        }

        // Cek Tanggal
        $now = Carbon::now();
        if ($now < $promo->start_date || $now > $promo->end_date) {
            return response()->json(['success' => false, 'message' => 'Kode promo sudah kadaluarsa.'], 400);
        }

        // Hitung Diskon
        $discount = 0;
        if ($promo->type == 'fixed') {
            $discount = $promo->discount_amount;
        } else {
            $discount = ($request->total_amount * $promo->discount_amount) / 100;
        }

        // Pastikan diskon tidak melebihi total harga
        if ($discount > $request->total_amount) {
            $discount = $request->total_amount;
        }

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil digunakan!',
            'data' => [
                'code' => $promo->code,
                'discount_amount' => $discount,
                'final_total' => $request->total_amount - $discount
            ]
        ]);
    }
}