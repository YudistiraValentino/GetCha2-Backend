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
        // 1. LOG DATA (Cek Logs Railway untuk lihat data yang dikirim Frontend)
        Log::info('CREATE PRODUCT REQUEST:', $request->all());

        // 🔥 [DETEKTIF MODE] CEK VARIABLE RAILWAY SEBELUM LANJUT
        // Kode ini akan menangkap error konfigurasi yang bikin "Array Offset Null"
        $cloudinaryUrl = env('CLOUDINARY_URL');
        
        if (empty($cloudinaryUrl)) {
            return response()->json([
                'success' => false, 
                'message' => 'FATAL ERROR: Variable CLOUDINARY_URL belum ada di Railway! Harap tambahkan di menu Variables.'
            ], 500);
        }

        // Cek apakah user tidak sengaja copy-paste nama variabelnya juga
        if (strpos($cloudinaryUrl, 'CLOUDINARY_URL=') !== false) {
             return response()->json([
                'success' => false, 
                'message' => 'FATAL ERROR: Format Value CLOUDINARY_URL salah! Jangan tulis "CLOUDINARY_URL=" di dalam kolom Value. Cukup URL-nya saja (cloudinary://...).'
            ], 500);
        }

        // Cek apakah ada tanda kutip
        if (strpos($cloudinaryUrl, '"') !== false || strpos($cloudinaryUrl, "'") !== false) {
             return response()->json([
                'success' => false, 
                'message' => 'FATAL ERROR: Format CLOUDINARY_URL tidak boleh ada tanda kutip (" atau \'). Hapus tanda kutipnya di Railway Variables.'
            ], 500);
        }
        // 🔥 [END DETEKTIF MODE]

        // Validasi Input
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
                try {
                    $uploadedFile = Cloudinary::upload($request->file('image')->getRealPath(), [
                        'folder' => 'getcha_products'
                    ]);
                    $imagePath = $uploadedFile->getSecurePath();
                } catch (\Exception $e) {
                    // Tangkap error spesifik Cloudinary biar ketahuan
                    throw new \Exception("Gagal Upload ke Cloudinary: " . $e->getMessage() . ". (Cek koneksi internet server atau validitas URL Cloudinary)");
                }
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

            // 4. Simpan Variants (MENGGUNAKAN DATA_GET AGAR AMAN)
            $variants = $this->parseJsonField($request->variants);
            if (!empty($variants) && is_array($variants)) {
                foreach ($variants as $variant) {
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

            // 5. Simpan Modifiers (MENGGUNAKAN DATA_GET AGAR AMAN)
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

            return response()->json([
                'success' => true, 
                'message' => 'Produk berhasil dibuat!', 
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
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

            $imagePath = $product->image;
            if ($request->hasFile('image')) {
                try {
                    $uploadedFile = Cloudinary::upload($request->file('image')->getRealPath(), [
                        'folder' => 'getcha_products'
                    ]);
                    $imagePath = $uploadedFile->getSecurePath();
                } catch (\Exception $e) {
                     throw new \Exception("Gagal Upload ke Cloudinary (Update): " . $e->getMessage());
                }
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

            // Update Modifiers
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