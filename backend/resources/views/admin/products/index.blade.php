@extends('admin.layout')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Product List</h1>
    <a href="{{ route('admin.products.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">
        + Add New Product
    </a>
</div>

<div class="bg-white shadow-md rounded my-6 overflow-x-auto">
    <table class="min-w-full bg-white grid-cols-1">
        <thead>
            <tr class="w-full bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                <th class="py-3 px-6 text-left">Image</th>
                <th class="py-3 px-6 text-left">Name</th>
                <th class="py-3 px-6 text-left">Price</th>
                <th class="py-3 px-6 text-center">Variants</th>
                <th class="py-3 px-6 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="text-gray-600 text-sm font-light">
            @foreach($products as $product)
            <tr class="border-b border-gray-200 hover:bg-gray-100">
                <td class="py-3 px-6 text-left">
                    <img src="{{ asset($product->image) }}" class="w-12 h-12 rounded object-cover border">
                </td>
                <td class="py-3 px-6 text-left font-bold">{{ $product->name }}</td>
                <td class="py-3 px-6 text-left">Rp {{ number_format($product->price) }}</td>
                <td class="py-3 px-6 text-center">
                    <span class="bg-gray-200 text-gray-600 py-1 px-3 rounded-full text-xs">
                        {{ $product->variants->count() }} Variants
                    </span>
                </td>
                {{-- 👇 PERBAIKAN DI SINI: Tombol Action --}}
                <td class="py-3 px-6 text-center">
                    <div class="flex item-center justify-center gap-2">
                        {{-- Tombol Edit Link --}}
                        <a href="{{ route('admin.products.edit', $product->id) }}" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110">
                            <i class="fas fa-edit"></i>
                        </a>

                        {{-- Tombol Delete Form --}}
                        <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus {{ $product->name }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-4 mr-2 transform hover:text-red-500 hover:scale-110">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection