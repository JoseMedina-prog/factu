<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class InvoiceValidatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Invoice $invoice,
        public bool $sendToClient = true,
        public ?string $customSubject = null,
        public ?string $customMessage = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->invoice->loadMissing('client', 'items', 'tenant');

        $tenantName = $invoice->tenant?->name ?? config('app.name');
        $subject = $this->customSubject ?? "Factura {$invoice->number} validada por la DIAN";
        $clientName = $invoice->client?->name ?? 'cliente';

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("¡Hola {$clientName}!")
            ->line("Su factura electrónica **{$invoice->number}** ha sido validada exitosamente por la DIAN.")
            ->line("**Monto total:** $" . number_format($invoice->total, 0, ',', '.'))
            ->line("**Fecha de emisión:** " . $invoice->issue_date->format('d/m/Y'))
            ->line("**CUFE:** " . ($invoice->cufe ?? 'N/A'))
            ->action('Ver factura', route('invoices.show', $invoice))
            ->line("Gracias por su preferencia. Si tiene alguna pregunta, no dude en contactarnos.")
            ->salutation("Atentamente, {$tenantName}");

        if ($this->customMessage) {
            $mail->line($this->customMessage);
        }

        $this->attachDocuments($mail);

        return $mail;
    }

    protected function attachDocuments(MailMessage $mail): void
    {
        try {
            $pdfService = app(InvoicePdfService::class);
            $pdfContent = $pdfService->generate($this->invoice, returnAsString: true);

            if ($pdfContent) {
                $mail->attachData(
                    $pdfContent,
                    "factura-{$this->invoice->number}.pdf",
                    ['mime' => 'application/pdf']
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to attach PDF to invoice notification', [
                'invoice_id' => $this->invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'cufe' => $this->invoice->cufe,
            'total' => $this->invoice->total,
            'client_name' => $this->invoice->client?->name,
            'tenant_id' => $this->invoice->tenant_id,
            'type' => 'invoice_validated',
            'message' => "Factura {$this->invoice->number} validada por la DIAN",
        ];
    }
}