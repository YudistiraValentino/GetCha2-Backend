<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 

class UserController extends Controller
{
    /**
     * GET /api/admin/users
     * List Semua User
     */
    public function index()
    {
        $users = User::latest()->get(); 
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * GET /api/admin/users/{id}/stats
     * Mengambil statistik user (Total belanja & Favorite Item)
     */
    public function getUserStats($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);

        // --- LOGIC STATISTIK ---
        // Mencocokkan nama user dengan customer_name di tabel orders
        // (Idealnya pakai user_id, tapi kita ikuti struktur database kamu saat ini)
        $userName = $user->name; 

        // 1. Hitung Total Order
        $totalOrders = DB::table('orders')
            ->where('customer_name', $userName)
            ->count();

        // 2. Cari Barang Favorit
        $favoriteItemName = '-';
        $freqCount = 0;

        try {
            // Cek apakah tabel order_items ada
            if (\Schema::hasTable('order_items')) {
                // Query Agregat untuk cari item paling sering dibeli
                $favorite = DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.customer_name', $userName)
                    ->select('order_items.product_name as item_name', DB::raw('SUM(order_items.quantity) as total_qty'))
                    ->groupBy('order_items.product_name')
                    ->orderByDesc('total_qty')
                    ->first();

                if ($favorite) {
                    $favoriteItemName = $favorite->item_name;
                    $freqCount = (int)$favorite->total_qty;
                } else {
                    $favoriteItemName = "Belum belanja";
                }
            }
        } catch (\Exception $e) {
            $favoriteItemName = "Error calc stats";
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'stats' => [
                    'total_orders' => $totalOrders,
                    'favorite_item' => $favoriteItemName,
                    'freq_count' => $freqCount
                ]
            ]
        ]);
    }

    /**
     * POST /api/admin/users/{id}/points
     * Update Poin User
     */
    public function updatePoints(Request $request, $id)
    {
        $request->validate(['points' => 'required|integer|min:0']);
        
        $user = User::find($id);
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);

        $user->update(['points' => $request->points]);

        return response()->json([
            'success' => true,
            'message' => 'Poin user berhasil diperbarui!',
            'data' => $user
        ]);
    }
}