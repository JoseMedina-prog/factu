<?php

namespace App\Notifications;

use App\Models\NumberingRange;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NumberingRangeAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public NumberingRange $range,
        public string $level,
        public string $alertMessage
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $range = $this->range->loadMissing('tenant');
        $tenantName = $range->tenant?->name ?? config('app.name');

        $documentLabel = $range->document_type === NumberingRange::TYPE_INVOICE ? 'factura' : 'nota crédito';
        $isCritical = $this->level === 'critical';

        $mail = (new MailMessage)
            ->subject("Alerta de rango de numeración {$range->prefix}")
            ->greeting("Hola equipo de {$tenantName},")
            ->line("El rango de numeración **{$range->prefix}** para {$documentLabel} requiere su atención.")
            ->line("**Detalle:** {$this->alertMessage}");

        if ($isCritical) {
            $mail->error();
        }

        $mail->line("**Prefijo:** {$range->prefix}")
            ->line("**Usados:** " . number_format($range->current_number - $range->from_number + 1) . " / " . number_format($range->to_number - $range->from_number + 1))
            ->line("**Restantes:** " . number_format($range->availableCount()))
            ->line("**% Uso:** {$range->usagePercentage()}%")
            ->when($range->expiration_date, fn ($m) => $m->line("**Vencimiento:** " . $range->expiration_date->format('d/m/Y')))
            ->action('Gestionar numeración', route('settings.numbering.index'));

        if ($isCritical) {
            $mail->line('⚠️ Configure un nuevo rango antes de seguir facturando.');
        } else {
            $mail->line('Recomendamos planificar un nuevo rango con anticipación.');
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'range_id' => $this->range->id,
            'prefix' => $this->range->prefix,
            'document_type' => $this->range->document_type,
            'level' => $this->level,
            'usage_percentage' => $this->range->usagePercentage(),
            'tenant_id' => $this->range->tenant_id,
            'type' => 'numbering_range_alert',
            'notification_message' => $this->alertMessage,
        ];
    }
}