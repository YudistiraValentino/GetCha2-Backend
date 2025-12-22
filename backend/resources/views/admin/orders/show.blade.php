@extends('admin.layout')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">🧾 Order Detail: #{{ $order->order_number }}</h1>
    
    <div class="flex items-center gap-3">
        {{-- 👇 TOMBOL PRINT STRUK (BARU) --}}
        <a href="#" onclick="window.open('{{ route('admin.orders.print', $order->id) }}', 'Struk', 'width=400,height=600'); return false;" class="bg-gray-800 text-white px-4 py-2 rounded shadow hover:bg-gray-700 flex items-center gap-2 transition">
            <i class="fas fa-print"></i> Print Struk
        </a>

        <a href="{{ route('admin.orders.index') }}" class="text-gray-600 hover:text-gray-900 font-bold flex items-center gap-2 ml-4">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <div class="lg:col-span-2 bg-white shadow-md rounded-lg p-6 h-fit">
        <h3 class="text-lg font-bold mb-4 border-b pb-2 flex items-center gap-2">
            <i class="fas fa-receipt text-gray-400"></i> Order Items
        </h3>
        
        <div class="space-y-4">
            @foreach($order->items as $item)
            <div class="flex justify-between items-start border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                <div>
                    <p class="font-bold text-navy-900 text-lg">{{ $item->product_name }}</p>
                    
                    {{-- Tampilkan Variants & Modifiers --}}
                    <div class="text-sm text-gray-500 mt-1 flex flex-wrap gap-1">
                        @if($item->variants)
                            <span class="bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded border border-blue-100 font-medium">{{ $item->variants }}</span>
                        @endif
                        
                        @if($item->modifiers)
                            @foreach($item->modifiers as $key => $val)
                                <span class="bg-gray-50 text-gray-600 text-xs px-2 py-0.5 rounded border border-gray-100 ml-1">{{ $val }}</span>
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-gray-600 text-sm">{{ $item->quantity }} x Rp {{ number_format($item->unit_price) }}</p>
                    <p class="font-bold text-gray-800">Rp {{ number_format($item->subtotal) }}</p>
                </div>
            </div>
            @endforeach
        </div>

        <div class="flex justify-between items-center mt-6 pt-6 border-t-2 border-dashed border-gray-200">
            <span class="text-xl font-bold text-gray-600">Total Amount</span>
            <span class="text-3xl font-black text-blue-600">Rp {{ number_format($order->total_price) }}</span>
        </div>
    </div>

    <div class="space-y-6">
        
        <div class="bg-white shadow-md rounded-lg p-6 relative overflow-hidden border border-gray-100">
            <div class="absolute -top-4 -right-4 opacity-[0.05] pointer-events-none">
                @if($order->order_type == 'dine_in')
                    <i class="fas fa-utensils text-9xl text-purple-900"></i>
                @else
                    <i class="fas fa-shopping-bag text-9xl text-orange-900"></i>
                @endif
            </div>

            <h3 class="text-gray-400 text-xs font-bold uppercase mb-4 tracking-wider">Order Information</h3>
            
            @if($order->order_type == 'dine_in')
                <div class="mb-6">
                    <span class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md inline-flex items-center gap-2">
                        <i class="fas fa-utensils"></i> DINE IN
                    </span>
                    <div class="mt-4 p-3 bg-purple-50 rounded-lg border border-purple-100">
                        <p class="text-purple-800 text-xs uppercase font-bold mb-1">Table Number</p>
                        <p class="text-4xl font-black text-purple-900">{{ $order->table_number }}</p>
                    </div>
                </div>
            @else
                <div class="mb-6">
                    <span class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md inline-flex items-center gap-2">
                        <i class="fas fa-shopping-bag"></i> TAKE AWAY
                    </span>
                    <p class="text-xs text-orange-600 mt-2 font-medium bg-orange-50 p-2 rounded border border-orange-100">
                        *Pesanan ini untuk dibungkus / diambil.
                    </p>
                </div>
            @endif

            <div class="pt-4 border-t border-gray-100">
                <h3 class="text-gray-400 text-xs font-bold uppercase mb-1">Customer Name</h3>
                <p class="text-lg font-bold text-gray-800">{{ $order->customer_name ?: 'Guest Customer' }}</p>
                <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                    <i class="far fa-clock"></i> {{ $order->created_at->format('d M Y, H:i') }}
                </p>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6 border-l-4 border-yellow-500">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-tasks text-yellow-500"></i> Update Status
            </h3>
            
            <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Order Status</label>
                    <div class="relative">
                        <select name="status" class="w-full border rounded px-3 py-2 outline-none focus:ring-2 focus:ring-yellow-400 bg-gray-50 appearance-none">
                            <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>⏳ Pending (Baru Masuk)</option>
                            <option value="confirmed" {{ $order->status == 'confirmed' ? 'selected' : '' }}>✅ Confirmed (Diterima)</option>
                            <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>🍳 Processing (Dimasak)</option>
                            <option value="ready" {{ $order->status == 'ready' ? 'selected' : '' }}>🔔 Ready (Siap Saji)</option>
                            <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>🎉 Completed (Selesai)</option>
                            <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>❌ Cancelled (Batal)</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Payment Status</label>
                    <div class="relative">
                        <select name="payment_status" class="w-full border rounded px-3 py-2 outline-none focus:ring-2 focus:ring-yellow-400 bg-gray-50 appearance-none">
                            <option value="unpaid" {{ $order->payment_status == 'unpaid' ? 'selected' : '' }}>❌ Unpaid (Belum Bayar)</option>
                            <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>💰 Paid (Lunas)</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 rounded-lg shadow-md transition transform active:scale-95">
                    Update Order
                </button>
            </form>
        </div>
    </div>
</div>
@endsection