<?php

namespace App\Listeners;

use App\Events\LowStockReached;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendLowStockNotification implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 60;

    public function handle(LowStockReached $event): void
    {
        $product = $event->product;
        $tenant = $event->tenant;

        if (!$tenant) {
            return;
        }

        $admins = User::query()
            ->where('tenant_id', $tenant->id)
            ->where(function ($q) {
                $q->where('role', 'admin')
                  ->orWhereHas('roles', fn ($r) => $r->where('name', 'admin'));
            })
            ->get();

        foreach ($admins as $admin) {
            try {
                $admin->notify(new LowStockNotification(
                    $product,
                    $event->currentStock,
                    $event->minStock
                ));
            } catch (\Throwable $e) {
                Log::warning('Failed to send low stock notification', [
                    'product_id' => $product->id,
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}