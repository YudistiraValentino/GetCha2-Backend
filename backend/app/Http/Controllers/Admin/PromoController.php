<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PromoController extends Controller
{
    /**
     * GET /api/admin/promos
     */
    public function index()
    {
        $promos = Promo::latest()->get();
        return response()->json([
            'success' => true,
            'data' => $promos
        ]);
    }

    /**
     * GET /api/admin/promos/{id}
     */
    public function show($id)
    {
        $promo = Promo::find($id);
        if (!$promo) return response()->json(['success' => false, 'message' => 'Not Found'], 404);

        return response()->json([
            'success' => true,
            'data' => $promo
        ]);
    }

    /**
     * POST /api/admin/promos
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'code' => 'required|unique:promos,code',
            'type' => 'required|in:fixed,percent',
            'discount_amount' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'image' => 'nullable|image|max:2048',
        ]);

        try {
            $data = $request->all();
            
            // Handle Boolean Active (String 'true'/'false' to 1/0)
            $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

            // Handle Image Upload
            if ($request->hasFile('image')) {
                // Simpan di folder public/images/promos
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('images/promos'), $filename);
                $data['image'] = '/images/promos/' . $filename;
            }

            Promo::create($data);

            return response()->json(['success' => true, 'message' => 'Promo created successfully']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/admin/promos/{id}
     */
    public function update(Request $request, $id)
    {
        $promo = Promo::find($id);
        if (!$promo) return response()->json(['success' => false, 'message' => 'Not Found'], 404);

        $request->validate([
            'title' => 'required',
            'code' => 'required|unique:promos,code,'.$id,
            'type' => 'required|in:fixed,percent',
            'discount_amount' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'image' => 'nullable|image|max:2048',
        ]);

        try {
            $data = $request->all();
            $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

            if ($request->hasFile('image')) {
                // Hapus lama
                if ($promo->image && file_exists(public_path($promo->image))) {
                    @unlink(public_path($promo->image));
                }
                // Upload baru
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('images/promos'), $filename);
                $data['image'] = '/images/promos/' . $filename;
            } else {
                // Jika tidak ada upload baru, pakai gambar lama (jangan di-overwrite null)
                unset($data['image']);
            }

            $promo->update($data);

            return response()->json(['success' => true, 'message' => 'Promo updated successfully']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/admin/promos/{id}
     */
    public function destroy($id)
    {
        $promo = Promo::find($id);
        if (!$promo) return response()->json(['success' => false, 'message' => 'Not Found'], 404);

        if ($promo->image && file_exists(public_path($promo->image))) {
            @unlink(public_path($promo->image));
        }

        $promo->delete();
        return response()->json(['success' => true, 'message' => 'Promo deleted']);
    }
}