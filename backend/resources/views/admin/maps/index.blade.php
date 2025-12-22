@extends('admin.layout')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Reservation Map Manager</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        {{-- FORM UPLOAD (Kiri) --}}
        <div class="md:col-span-1">
            <div class="bg-white shadow-md rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 text-navy-900">Upload SVG Baru</h3>
                <form action="{{ route('admin.maps.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nama Denah</label>
                        <input type="text" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="e.g. Lantai 1 (Vip)" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">File SVG</label>
                        <input type="file" name="map_file" accept=".svg" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-50" required>
                        <p class="text-xs text-gray-500 mt-1">Format wajib .svg. Pastikan ID table di SVG sesuai nomor meja (table_1, table_2).</p>
                    </div>
                    <button type="submit" class="w-full bg-navy-900 text-white font-bold py-2 px-4 rounded hover:bg-blue-800 transition">
                        <i class="fas fa-upload mr-2"></i> Upload Map
                    </button>
                </form>
            </div>
        </div>

        {{-- LIST MAPS (Kanan) --}}
        <div class="md:col-span-2">
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h5 class="font-bold text-gray-700">Daftar Map Tersedia</h5>
                </div>
                
                @if($maps->isEmpty())
                    <div class="p-6 text-center text-gray-400">Belum ada map yang diupload.</div>
                @else
                    <div class="grid grid-cols-1 gap-4 p-4">
                        @foreach($maps as $map)
                        <div class="border rounded-lg p-4 flex items-center justify-between {{ $map->is_active ? 'border-green-500 bg-green-50' : 'border-gray-200' }}">
                            <div class="flex items-center gap-4">
                                {{-- Preview Kecil --}}
                                <div class="w-20 h-20 bg-gray-100 rounded flex items-center justify-center overflow-hidden border">
                                    <img src="{{ $map->image_path }}" class="w-full h-full object-contain">
                                </div>
                                <div>
                                    <h4 class="font-bold text-lg {{ $map->is_active ? 'text-green-700' : 'text-gray-800' }}">
                                        {{ $map->name }}
                                        @if($map->is_active)
                                            <span class="ml-2 bg-green-200 text-green-800 text-xs px-2 py-1 rounded-full">ACTIVE</span>
                                        @endif
                                    </h4>
                                    <a href="{{ $map->image_path }}" target="_blank" class="text-blue-500 text-sm hover:underline">Lihat Full SVG</a>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                @if(!$map->is_active)
                                    <form action="{{ route('admin.maps.activate', $map->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm w-full">
                                            <i class="fas fa-check-circle mr-1"></i> Pakai Ini
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.maps.destroy', $map->id) }}" method="POST" onsubmit="return confirm('Hapus map ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm w-full">
                                            <i class="fas fa-trash mr-1"></i> Hapus
                                        </button>
                                    </form>
                                @else
                                    <button disabled class="bg-gray-300 text-gray-500 px-3 py-1 rounded cursor-not-allowed text-sm w-full">
                                        Sedang Dipakai
                                    </button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection