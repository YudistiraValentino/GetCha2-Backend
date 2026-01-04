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

// 🔥 IMPORT CLOUDINARY
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProductController extends Controller
{
    /**
     * GET /api/admin/products
     * Mengambil semua produk untuk List Table
     */
    public function index()
    {
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
            'image' => 'required|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        try {
            DB::beginTransaction();

            // 1. Handle Upload Image (KE CLOUDINARY) ☁️
            $imagePath = null;
            if ($request->hasFile('image')) {
                // Upload ke Cloudinary folder 'getcha_products'
                $uploadedFile = Cloudinary::upload($request->file('image')->getRealPath(), [
                    'folder' => 'getcha_products'
                ]);
                
                // Ambil URL HTTPS yang aman
                $imagePath = $uploadedFile->getSecurePath();
            }

            // 2. Simpan Data Utama
            $product = Product::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'image' => $imagePath, // URL dari Cloudinary
                'is_promo' => filter_var($request->is_promo, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            ]);

            // 3. Simpan Variants
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
                'message' => 'Produk berhasil dibuat (Saved to Cloud)!', 
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Gagal membuat produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST/PUT /api/admin/products/{id}
     * Update produk
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // 1. Handle Image (KE CLOUDINARY) ☁️
            $imagePath = $product->image; // Pakai gambar lama kalau user gak upload baru
            
            if ($request->hasFile('image')) {
                // Upload gambar baru ke Cloudinary
                $uploadedFile = Cloudinary::upload($request->file('image')->getRealPath(), [
                    'folder' => 'getcha_products'
                ]);
                $imagePath = $uploadedFile->getSecurePath();
                
                // (Optional: Gambar lama di Cloudinary biarkan saja dulu biar gak ribet error handling)
            }

            // 2. Update Data Utama
            $product->update([
                'name' => $request->name,
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'image' => $imagePath, // URL Baru atau Lama
                'is_promo' => filter_var($request->is_promo, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            ]);

            // 3. Update Variants (Reset strategy)
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
            // Hapus data di DB saja (Gambar di Cloudinary biarkan saja biar aman/cepat)
            $product->delete();

            return response()->json(['success' => true, 'message' => 'Produk berhasil dihapus!']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal hapus: ' . $e->getMessage()], 500);
        }
    }

    // Helper: Parse JSON form-data
    private function parseJsonField($field)
    {
        if (is_string($field)) {
            return json_decode($field, true) ?? [];
        }
        return is_array($field) ? $field : [];
    }
}