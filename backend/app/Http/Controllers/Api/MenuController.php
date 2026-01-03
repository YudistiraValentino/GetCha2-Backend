<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    // Ambil Semua Menu
    public function index()
    {
        // ✅ PERBAIKAN: Gunakan 'with' untuk relasi category, variants, modifiers
        // Hapus leftJoin yang manual dan ribet
        $products = Product::with(['category', 'variants', 'modifiers'])
            ->orderBy('created_at', 'desc') // Urutkan biar rapi
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    // Ambil Detail 1 Menu
    public function show($id)
    {
        // ✅ PERBAIKAN: Gunakan 'with' disini juga
        $product = Product::with(['category', 'variants', 'modifiers'])
            ->where('id', $id)
            ->first();

        if ($product) {
            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Product not found'], 404);
    }

    // Ambil 4 Produk Terbaru
    public function getNewArrivals()
    {
        // ✅ PERBAIKAN: Gunakan 'with' disini juga
        $products = Product::with(['category', 'variants', 'modifiers'])
            ->latest() 
            ->take(4) 
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}