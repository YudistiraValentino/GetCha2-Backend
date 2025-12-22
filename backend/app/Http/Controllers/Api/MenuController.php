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
        $products = Product::leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select('products.*', 'categories.name as category_name')
            ->with(['variants', 'modifiers']) 
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    // Ambil Detail 1 Menu
    public function show($id)
    {
        $product = Product::leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select('products.*', 'categories.name as category_name')
            ->with(['variants', 'modifiers']) 
            ->where('products.id', $id)
            ->first();

        if ($product) {
            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Product not found'], 404);
    }

    // 👇 FUNGSI BARU: Ambil 4 Produk Terbaru (Otomatis)
    public function getNewArrivals()
    {
        $products = Product::leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select('products.*', 'categories.name as category_name')
            ->with(['variants', 'modifiers']) 
            ->latest() // Urutkan dari yang created_at paling baru
            ->take(4)  // Ambil cuma 4 biji
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}