@extends('admin.layout')

@section('content')
<div class="max-w-5xl mx-auto bg-white shadow-lg rounded-xl px-8 pt-6 pb-8 mb-4 border border-gray-100">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h2 class="text-2xl font-bold text-gray-800">✏️ Edit Product: {{ $product->name }}</h2>
    </div>

    {{-- Form mengarah ke Route Update dengan Method PUT --}}
    <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT') 

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Product Name</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Category</label>
                <select name="category_id" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 bg-white outline-none">
                    @foreach(\App\Models\Category::all() as $cat)
                        <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                <textarea name="description" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 outline-none" rows="2">{{ old('description', $product->description) }}</textarea>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Base Price (Rp)</label>
                <input type="number" name="price" value="{{ old('price', $product->price) }}" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 outline-none" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Image (Leave empty to keep current)</label>
                <input type="file" name="image" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @if($product->image)
                    <p class="text-xs text-gray-500 mt-2">Current: <a href="{{ asset($product->image) }}" target="_blank" class="text-blue-500 underline">View Image</a></p>
                @endif
            </div>
            <div class="md:col-span-2">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_promo" class="form-checkbox h-5 w-5 text-yellow-500 rounded" {{ $product->is_promo ? 'checked' : '' }}>
                    <span class="ml-2 text-gray-700 font-medium">Set as New Arrival / Promo?</span>
                </label>
            </div>
        </div>

        <hr class="my-8 border-gray-200">

        <div class="mb-8 bg-blue-50 p-6 rounded-xl border border-blue-100">
            <h3 class="text-lg font-bold text-blue-800 mb-2"><i class="fas fa-expand-arrows-alt"></i> Size Variants</h3>
            <div id="variant-container" class="space-y-3"></div>
            <button type="button" onclick="addVariant()" class="mt-2 bg-white text-blue-600 border border-blue-200 font-bold py-2 px-4 rounded-lg text-sm">+ Add Size</button>
        </div>

        <div class="mb-8 bg-purple-50 p-6 rounded-xl border border-purple-100">
            <h3 class="text-lg font-bold text-purple-800 mb-2"><i class="fas fa-sliders-h"></i> Customization Groups</h3>
            <div id="modifiers-container" class="space-y-6"></div>
            <button type="button" onclick="addModifierGroup()" class="mt-4 bg-purple-600 text-white font-bold py-2 px-6 rounded-lg shadow-md">+ Create New Group</button>
        </div>

        <div class="flex items-center justify-end border-t pt-6 gap-4">
            <a href="{{ route('admin.products.index') }}" class="text-gray-500 font-bold hover:text-gray-700">Cancel</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition text-lg">Update Product</button>
        </div>
    </form>
</div>

<script>
    // --- AMBIL DATA DARI LARAVEL KE JS ---
    const existingVariants = @json($product->variants);
    const existingModifiers = @json($product->modifiers);

    // --- 1. LOGIC SIZE (VARIANTS) ---
    let variantCount = 0;
    function addVariant(data = null) {
        const container = document.getElementById('variant-container');
        const nameVal = data ? data.name : '';
        const priceVal = data ? parseFloat(data.price) : '';

        const html = `
            <div class="flex gap-4 items-center animate-fade-in">
                <input type="text" name="variants[${variantCount}][name]" value="${nameVal}" placeholder="Size Name" class="flex-1 shadow-sm border rounded py-2 px-3">
                <input type="number" name="variants[${variantCount}][price]" value="${priceVal}" placeholder="Price" class="flex-1 shadow-sm border rounded py-2 px-3">
                <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-600 p-2"><i class="fas fa-trash"></i></button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        variantCount++;
    }

    // --- 2. LOGIC MODIFIERS ---
    let modGroupCount = 0;
    function addModifierGroup(data = null) {
        const container = document.getElementById('modifiers-container');
        const groupId = modGroupCount;
        
        const nameVal = data ? data.name : '';
        const requiredCheck = (data && data.is_required) ? 'checked' : '';

        const html = `
            <div class="bg-white p-4 rounded-lg shadow-sm border border-purple-200 relative group animate-fade-in" id="group-${groupId}">
                <button type="button" onclick="document.getElementById('group-${groupId}').remove()" class="absolute top-2 right-2 text-gray-300 hover:text-red-500 transition"><i class="fas fa-times-circle"></i></button>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Group Name</label>
                        <input type="text" name="modifiers[${groupId}][name]" value="${nameVal}" class="w-full border-b-2 border-purple-100 focus:border-purple-500 outline-none py-1 font-bold text-gray-700" required>
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="modifiers[${groupId}][required]" class="form-checkbox text-purple-600 rounded" ${requiredCheck}>
                            <span class="ml-2 text-sm text-gray-600">Is Required?</span>
                        </label>
                    </div>
                </div>
                <div class="pl-4 border-l-2 border-gray-100">
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Options</label>
                    <div id="options-container-${groupId}" class="space-y-2"></div>
                    <button type="button" onclick="addOption(${groupId})" class="mt-2 text-xs font-bold text-purple-500 hover:text-purple-700">+ Add Option</button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);

        // Jika ada data opsi lama, looping
        if(data && data.options) {
            data.options.forEach(opt => addOption(groupId, opt));
        } else if (!data) {
            // Kalau bikin baru manual, kasih 1 opsi kosong
            addOption(groupId);
        }

        modGroupCount++;
    }

    function addOption(groupId, data = null) {
        const container = document.getElementById(`options-container-${groupId}`);
        const optId = Date.now() + Math.random().toString(36).substr(2, 5); 
        
        const labelVal = data ? data.label : '';
        const priceVal = data ? data.priceChange : '';
        const defaultCheck = (data && data.isDefault) ? 'checked' : '';

        const html = `
            <div class="flex gap-2 items-center">
                <input type="text" name="modifiers[${groupId}][options][${optId}][label]" value="${labelVal}" placeholder="Label" class="flex-grow text-sm border rounded py-1 px-2">
                <input type="number" name="modifiers[${groupId}][options][${optId}][price]" value="${priceVal}" placeholder="+Rp" class="w-24 text-sm border rounded py-1 px-2">
                <label class="flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" name="modifiers[${groupId}][options][${optId}][default]" class="rounded text-purple-600" ${defaultCheck}>
                    <span class="text-xs text-gray-400">Default</span>
                </label>
                <button type="button" onclick="this.parentElement.remove()" class="text-gray-300 hover:text-red-500"><i class="fas fa-times"></i></button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }

    // --- INITIALIZE DATA ON LOAD ---
    // Load existing variants
    if(existingVariants.length > 0) {
        existingVariants.forEach(v => addVariant(v));
    } else {
        // addVariant(); // Uncomment kalo mau default kosong ada 1
    }

    // Load existing modifiers
    if(existingModifiers.length > 0) {
        existingModifiers.forEach(m => addModifierGroup(m));
    }
</script>
@endsection