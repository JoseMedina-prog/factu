<?php

namespace App\Events;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockReached
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public Product $product,
        public float $currentStock,
        public float $minStock
    ) {}
}