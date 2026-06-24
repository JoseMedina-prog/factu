<?php

namespace App\Listeners;

use App\Events\InvoiceRejected;
use App\Models\User;
use App\Notifications\InvoiceRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendAdminInvoiceNotification implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 60;

    public function handle(InvoiceRejected $event): void
    {
        $invoice = $event->invoice->loadMissing('tenant');
        $tenant = $invoice->tenant;

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
                $admin->notify(new InvoiceRejectedNotification(
                    $invoice,
                    $event->errors,
                    $event->message
                ));
            } catch (\Throwable $e) {
                Log::warning('Failed to send rejection notification', [
                    'invoice_id' => $invoice->id,
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}