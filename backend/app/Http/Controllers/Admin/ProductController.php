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
     */
    public function index()
    {
        $products = Product::with(['category', 'variants', 'modifiers'])
                           ->latest()
                           ->get();

        return response()->json(['success' => true, 'data' => $products]);
    }

    /**
     * GET /api/admin/products/{id}
     */
    public function show($id)
    {
        $product = Product::with(['category', 'variants', 'modifiers'])->find($id);
        if (!$product) return response()->json(['success' => false, 'message' => 'Product not found'], 404);

        return response()->json(['success' => true, 'data' => $product]);
    }

    /**
     * POST /api/admin/products
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required',
            'price' => 'required|numeric',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        try {
            DB::beginTransaction();

            // 1. Handle Upload Image (Cloudinary)
            $imagePath = null;
            if ($request->hasFile('image')) {
                $uploadedFile = Cloudinary::upload($request->file('image')->getRealPath(), [
                    'folder' => 'getcha_products'
                ]);
                $imagePath = $uploadedFile->getSecurePath();
            }

            // 2. Simpan Data Utama
            $product = Product::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'image' => $imagePath,
                'is_promo' => filter_var($request->is_promo, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            ]);

            // 3. Simpan Variants (DENGAN PENJAGAAN KETAT 🛡️)
            $variants = $this->parseJsonField($request->variants);
            if (!empty($variants) && is_array($variants)) {
                foreach ($variants as $variant) {
                    // 🛡️ CEK: Kalau variant-nya null/bukan array, SKIP!
                    if (!is_array($variant)) continue; 

                    if (!empty($variant['name'])) {
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'name' => $variant['name'],
                            'price' => $variant['price'] ?? $product->price,
                        ]);
                    }
                }
            }

            // 4. Simpan Modifiers (DENGAN PENJAGAAN KETAT 🛡️)
            $modifiers = $this->parseJsonField($request->modifiers);
            if (!empty($modifiers) && is_array($modifiers)) {
                foreach ($modifiers as $mod) {
                    // 🛡️ CEK: Kalau modifier-nya null/bukan array, SKIP!
                    if (!is_array($mod)) continue;

                    if (!empty($mod['name'])) {
                        $cleanOptions = [];
                        if (isset($mod['options']) && is_array($mod['options'])) {
                            foreach ($mod['options'] as $opt) {
                                // 🛡️ CEK: Kalau option-nya null, SKIP!
                                if (!is_array($opt)) continue;

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
                'message' => 'Produk berhasil dibuat!', 
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error create product: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Gagal membuat produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/admin/products/{id}
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['success' => false, 'message' => 'Product not found'], 404);

        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required',
            'price' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $imagePath = $product->image;
            if ($request->hasFile('image')) {
                $uploadedFile = Cloudinary::upload($request->file('image')->getRealPath(), [
                    'folder' => 'getcha_products'
                ]);
                $imagePath = $uploadedFile->getSecurePath();
            }

            $product->update([
                'name' => $request->name,
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'image' => $imagePath,
                'is_promo' => filter_var($request->is_promo, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            ]);

            // Update Variants
            $product->variants()->delete();
            $variants = $this->parseJsonField($request->variants);
            if (!empty($variants) && is_array($variants)) {
                foreach ($variants as $variant) {
                    if (!is_array($variant)) continue; // 🛡️ Safety Check

                    if (!empty($variant['name'])) {
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'name' => $variant['name'],
                            'price' => $variant['price'] ?? $product->price,
                        ]);
                    }
                }
            }

            // Update Modifiers
            $product->modifiers()->delete();
            $modifiers = $this->parseJsonField($request->modifiers);
            if (!empty($modifiers) && is_array($modifiers)) {
                foreach ($modifiers as $mod) {
                    if (!is_array($mod)) continue; // 🛡️ Safety Check

                    if (!empty($mod['name'])) {
                        $cleanOptions = [];
                        if (isset($mod['options']) && is_array($mod['options'])) {
                            foreach ($mod['options'] as $opt) {
                                if (!is_array($opt)) continue; // 🛡️ Safety Check

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

            return response()->json(['success' => true, 'message' => 'Produk berhasil diupdate!', 'data' => $product]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Gagal update: ' . $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/admin/products/{id}
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['success' => false, 'message' => 'Product not found'], 404);

        try {
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
            $decoded = json_decode($field, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($field) ? $field : [];
    }
}