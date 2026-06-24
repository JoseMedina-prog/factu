<?php

namespace App\Console\Commands\Notifications;

use App\Models\Tenant;
use App\Services\NumberingService;
use Illuminate\Console\Command;

class CheckNumberingRangesCommand extends Command
{
    protected $signature = 'notifications:check-numbering-ranges
                            {--tenant= : ID de tenant específico}
                            {--threshold=90 : Umbral de uso para alertar (%)}
                            {--dry-run : Solo mostrar sin enviar}';

    protected $description = 'Verifica rangos de numeración y notifica a admins sobre agotamiento o expiración';

    public function handle(NumberingService $numberingService): int
    {
        $threshold = (float) $this->option('threshold');
        $dryRun = $this->option('dry-run');

        $tenantsQuery = Tenant::query()->where('is_active', true);

        if ($tenantId = $this->option('tenant')) {
            $tenantsQuery->where('id', $tenantId);
        }

        $tenants = $tenantsQuery->get();

        $this->info("Verificando rangos de {$tenants->count()} tenant(s)..." . ($dryRun ? ' (DRY-RUN)' : ''));

        $totalAlerts = 0;
        foreach ($tenants as $tenant) {
            $alerts = $dryRun
                ? $numberingService->getExhaustionAlertsQuietly($tenant, $threshold)
                : $numberingService->getExhaustionAlerts($tenant, $threshold);

            if (count($alerts) > 0) {
                $this->line("Tenant {$tenant->name} (ID: {$tenant->id}):");
                foreach ($alerts as $alert) {
                    $level = $alert['level'] === 'critical' ? '🔴' : '🟡';
                    $this->line("  {$level} {$alert['message']}");
                    $totalAlerts++;
                }
            }
        }

        $this->info("Total alertas: {$totalAlerts}");

        return self::SUCCESS;
    }
}