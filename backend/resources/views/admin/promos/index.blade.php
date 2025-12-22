@extends('admin.layout') {{-- 👈 Update extends ke layout admin --}}

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center my-4">
        <h1 class="mt-4 text-2xl font-bold text-gray-800">Deals Management</h1>
        <a href="{{ route('admin.promos.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
            <i class="fas fa-plus mr-2"></i> Tambah Promo
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
            <h5 class="text-gray-700 font-bold"><i class="fas fa-table mr-2"></i> Daftar Kode Promo Aktif</h5>
        </div>
        <div class="p-6 overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Judul</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kode</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Diskon</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Periode</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($promos as $promo)
                    <tr>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ $promo->title }}</td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <span class="bg-green-200 text-green-800 py-1 px-2 rounded-full text-xs font-bold">{{ $promo->code }}</span>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            @if($promo->type == 'fixed')
                                Rp {{ number_format($promo->discount_amount, 0, ',', '.') }}
                            @else
                                {{ $promo->discount_amount }}%
                            @endif
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">{{ $promo->start_date }} s/d {{ $promo->end_date }}</td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            @if($promo->is_active)
                                <span class="bg-blue-200 text-blue-800 py-1 px-2 rounded-full text-xs font-bold">Aktif</span>
                            @else
                                <span class="bg-gray-200 text-gray-800 py-1 px-2 rounded-full text-xs font-bold">Non-Aktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                            <a href="{{ route('admin.promos.edit', $promo->id) }}" class="text-yellow-600 hover:text-yellow-900 mx-2"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.promos.destroy', $promo->id) }}" method="POST" class="d-inline inline-block" onsubmit="return confirm('Hapus promo ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 mx-2"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection