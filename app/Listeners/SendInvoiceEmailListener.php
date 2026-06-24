<?php

namespace App\Listeners;

use App\Events\InvoiceValidated;
use App\Models\User;
use App\Notifications\InvoiceValidatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendInvoiceEmailListener implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 60;

    public function handle(InvoiceValidated $event): void
    {
        $invoice = $event->invoice->loadMissing('client', 'tenant', 'items');

        $client = $invoice->client;
        $tenant = $invoice->tenant;

        if (!$client || !$client->email) {
            Log::info('Skipping invoice email: client has no email', [
                'invoice_id' => $invoice->id,
                'client_id' => $client?->id,
            ]);
        } else {
            try {
                \Illuminate\Support\Facades\Notification::route('mail', $client->email)
                    ->notify(new InvoiceValidatedNotification($invoice));
            } catch (\Throwable $e) {
                Log::error('Failed to send invoice notification to client', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($tenant) {
            $admins = User::query()
                ->where('tenant_id', $tenant->id)
                ->where(function ($q) {
                    $q->where('role', 'admin')
                      ->orWhereHas('roles', fn ($r) => $r->where('name', 'admin'));
                })
                ->get();

            foreach ($admins as $admin) {
                try {
                    $admin->notify(new InvoiceValidatedNotification($invoice, sendToClient: false));
                } catch (\Throwable $e) {
                    Log::warning('Failed to send admin invoice notification', [
                        'invoice_id' => $invoice->id,
                        'admin_id' => $admin->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}