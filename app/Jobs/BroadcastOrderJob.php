<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\DeliveryBroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BroadcastOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $orderId) {}

    public function handle(): void
    {
        $order = Order::find($this->orderId);
        if (!$order) {
            Log::warning("[BroadcastOrderJob] Order #{$this->orderId} not found.");
            return;
        }

        DeliveryBroadcastService::broadcast($order);
    }
}
