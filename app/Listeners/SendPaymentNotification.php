<?php

namespace App\Listeners;

use App\Events\PaymentRegistered;
use App\Models\User;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentNotification implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 60;

    public function handle(PaymentRegistered $event): void
    {
        $payment = $event->payment->loadMissing('client', 'invoice', 'tenant');
        $client = $payment->client;
        $tenant = $payment->tenant;

        if (!$payment->isConfirmed()) {
            return;
        }

        if ($client && $client->email) {
            try {
                \Illuminate\Support\Facades\Notification::route('mail', $client->email)
                    ->notify(new PaymentReceivedNotification($payment));
            } catch (\Throwable $e) {
                Log::warning('Failed to send payment notification to client', [
                    'payment_id' => $payment->id,
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
                    $admin->notify(new PaymentReceivedNotification($payment, sendToClient: false));
                } catch (\Throwable $e) {
                    Log::warning('Failed to send admin payment notification', [
                        'payment_id' => $payment->id,
                        'admin_id' => $admin->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}