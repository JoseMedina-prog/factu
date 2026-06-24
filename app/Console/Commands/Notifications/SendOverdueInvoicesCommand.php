<?php

namespace App\Console\Commands\Notifications;

use App\Events\InvoiceOverdue;
use App\Models\Invoice;
use Illuminate\Console\Command;

class SendOverdueInvoicesCommand extends Command
{
    protected $signature = 'notifications:send-overdue-invoices
                            {--days=0 : Días mínimos de vencimiento para notificar}
                            {--dry-run : Solo mostrar sin enviar}';

    protected $description = 'Detecta facturas vencidas y notifica al cliente y administradores';

    public function handle(): int
    {
        $minDays = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $today = now()->toDateString();

        $this->info("Buscando facturas vencidas con más de {$minDays} días...");

        $invoices = Invoice::query()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereIn('status', ['approved', 'sent'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->with('client')
            ->get();

        $count = 0;
        foreach ($invoices as $invoice) {
            $daysOverdue = (int) $invoice->due_date->diffInDays(now());

            if ($daysOverdue < $minDays) {
                continue;
            }

            if ($dryRun) {
                $this->line("  [DRY-RUN] {$invoice->number} - {$invoice->client?->name} - {$daysOverdue} días vencidos");
                continue;
            }

            try {
                event(new InvoiceOverdue($invoice, $daysOverdue));
                $count++;
                $this->line("  ✓ {$invoice->number} notificada ({$daysOverdue} días)");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$invoice->number}: " . $e->getMessage());
            }
        }

        $this->info("Proceso completado. {$count} factura(s) notificada(s).");

        return self::SUCCESS;
    }
}