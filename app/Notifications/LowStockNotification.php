<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Product $product,
        public float $currentStock,
        public float $minStock
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->product->loadMissing('tenant');

        $tenantName = $product->tenant?->name ?? config('app.name');
        $isOut = $this->currentStock <= 0;
        $stockStatus = $isOut ? 'agotado' : 'bajo';
        $subjectPrefix = $isOut ? 'Producto agotado' : 'Stock bajo';

        $mail = (new MailMessage)
            ->subject("{$subjectPrefix}: {$product->name}")
            ->greeting("Hola equipo de {$tenantName},")
            ->line("El producto {$product->name} tiene stock {$stockStatus}.")
            ->line("Stock actual: {$this->currentStock} {$product->unit_of_measure_label}")
            ->line("Stock mínimo: {$this->minStock} {$product->unit_of_measure_label}");

        if ($isOut) {
            $mail->error()
                ->line('El producto se ha agotado completamente. Considere reabastecer urgentemente.');
        } else {
            $mail->line('Recomendamos reabastecer el inventario para evitar desabastecimiento.');
        }

        $mail->action('Ver producto', route('products.show', $product))
            ->line('Este es un mensaje automático del sistema de inventario.');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $isOut = $this->currentStock <= 0;

        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'current_stock' => $this->currentStock,
            'min_stock' => $this->minStock,
            'tenant_id' => $this->product->tenant_id,
            'type' => $isOut ? 'product_out_of_stock' : 'product_low_stock',
            'notification_message' => $isOut
                ? "{$this->product->name} agotado"
                : "{$this->product->name} con stock bajo ({$this->currentStock} / {$this->minStock})",
        ];
    }
}