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
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Product::with(['category', 'variants', 'modifiers'])->latest()->get()]);
    }

    public function show($id)
    {
        $product = Product::with(['category', 'variants', 'modifiers'])->find($id);
        if (!$product) return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        return response()->json(['success' => true, 'data' => $product]);
    }

    public function store(Request $request)
    {
        Log::info('CREATE PRODUCT REQUEST:', $request->all());

        $request->validate([
            'name' => 'required',
            'price' => 'required',
            'image' => 'required|image|max:10240',
        ]);

        try {
            DB::beginTransaction();

            // 🔥 JURUS PAMUNGKAS: FORCE CONFIGURATION
            // Ganti tulisan di bawah ini dengan URL Cloudinary kamu!
            // Jangan hapus tanda kutipnya!
            config(['cloudinary.cloud_url' => 'MASUKKAN_URL_CLOUDINARY_DISINI']);

            // Contoh hasil jadinya nanti begini:
            // config(['cloudinary.cloud_url' => 'cloudinary://87463728:abcdefgh@namacloudkamu']);

            $imagePath = null;
            if ($request->hasFile('image')) {
                try {
                    // Upload
                    $uploadedFile = Cloudinary::upload($request->file('image')->getRealPath(), [
                        'folder' => 'getcha_products'
                    ]);
                    $imagePath = $uploadedFile->getSecurePath();
                } catch (\Exception $e) {
                    // Kalau upload gagal, kita pakai gambar dummy biar GAK ERROR
                    Log::error("Cloudinary Error: " . $e->getMessage());
                    $imagePath = 'https://placehold.co/600x400?text=Upload+Failed';
                }
            }

            $product = Product::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'image' => $imagePath,
                'is_promo' => filter_var($request->is_promo, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            ]);

            // Save Variants (Versi Paling Aman)
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

            // Save Modifiers (Versi Paling Aman)
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
            return response()->json(['success' => true, 'message' => 'Produk berhasil dibuat!', 'data' => $product], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("ERROR STORE: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['success' => false, 'message' => 'Product not found'], 404);

        $request->validate([
            'name' => 'required',
            'price' => 'required',
        ]);

        try {
            DB::beginTransaction();

            // 🔥 FORCE CONFIG JUGA DISINI
            config(['cloudinary.cloud_url' => 'MASUKKAN_URL_CLOUDINARY_DISINI']);

            $imagePath = $product->image;
            if ($request->hasFile('image')) {
                try {
                    $uploadedFile = Cloudinary::upload($request->file('image')->getRealPath(), [
                        'folder' => 'getcha_products'
                    ]);
                    $imagePath = $uploadedFile->getSecurePath();
                } catch (\Exception $e) {
                     Log::error("Cloudinary Update Error: " . $e->getMessage());
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
            return response()->json(['success' => false, 'message' => 'Gagal update: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if ($product) $product->delete();
        return response()->json(['success' => true, 'message' => 'Produk berhasil dihapus!']);
    }

    private function parseJsonField($field)
    {
        if (is_string($field)) {
            $decoded = json_decode($field, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($field) ? $field : [];
    }
}