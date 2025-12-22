<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order; // 👈 Pastikan Import Model Order

class OrderController extends Controller
{
    public function index(Request $request)
    {
        // Pastikan nama kolom di database 'customer_name' benar
        $orders = Order::where('customer_name', $request->user()->name)
                    ->with('items')
                    ->latest()
                    ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }
}