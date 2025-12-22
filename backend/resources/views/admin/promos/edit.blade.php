@extends('admin.layout') {{-- 👈 Update extends --}}

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Edit Promo</h1>
    
    <div class="bg-white shadow-md rounded-lg overflow-hidden p-6">
        <form action="{{ route('admin.promos.update', $promo->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Judul Promo</label>
                    <input type="text" name="title" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="{{ $promo->title }}" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Kode Voucher</label>
                    <input type="text" name="code" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="{{ $promo->code }}" required style="text-transform:uppercase">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Tipe Diskon</label>
                    <select name="type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="fixed" {{ $promo->type == 'fixed' ? 'selected' : '' }}>Nominal (Rp)</option>
                        <option value="percent" {{ $promo->type == 'percent' ? 'selected' : '' }}>Persen (%)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Jumlah Diskon</label>
                    <input type="number" name="discount_amount" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="{{ $promo->discount_amount }}" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="{{ $promo->start_date }}" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Tanggal Berakhir</label>
                    <input type="date" name="end_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="{{ $promo->end_date }}" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Deskripsi</label>
                <textarea name="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="3">{{ $promo->description }}</textarea>
            </div>

            <div class="mb-6 flex items-center">
                <input type="checkbox" name="is_active" value="1" id="isActive" class="mr-2 leading-tight h-4 w-4" {{ $promo->is_active ? 'checked' : '' }}>
                <label class="text-sm font-bold text-gray-700" for="isActive">Promo Aktif?</label>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Update Promo
                </button>
                <a href="{{ route('admin.promos.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection