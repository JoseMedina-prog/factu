<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Invoice $invoice,
        public int $daysOverdue = 0
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->invoice->loadMissing('client', 'tenant');

        $clientName = $invoice->client?->name ?? 'cliente';
        $tenantName = $invoice->tenant?->name ?? config('app.name');

        return (new MailMessage)
            ->subject("Factura {$invoice->number} vencida hace {$this->daysOverdue} días")
            ->greeting("Estimado {$clientName},")
            ->line("Le informamos que la factura **{$invoice->number}** se encuentra vencida hace **{$this->daysOverdue} días**.")
            ->line("**Monto pendiente:** $" . number_format($invoice->balance, 0, ',', '.'))
            ->line("**Fecha de vencimiento:** " . $invoice->due_date->format('d/m/Y'))
            ->line("Le solicitamos realizar el pago a la brevedad posible para evitar cargos adicionales.")
            ->action('Ver factura', route('invoices.show', $invoice))
            ->salutation("Atentamente, {$tenantName}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'days_overdue' => $this->daysOverdue,
            'balance' => $this->invoice->balance,
            'client_name' => $this->invoice->client?->name,
            'tenant_id' => $this->invoice->tenant_id,
            'type' => 'invoice_overdue',
            'notification_message' => "Factura {$this->invoice->number} vencida hace {$this->daysOverdue} días",
        ];
    }
}