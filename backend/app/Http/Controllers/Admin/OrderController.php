<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Events\OrderStatusUpdated; // Pastikan event ini ada (bawaan project kamu)

class OrderController extends Controller
{
    /**
     * GET /api/admin/orders
     * List Semua Order
     */
    public function index()
    {
        // Load items biar bisa hitung total item di tabel depan jika perlu
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
        // Load items, variant, modifier biar lengkap di detail
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

        // Update Database
        $order->update([
            'status' => $request->status,
            'payment_status' => $request->payment_status
        ]);

        // 👇 KIRIM SINYAL REALTIME (KE REVERB / PUSHER)
        // Pastikan Event OrderStatusUpdated sudah dibuat di Laravel
        try {
            OrderStatusUpdated::dispatch($order);
        } catch (\Exception $e) {
            // Abaikan error broadcast kalau reverb mati, biar gak ganggu update DB
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully!',
            'data' => $order
        ]);
    }
}