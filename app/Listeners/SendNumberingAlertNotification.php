<?php

namespace App\Listeners;

use App\Events\NumberingRangeAlert;
use App\Models\User;
use App\Notifications\NumberingRangeAlertNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendNumberingAlertNotification implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 60;

    public function handle(NumberingRangeAlert $event): void
    {
        $tenant = $event->tenant;
        $range = $event->range;

        $admins = User::query()
            ->where('tenant_id', $tenant->id)
            ->where(function ($q) {
                $q->where('role', 'admin')
                  ->orWhereHas('roles', fn ($r) => $r->where('name', 'admin'));
            })
            ->get();

        if ($admins->isEmpty()) {
            Log::info('No admins to notify for numbering range alert', [
                'tenant_id' => $tenant->id,
                'range_id' => $range->id,
            ]);
            return;
        }

        foreach ($admins as $admin) {
            try {
                $admin->notify(new NumberingRangeAlertNotification($range, $event->level, $event->message));
            } catch (\Throwable $e) {
                Log::warning('Failed to send numbering range alert', [
                    'range_id' => $range->id,
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}