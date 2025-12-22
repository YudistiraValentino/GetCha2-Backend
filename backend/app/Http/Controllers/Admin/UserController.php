<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash; // 👈 WAJIB ADA: Untuk enkripsi password

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
     * POST /api/admin/users
     * Menyimpan User Baru (Customer)
     * ✅ PERBAIKAN: Auto Generate Username & Hash Password
     */
    public function store(Request $request)
    {
        // 1. Validasi Input (Username tidak divalidasi karena auto-generate)
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        try {
            // 2. Generate Username Otomatis
            // Ambil teks sebelum @ di email, lalu tambah 3 angka acak
            // Contoh: yudis@getcha.com -> yudis843
            $usernameBase = explode('@', $request->email)[0];
            $username = $usernameBase . rand(100, 999);

            // 3. Simpan ke Database
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $username, // ✅ Solusi error "Field username doesn't have default value"
                'password' => Hash::make($request->password), // ✅ Password wajib di-hash
                'role' => 'user', // Default role customer/user
                'points' => 0, // Default poin 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customer berhasil dibuat!',
                'data' => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat user: ' . $e->getMessage()
            ], 500);
        }
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
        $userName = $user->name; 

        // 1. Hitung Total Order
        $totalOrders = DB::table('orders')
            ->where('customer_name', $userName)
            ->count();

        // 2. Cari Barang Favorit
        $favoriteItemName = '-';
        $freqCount = 0;

        try {
            // Cek apakah tabel order_items ada (untuk keamanan jika migrasi belum jalan)
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