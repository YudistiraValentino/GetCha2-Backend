@extends('admin.layout')

@section('content')
<div class="max-w-5xl mx-auto bg-white shadow-lg rounded-xl px-8 pt-6 pb-8 mb-4 border border-gray-100">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h2 class="text-2xl font-bold text-gray-800">☕ Add New Menu Item</h2>
    </div>

    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Product Name</label>
                <input type="text" name="name" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="Ex: Hazelnut Latte" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Category</label>
                <select name="category_id" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                    {{-- Pastikan data kategori sudah ada di DB --}}
                    @foreach(\App\Models\Category::all() as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                <textarea name="description" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 focus:ring-2 focus:ring-blue-500 outline-none" rows="2" placeholder="Describe the taste..."></textarea>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Base Price (Rp)</label>
                <input type="number" name="price" class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="25000" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Image</label>
                <input type="file" name="image" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition" required>
            </div>
            <div class="md:col-span-2">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_promo" class="form-checkbox h-5 w-5 text-yellow-500 rounded border-gray-300 focus:ring-yellow-500">
                    <span class="ml-2 text-gray-700 font-medium">Set as New Arrival / Promo?</span>
                </label>
            </div>
        </div>

        <hr class="my-8 border-gray-200">

        <div class="mb-8 bg-blue-50 p-6 rounded-xl border border-blue-100">
            <h3 class="text-lg font-bold text-blue-800 mb-2 flex items-center gap-2">
                <i class="fas fa-expand-arrows-alt"></i> Size Variants
            </h3>
            <p class="text-sm text-blue-600 mb-4">Add sizes like Regular, Large, or Jumbo. If empty, base price is used.</p>

            <div id="variant-container" class="space-y-3">
                </div>

            <button type="button" onclick="addVariant()" class="mt-2 bg-white text-blue-600 border border-blue-200 font-bold py-2 px-4 rounded-lg hover:bg-blue-100 transition shadow-sm text-sm">
                + Add Size
            </button>
        </div>

        <div class="mb-8 bg-purple-50 p-6 rounded-xl border border-purple-100">
            <h3 class="text-lg font-bold text-purple-800 mb-2 flex items-center gap-2">
                <i class="fas fa-sliders-h"></i> Customization Groups
            </h3>
            <p class="text-sm text-purple-600 mb-4">Create groups like "Sugar Level", "Temperature", or "Toppings".</p>

            <div id="modifiers-container" class="space-y-6">
                </div>

            <button type="button" onclick="addModifierGroup()" class="mt-4 bg-purple-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-purple-700 transition shadow-md">
                + Create New Group (e.g. Sugar)
            </button>
        </div>

        <div class="flex items-center justify-end border-t pt-6">
            <button type="submit" class="bg-gray-900 hover:bg-black text-white font-bold py-3 px-8 rounded-xl shadow-lg transform hover:-translate-y-1 transition text-lg">
                Save Product
            </button>
        </div>
    </form>
</div>

<script>
    // --- 1. LOGIC SIZE (VARIANTS) ---
    let variantCount = 0;
    function addVariant() {
        const container = document.getElementById('variant-container');
        const html = `
            <div class="flex gap-4 items-center animate-fade-in">
                <input type="text" name="variants[${variantCount}][name]" placeholder="Size Name (e.g. Large)" class="flex-1 shadow-sm border rounded py-2 px-3">
                <input type="number" name="variants[${variantCount}][price]" placeholder="Price (e.g. 32000)" class="flex-1 shadow-sm border rounded py-2 px-3">
                <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-600 p-2"><i class="fas fa-trash"></i></button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        variantCount++;
    }

    // --- 2. LOGIC MODIFIERS (GROUPS & OPTIONS) ---
    let modGroupCount = 0;

    function addModifierGroup() {
        const container = document.getElementById('modifiers-container');
        const groupId = modGroupCount;
        
        const html = `
            <div class="bg-white p-4 rounded-lg shadow-sm border border-purple-200 relative group animate-fade-in" id="group-${groupId}">
                <button type="button" onclick="document.getElementById('group-${groupId}').remove()" class="absolute top-2 right-2 text-gray-300 hover:text-red-500 transition"><i class="fas fa-times-circle"></i></button>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Group Name</label>
                        <input type="text" name="modifiers[${groupId}][name]" placeholder="e.g. Sugar Level" class="w-full border-b-2 border-purple-100 focus:border-purple-500 outline-none py-1 font-bold text-gray-700" required>
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="modifiers[${groupId}][required]" class="form-checkbox text-purple-600 rounded">
                            <span class="ml-2 text-sm text-gray-600">Is Required? (Wajib Pilih)</span>
                        </label>
                    </div>
                </div>

                <div class="pl-4 border-l-2 border-gray-100">
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Options</label>
                    <div id="options-container-${groupId}" class="space-y-2">
                        </div>
                    <button type="button" onclick="addOption(${groupId})" class="mt-2 text-xs font-bold text-purple-500 hover:text-purple-700">+ Add Option</button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        modGroupCount++;
        
        // Auto add 2 empty options for convenience
        addOption(groupId);
        addOption(groupId);
    }

    function addOption(groupId) {
        const container = document.getElementById(`options-container-${groupId}`);
        // Kita pakai timestamp biar unik indexnya di dalam array option
        const optId = Date.now() + Math.random().toString(36).substr(2, 5); 
        
        const html = `
            <div class="flex gap-2 items-center">
                <input type="text" name="modifiers[${groupId}][options][${optId}][label]" placeholder="Label (e.g. Less Sugar)" class="flex-grow text-sm border rounded py-1 px-2 focus:ring-1 focus:ring-purple-300 outline-none">
                <input type="number" name="modifiers[${groupId}][options][${optId}][price]" placeholder="+Rp (0 if free)" class="w-24 text-sm border rounded py-1 px-2 focus:ring-1 focus:ring-purple-300 outline-none">
                <label class="flex items-center gap-1 cursor-pointer" title="Set as Default">
                    <input type="checkbox" name="modifiers[${groupId}][options][${optId}][default]" class="rounded text-purple-600 focus:ring-0">
                    <span class="text-xs text-gray-400">Default</span>
                </label>
                <button type="button" onclick="this.parentElement.remove()" class="text-gray-300 hover:text-red-500"><i class="fas fa-times"></i></button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }

    // Auto add 1 variant field on load
    addVariant();
</script>

<style>
    .animate-fade-in { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endsection