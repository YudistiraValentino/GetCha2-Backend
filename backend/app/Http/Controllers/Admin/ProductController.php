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
     * GET ALL PRODUCTS
     */
    public function index()
    {
        $products = Product::with(['category', 'variants', 'modifiers'])->latest()->get();
        return response()->json(['success' => true, 'data' => $products]);
    }

    /**
     * GET SINGLE PRODUCT
     */
    public function show($id)
    {
        $product = Product::with(['category', 'variants', 'modifiers'])->find($id);
        if (!$product) return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        return response()->json(['success' => true, 'data' => $product]);
    }

    /**
     * STORE (CREATE) PRODUCT
     */
    public function store(Request $request)
    {
        // 1. LOG DATA (Biar ketahuan di Logs Railway apa yang dikirim Frontend)
        Log::info('CREATE PRODUCT REQUEST:', $request->all());

        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required',
            'price' => 'required|numeric',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        try {
            DB::beginTransaction();

            // 2. Handle Upload Image (Cloudinary)
            $imagePath = null;
            if ($request->hasFile('image')) {
                // Upload ke folder 'getcha_products'
                $uploadedFile = Cloudinary::upload($request->file('image')->getRealPath(), [
                    'folder' => 'getcha_products'
                ]);
                $imagePath = $uploadedFile->getSecurePath();
            }

            // 3. Simpan Data Utama
            $product = Product::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'image' => $imagePath,
                'is_promo' => filter_var($request->is_promo, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            ]);

            // 4. Simpan Variants (MENGGUNAKAN DATA_GET AGAR TIDAK ERROR NULL)
            $variants = $this->parseJsonField($request->variants);
            if (!empty($variants) && is_array($variants)) {
                foreach ($variants as $variant) {
                    // Ambil data dengan aman. Jika null, return null (tidak crash)
                    $vName = data_get($variant, 'name'); 
                    $vPrice = data_get($variant, 'price');

                    if (!empty($vName)) {
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'name' => $vName,
                            'price' => $vPrice ?? $product->price,
                        ]);
                    }
                }
            }

            // 5. Simpan Modifiers (MENGGUNAKAN DATA_GET AGAR TIDAK ERROR NULL)
            $modifiers = $this->parseJsonField($request->modifiers);
            if (!empty($modifiers) && is_array($modifiers)) {
                foreach ($modifiers as $mod) {
                    $mName = data_get($mod, 'name');
                    
                    if (!empty($mName)) {
                        $cleanOptions = [];
                        $rawOptions = data_get($mod, 'options'); // Ambil array options

                        if (is_array($rawOptions)) {
                            foreach ($rawOptions as $opt) {
                                $oLabel = data_get($opt, 'label');
                                
                                if (!empty($oLabel)) {
                                    $cleanOptions[] = [
                                        'label' => $oLabel,
                                        'priceChange' => data_get($opt, 'price') ?? 0,
                                        'isDefault' => filter_var(data_get($opt, 'default'), FILTER_VALIDATE_BOOLEAN)
                                    ];
                                }
                            }
                        }

                        ProductModifier::create([
                            'product_id' => $product->id,
                            'name' => $mName,
                            'is_required' => filter_var(data_get($mod, 'required'), FILTER_VALIDATE_BOOLEAN),
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
            // Log Error lengkap dengan nomor baris
            Log::error("GAGAL STORE PRODUCT: " . $e->getMessage() . ' - Line: ' . $e->getLine());
            
            return response()->json([
                'success' => false, 
                'message' => 'Gagal membuat produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * UPDATE PRODUCT
     */
    public function update(Request $request, $id)
    {
        Log::info('UPDATE PRODUCT REQUEST:', $request->all());

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

            // Handle Image Update
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

            // Update Variants (Delete Old -> Create New)
            $product->variants()->delete();
            $variants = $this->parseJsonField($request->variants);
            if (!empty($variants) && is_array($variants)) {
                foreach ($variants as $variant) {
                    $vName = data_get($variant, 'name');
                    if (!empty($vName)) {
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'name' => $vName,
                            'price' => data_get($variant, 'price') ?? $product->price,
                        ]);
                    }
                }
            }

            // Update Modifiers (Delete Old -> Create New)
            $product->modifiers()->delete();
            $modifiers = $this->parseJsonField($request->modifiers);
            if (!empty($modifiers) && is_array($modifiers)) {
                foreach ($modifiers as $mod) {
                    $mName = data_get($mod, 'name');
                    if (!empty($mName)) {
                        $cleanOptions = [];
                        $rawOptions = data_get($mod, 'options');

                        if (is_array($rawOptions)) {
                            foreach ($rawOptions as $opt) {
                                $oLabel = data_get($opt, 'label');
                                if (!empty($oLabel)) {
                                    $cleanOptions[] = [
                                        'label' => $oLabel,
                                        'priceChange' => data_get($opt, 'price') ?? 0,
                                        'isDefault' => filter_var(data_get($opt, 'default'), FILTER_VALIDATE_BOOLEAN)
                                    ];
                                }
                            }
                        }

                        ProductModifier::create([
                            'product_id' => $product->id,
                            'name' => $mName,
                            'is_required' => filter_var(data_get($mod, 'required'), FILTER_VALIDATE_BOOLEAN),
                            'options' => $cleanOptions
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Produk berhasil diupdate!', 'data' => $product]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("GAGAL UPDATE PRODUCT: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal update: ' . $e->getMessage()], 500);
        }
    }

    /**
     * DELETE PRODUCT
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

    /**
     * HELPER: Parse JSON String from Frontend
     */
    private function parseJsonField($field)
    {
        if (is_string($field)) {
            $decoded = json_decode($field, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($field) ? $field : [];
    }
}