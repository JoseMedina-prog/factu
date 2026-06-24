<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Payment $payment,
        public bool $sendToClient = true
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payment = $this->payment->loadMissing('client', 'invoice', 'tenant');

        $clientName = $payment->client?->name ?? 'cliente';
        $tenantName = $payment->tenant?->name ?? config('app.name');
        $invoiceNumber = $payment->invoice?->number ?? 'anticipo';
        $balance = $payment->invoice ? (float) $payment->invoice->balance : 0.0;
        $isFullPayment = $payment->invoice && $payment->invoice->isFullyPaid();

        $mail = (new MailMessage)
            ->subject("Pago recibido - Factura {$invoiceNumber}")
            ->greeting("¡Hola {$clientName}!")
            ->line("Hemos recibido su pago por $" . number_format($payment->amount, 0, ',', '.') . ".")
            ->line("**Método:** " . $payment->method_label)
            ->line("**Fecha:** " . $payment->payment_date->format('d/m/Y'))
            ->line("**Referencia:** " . ($payment->reference ?? 'N/A'));

        if ($payment->invoice) {
            $mail->line("**Factura:** {$invoiceNumber}")
                ->line("**Saldo restante:** $" . number_format($balance, 0, ',', '.'))
                ->line($isFullPayment ? "✅ Su factura está completamente pagada." : "Su factura tiene un saldo pendiente.");
            $mail->action('Ver factura', route('invoices.show', $payment->invoice));
        }

        $mail->line("Gracias por su pago.")
            ->salutation("Atentamente, {$tenantName}");

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'invoice_id' => $this->payment->invoice_id,
            'invoice_number' => $this->payment->invoice?->number,
            'amount' => $this->payment->amount,
            'method' => $this->payment->method,
            'client_name' => $this->payment->client?->name,
            'tenant_id' => $this->payment->tenant_id,
            'type' => 'payment_received',
            'notification_message' => "Pago de \${$this->payment->amount} recibido",
        ];
    }
}