<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    // List Semua Promo (Termasuk yang non-aktif)
    public function index()
    {
        $promos = Promo::orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $promos]);
    }

    // Buat Promo Baru
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'code' => 'required|unique:promos,code',
            'type' => 'required|in:fixed,percent',
            'discount_amount' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $promo = Promo::create($request->all());

        return response()->json(['success' => true, 'data' => $promo, 'message' => 'Promo Created']);
    }

    // Update Promo
    public function update(Request $request, $id)
    {
        $promo = Promo::findOrFail($id);
        $promo->update($request->all());
        return response()->json(['success' => true, 'data' => $promo, 'message' => 'Promo Updated']);
    }

    // Hapus Promo
    public function destroy($id)
    {
        Promo::destroy($id);
        return response()->json(['success' => true, 'message' => 'Promo Deleted']);
    }
}