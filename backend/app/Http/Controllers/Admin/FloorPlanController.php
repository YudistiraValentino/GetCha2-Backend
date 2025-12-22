<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FloorPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FloorPlanController extends Controller
{
    /**
     * GET /api/admin/maps
     */
    public function index()
    {
        $maps = FloorPlan::latest()->get();
        return response()->json([
            'success' => true,
            'data' => $maps
        ]);
    }

    /**
     * POST /api/admin/maps
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'map_file' => 'required|mimes:svg|max:2048', // Wajib SVG
        ]);

        try {
            if ($request->hasFile('map_file')) {
                $file = $request->file('map_file');
                
                // Simpan di folder public/maps
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('maps'), $filename);
                
                // Logic: Jika ini map pertama, langsung set active
                $isActive = FloorPlan::count() === 0 ? true : false;

                $map = FloorPlan::create([
                    'name' => $request->name,
                    'image_path' => '/maps/' . $filename,
                    'is_active' => $isActive
                ]);

                return response()->json(['success' => true, 'message' => 'Map uploaded!', 'data' => $map]);
            }

            return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/maps/{id}/activate
     */
    public function activate($id)
    {
        // 1. Matikan semua map dulu
        FloorPlan::query()->update(['is_active' => false]);

        // 2. Aktifkan map yang dipilih
        $map = FloorPlan::find($id);
        
        if(!$map) return response()->json(['success' => false, 'message' => 'Map not found'], 404);

        $map->update(['is_active' => true]);

        return response()->json(['success' => true, 'message' => 'Map activated!']);
    }

    /**
     * DELETE /api/admin/maps/{id}
     */
    public function destroy($id)
    {
        $map = FloorPlan::find($id);
        if(!$map) return response()->json(['success' => false, 'message' => 'Map not found'], 404);
        
        // Hapus file fisik
        $path = public_path($map->image_path);
        if (file_exists($path)) {
            @unlink($path);
        }

        $map->delete();
        return response()->json(['success' => true, 'message' => 'Map deleted.']);
    }
}