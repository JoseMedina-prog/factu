<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Invoice $invoice,
        public array $errors = [],
        public string $message = ''
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->invoice->loadMissing('client', 'tenant');

        $errorText = $this->message ?: 'La DIAN rechazó esta factura.';
        if (!empty($this->errors)) {
            $errorText .= "\n\nDetalles:\n";
            foreach ($this->errors as $key => $value) {
                if (is_array($value)) {
                    $errorText .= "- {$key}: " . implode(', ', array_map(fn($v) => is_array($v) ? implode(': ', $v) : $v, $value)) . "\n";
                } else {
                    $errorText .= "- {$value}\n";
                }
            }
        }

        return (new MailMessage)
            ->error()
            ->subject("Factura {$invoice->number} rechazada por la DIAN")
            ->greeting("Hola,")
            ->line("La factura electrónica **{$invoice->number}** del cliente **{$invoice->client->name}** fue rechazada por la DIAN.")
            ->line($errorText)
            ->action('Corregir factura', route('invoices.show', $invoice))
            ->line('Por favor revise los datos y vuelva a intentar la validación.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'errors' => $this->errors,
            'message' => $this->message,
            'tenant_id' => $this->invoice->tenant_id,
            'type' => 'invoice_rejected',
            'notification_message' => "Factura {$this->invoice->number} rechazada",
        ];
    }
}