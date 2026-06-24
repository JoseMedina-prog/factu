<?php

namespace App\Listeners;

use App\Events\InvoiceOverdue;
use App\Models\User;
use App\Notifications\InvoiceOverdueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendOverdueInvoiceNotification implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct() {}

    public function handle(InvoiceOverdue $event): void
    {
        $invoice = $event->invoice->loadMissing('client', 'tenant');

        if (!$invoice->client || !$invoice->client->email) {
            Log::info('Skipping overdue notification: client has no email', [
                'invoice_id' => $invoice->id,
            ]);
        } else {
            try {
                \Illuminate\Support\Facades\Notification::route('mail', $invoice->client->email)
                    ->notify(new InvoiceOverdueNotification($invoice, $event->daysOverdue));
            } catch (\Throwable $e) {
                Log::warning('Failed to send overdue notification', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($invoice->tenant) {
            $admins = User::query()
                ->where('tenant_id', $invoice->tenant->id)
                ->where(function ($q) {
                    $q->where('role', 'admin')
                      ->orWhereHas('roles', fn ($r) => $r->where('name', 'admin'));
                })
                ->get();

            foreach ($admins as $admin) {
                try {
                    $admin->notify(new InvoiceOverdueNotification($invoice, $event->daysOverdue));
                } catch (\Throwable $e) {
                    Log::warning('Failed to send admin overdue notification', [
                        'invoice_id' => $invoice->id,
                        'admin_id' => $admin->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}