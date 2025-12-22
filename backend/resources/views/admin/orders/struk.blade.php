<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace; /* Font struk */
            font-size: 12px;
            margin: 0;
            padding: 10px;
            width: 58mm; /* Ukuran kertas thermal standar */
        }
        .header, .footer { text-align: center; margin-bottom: 10px; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 5px 0; }
        .items { width: 100%; border-collapse: collapse; }
        .items td { padding: 2px 0; vertical-align: top; }
        .text-right { text-align: right; }
        
        /* Hapus elemen browser saat print */
        @media print {
            @page { margin: 0; }
            body { margin: 0; padding: 5px; }
        }
    </style>
</head>
<body onload="window.print()"> <div class="header">
        <div class="bold" style="font-size: 16px;">GETCHA COFFEE</div>
        <div>Jl. Kopi Nikmat No. 99</div>
        <div>Bali, Indonesia</div>
        <div class="line"></div>
        <div>{{ $order->created_at->format('d/m/Y H:i') }}</div>
        <div>Order: {{ $order->order_number }}</div>
        <div>Cust: {{ $order->customer_name }}</div>
        <div class="bold">{{ strtoupper($order->order_type) }} {{ $order->table_number ? '#' . $order->table_number : '' }}</div>
    </div>

    <div class="line"></div>

    <table class="items">
        @foreach($order->items as $item)
        <tr>
            <td colspan="2" class="bold">{{ $item->product_name }}</td>
        </tr>
        <tr>
            <td>{{ $item->quantity }} x {{ number_format($item->unit_price, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($item->variants || $item->modifiers)
        <tr>
            <td colspan="2" style="font-size: 10px; color: #555;">
                {{ $item->variants }} {{ $item->modifiers ? implode(', ', $item->modifiers) : '' }}
            </td>
        </tr>
        @endif
        @endforeach
    </table>

    <div class="line"></div>

    <table class="items">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">{{ number_format($order->total_price / 1.11, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tax (11%)</td>
            <td class="text-right">{{ number_format($order->total_price - ($order->total_price / 1.11), 0, ',', '.') }}</td>
        </tr>
        <tr class="bold" style="font-size: 14px;">
            <td>TOTAL</td>
            <td class="text-right">{{ number_format($order->total_price, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="footer">
        <div>Payment: {{ strtoupper($order->payment_method ?? 'CASH') }}</div>
        <div>Status: {{ strtoupper($order->payment_status) }}</div>
        <br>
        <div>--- THANK YOU ---</div>
        <div>Wifi: GetCha_Free / Pass: kopi123</div>
    </div>

</body>
</html>