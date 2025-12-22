<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductModifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * GET /api/admin/products
     * Mengambil semua produk untuk List Table
     */
    public function index()
    {
        // Load data beserta relasinya biar frontend gak perlu fetch berkali-kali
        $products = Product::with(['category', 'variants', 'modifiers'])
                           ->latest()
                           ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * GET /api/admin/products/{id}
     * Mengambil 1 produk detail untuk form Edit
     */
    public function show($id)
    {
        $product = Product::with(['category', 'variants', 'modifiers'])->find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * POST /api/admin/products
     * Menyimpan produk baru
     */
    public function store(Request $request)
    {
        // Validasi
        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required',
            'price' => 'required|numeric',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // 1. Handle Upload Image
            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                // Simpan ke public/images (sesuai kodingan lama kamu)
                $file->move(public_path('images'), $filename);
                $imagePath = '/images/' . $filename;
            }

            // 2. Simpan Data Utama
            $product = Product::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'image' => $imagePath,
                // Handle boolean dari string "true"/"false" atau 1/0
                'is_promo' => filter_var($request->is_promo, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            ]);

            // 3. Simpan Variants (Handle Array dari JSON atau Form Array)
            $variants = $this->parseJsonField($request->variants);
            if (!empty($variants)) {
                foreach ($variants as $variant) {
                    if (!empty($variant['name'])) {
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'name' => $variant['name'],
                            'price' => $variant['price'] ?? $product->price,
                        ]);
                    }
                }
            }

            // 4. Simpan Modifiers
            $modifiers = $this->parseJsonField($request->modifiers);
            if (!empty($modifiers)) {
                foreach ($modifiers as $mod) {
                    if (!empty($mod['name'])) {
                        // Bersihkan opsi
                        $cleanOptions = [];
                        if (isset($mod['options']) && is_array($mod['options'])) {
                            foreach ($mod['options'] as $opt) {
                                if (!empty($opt['label'])) {
                                    $cleanOptions[] = [
                                        'label' => $opt['label'],
                                        'priceChange' => $opt['price'] ?? 0, // Frontend kirim 'price', DB simpan 'priceChange'
                                        'isDefault' => isset($opt['default']) ? filter_var($opt['default'], FILTER_VALIDATE_BOOLEAN) : false
                                    ];
                                }
                            }
                        }

                        ProductModifier::create([
                            'product_id' => $product->id,
                            'name' => $mod['name'],
                            'is_required' => isset($mod['required']) ? filter_var($mod['required'], FILTER_VALIDATE_BOOLEAN) : false,
                            'options' => $cleanOptions // Ini otomatis di-cast ke JSON oleh Model (pastikan casts ada)
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Produk berhasil dibuat!', 
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage()); // Catat error di log laravel
            return response()->json([
                'success' => false, 
                'message' => 'Gagal membuat produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST/PUT /api/admin/products/{id}
     * Update produk (Gunakan POST dengan _method=PUT jika kirim file di FormData)
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required',
            'price' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Nullable saat update
        ]);

        try {
            DB::beginTransaction();

            // 1. Handle Image
            $imagePath = $product->image;
            if ($request->hasFile('image')) {
                // Hapus yang lama
                if ($product->image && file_exists(public_path($product->image))) {
                    @unlink(public_path($product->image));
                }
                // Upload baru
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('images'), $filename);
                $imagePath = '/images/' . $filename;
            }

            // 2. Update Data Utama
            $product->update([
                'name' => $request->name,
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'image' => $imagePath,
                'is_promo' => filter_var($request->is_promo, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            ]);

            // 3. Update Variants (Hapus lama, buat baru - strategi paling aman)
            $product->variants()->delete();
            $variants = $this->parseJsonField($request->variants);
            
            if (!empty($variants)) {
                foreach ($variants as $variant) {
                    if (!empty($variant['name'])) {
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'name' => $variant['name'],
                            'price' => $variant['price'] ?? $product->price,
                        ]);
                    }
                }
            }

            // 4. Update Modifiers
            $product->modifiers()->delete();
            $modifiers = $this->parseJsonField($request->modifiers);

            if (!empty($modifiers)) {
                foreach ($modifiers as $mod) {
                    if (!empty($mod['name'])) {
                        $cleanOptions = [];
                        if (isset($mod['options']) && is_array($mod['options'])) {
                            foreach ($mod['options'] as $opt) {
                                if (!empty($opt['label'])) {
                                    $cleanOptions[] = [
                                        'label' => $opt['label'],
                                        'priceChange' => $opt['price'] ?? 0,
                                        'isDefault' => isset($opt['default']) ? filter_var($opt['default'], FILTER_VALIDATE_BOOLEAN) : false
                                    ];
                                }
                            }
                        }

                        ProductModifier::create([
                            'product_id' => $product->id,
                            'name' => $mod['name'],
                            'is_required' => isset($mod['required']) ? filter_var($mod['required'], FILTER_VALIDATE_BOOLEAN) : false,
                            'options' => $cleanOptions
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Produk berhasil diupdate!',
                'data' => $product
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false, 
                'message' => 'Gagal update: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/admin/products/{id}
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        try {
            // Hapus file gambar fisik
            if ($product->image && file_exists(public_path($product->image))) {
                @unlink(public_path($product->image));
            }

            // Hapus data (Cascade delete variants/modifiers biasanya diurus DB, tapi Eloquent handle juga)
            $product->delete();

            return response()->json(['success' => true, 'message' => 'Produk berhasil dihapus!']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal hapus: ' . $e->getMessage()], 500);
        }
    }

    // Helper: Next.js FormData sering mengirim array object sebagai JSON String
    private function parseJsonField($field)
    {
        if (is_string($field)) {
            return json_decode($field, true) ?? [];
        }
        return is_array($field) ? $field : [];
    }
}