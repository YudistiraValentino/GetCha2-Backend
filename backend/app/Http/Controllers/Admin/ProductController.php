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
        Log::info('CREATE PRODUCT SIMPLIFIED:', $request->all());

        $request->validate([
            'name' => 'required',
            'price' => 'required',
            'category_id' => 'required',
        ]);

        try {
            DB::beginTransaction();

            // LOGIKA GAMBAR OTOMATIS BERDASARKAN KATEGORI
            // Diasumsikan: ID 1=Coffee, 2=Non-Coffee, 3=Food, 4=Snack
            $imageName = 'food.jpg'; // Default

            $catId = $request->category_id;
            if ($catId == 1 || $catId == 2) {
                $imageName = 'coffee.jpg';
            } elseif ($catId == 4) {
                $imageName = 'snack.jpg';
            }

            // Simpan path relatif yang akan dibaca Next.js dari folder public-nya
            $imagePath = '/images/products/' . $imageName;

            $product = Product::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'image' => $imagePath,
                'is_promo' => filter_var($request->is_promo, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            ]);

            // Save Variants
            $variants = $this->parseJsonField($request->variants);
            if (!empty($variants)) {
                foreach ($variants as $variant) {
                    if (!empty(data_get($variant, 'name'))) {
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'name' => $variant['name'],
                            'price' => $variant['price'] ?? $product->price,
                        ]);
                    }
                }
            }

            // Save Modifiers
            $modifiers = $this->parseJsonField($request->modifiers);
            if (!empty($modifiers)) {
                foreach ($modifiers as $mod) {
                    if (!empty(data_get($mod, 'name'))) {
                        ProductModifier::create([
                            'product_id' => $product->id,
                            'name' => $mod['name'],
                            'is_required' => filter_var(data_get($mod, 'required'), FILTER_VALIDATE_BOOLEAN),
                            'options' => data_get($mod, 'options') ?? []
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

        try {
            DB::beginTransaction();

            // Update data dasar
            $product->update([
                'name' => $request->name ?? $product->name,
                'category_id' => $request->category_id ?? $product->category_id,
                'description' => $request->description ?? $product->description,
                'price' => $request->price ?? $product->price,
                'is_promo' => $request->has('is_promo') ? (filter_var($request->is_promo, FILTER_VALIDATE_BOOLEAN) ? 1 : 0) : $product->is_promo,
            ]);

            // Sync Variants & Modifiers (Delete then Re-create)
            if ($request->has('variants')) {
                $product->variants()->delete();
                $variants = $this->parseJsonField($request->variants);
                foreach ($variants as $variant) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => $variant['name'],
                        'price' => $variant['price'] ?? $product->price,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Produk diupdate!', 'data' => $product]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if ($product) $product->delete();
        return response()->json(['success' => true, 'message' => 'Produk dihapus!']);
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
