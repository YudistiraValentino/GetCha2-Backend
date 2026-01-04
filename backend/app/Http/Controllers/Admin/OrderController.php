<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product; // Tambah Model Product
use App\Models\User;    // Tambah Model User
use Illuminate\Http\Request;
use App\Events\OrderStatusUpdated; 

class OrderController extends Controller
{
    /**
     * GET /api/admin/orders
     * List Semua Order
     */
    public function index()
    {
        $orders = Order::with('items')->latest()->get();
        
        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * GET /api/admin/orders/{id}
     * Detail 1 Order
     */
    public function show($id)
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * PUT /api/admin/orders/{id}/status
     * Update Status Order
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }
        
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,ready,completed,cancelled',
            'payment_status' => 'required|in:unpaid,paid'
        ]);

        $order->update([
            'status' => $request->status,
            'payment_status' => $request->payment_status
        ]);

        try {
            OrderStatusUpdated::dispatch($order);
        } catch (\Exception $e) {
            // Abaikan error broadcast
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully!',
            'data' => $order
        ]);
    }

    /**
     * DELETE /api/admin/orders/{id}
     * Hapus Order
     */
    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        try {
            $order->items()->delete();
            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/admin/dashboard-stats
     * Statistik untuk Dashboard Utama
     */
    public function getDashboardStats()
    {
        // 1. Hitung Revenue (Total uang dari order 'completed')
        $revenue = Order::where('status', 'completed')->sum('total_price');

        // 2. Hitung Order Aktif (Pending, Confirmed, Processing)
        $activeOrders = Order::whereIn('status', ['pending', 'confirmed', 'processing'])->count();

        // 3. Hitung Total Menu
        $totalMenus = Product::count();

        // 4. Hitung Total Customer
        $totalCustomers = User::where('role', 'user')->count();

        // 5. Ambil 5 Orderan Terbaru
        $recentOrders = Order::latest()->take(5)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'revenue' => $revenue,
                'active_orders' => $activeOrders,
                'total_menus' => $totalMenus,
                'total_customers' => $totalCustomers,
                'recent_orders' => $recentOrders
            ]
        ]);
    }
}