<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Pastikan pakai Now
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function broadcastOn(): array
    {
        // ❌ JANGAN PAKE INI (Bikin Error kalau nama ada spasi)
        // return [new Channel('orders.' . $this->order->customer_name)];

        // ✅ PAKAI INI (Aman, satu saluran untuk semua)
        return [
            new Channel('public-orders'), 
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }
}