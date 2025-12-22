@extends('admin.layout')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">📋 Order List</h1>
</div>

<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead>
                <tr class="bg-gray-800 text-white text-sm uppercase leading-normal">
                    <th class="py-3 px-6 text-left">Order No</th>
                    <th class="py-3 px-6 text-left">Date</th>
                    <th class="py-3 px-6 text-left">Customer</th>
                    <th class="py-3 px-6 text-center">Type</th>
                    <th class="py-3 px-6 text-center">Status</th>
                    <th class="py-3 px-6 text-right">Total</th>
                    <th class="py-3 px-6 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm font-light">
                @forelse($orders as $order)
                <tr class="border-b border-gray-200 hover:bg-gray-100">
                    <td class="py-3 px-6 text-left font-bold text-blue-600">
                        {{ $order->order_number }}
                    </td>
                    <td class="py-3 px-6 text-left">
                        {{ $order->created_at->format('d M Y H:i') }}
                    </td>
                    
                    {{-- 👇 UPDATE KOLOM CUSTOMER: Tampilkan Meja jika Dine In --}}
                    <td class="py-3 px-6 text-left">
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-700">{{ $order->customer_name ?: 'Guest' }}</span>
                            @if($order->order_type == 'dine_in' && $order->table_number)
                                <span class="text-xs text-blue-600 font-bold bg-blue-50 w-fit px-2 py-0.5 rounded mt-1 border border-blue-100">
                                    <i class="fas fa-chair mr-1"></i> Meja {{ $order->table_number }}
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- 👇 UPDATE KOLOM TYPE: Beda Warna --}}
                    <td class="py-3 px-6 text-center">
                        @if($order->order_type == 'dine_in')
                            <span class="bg-purple-100 text-purple-700 py-1 px-3 rounded-full text-xs font-bold border border-purple-200">
                                🍽️ Dine In
                            </span>
                        @else
                            <span class="bg-orange-100 text-orange-700 py-1 px-3 rounded-full text-xs font-bold border border-orange-200">
                                🥡 Take Away
                            </span>
                        @endif
                    </td>

                    <td class="py-3 px-6 text-center">
                        {{-- Logic Warna Status --}}
                        @php
                            $statusColor = 'bg-gray-200 text-gray-600';
                            if($order->status == 'pending') $statusColor = 'bg-yellow-100 text-yellow-700 border border-yellow-300';
                            if($order->status == 'confirmed') $statusColor = 'bg-indigo-100 text-indigo-700 border border-indigo-300';
                            if($order->status == 'processing') $statusColor = 'bg-blue-100 text-blue-700 border border-blue-300';
                            if($order->status == 'ready') $statusColor = 'bg-green-100 text-green-700 border border-green-300';
                            if($order->status == 'completed') $statusColor = 'bg-green-600 text-white';
                            if($order->status == 'cancelled') $statusColor = 'bg-red-100 text-red-700 border border-red-300';
                        @endphp
                        <span class="{{ $statusColor }} py-1 px-3 rounded-full text-xs font-bold uppercase">
                            {{ $order->status }}
                        </span>
                    </td>
                    <td class="py-3 px-6 text-right font-bold text-gray-700">
                        Rp {{ number_format($order->total_price, 0, ',', '.') }}
                    </td>
                    <td class="py-3 px-6 text-center">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600 transition inline-block shadow-sm" title="View Detail">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-10 text-gray-400 bg-gray-50">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-clipboard-list text-4xl mb-3 text-gray-300"></i>
                            <p>Belum ada pesanan masuk.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection