<?php

namespace App\Services;

use App\Exceptions\NumberingRangeException;
use App\Models\Invoice;
use App\Models\CreditNote;
use App\Models\NumberingRange;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NumberingService
{
    public function assignNextNumber(Tenant $tenant, string $documentType, $issueDate = null): string
    {
        $issueDate = $issueDate ? Carbon::parse($issueDate) : now();

        return DB::transaction(function () use ($tenant, $documentType, $issueDate) {
            $ranges = NumberingRange::query()
                ->where('tenant_id', $tenant->id)
                ->where('document_type', $documentType)
                ->where('is_active', true)
                ->availableOn($issueDate)
                ->orderBy('from_number')
                ->lockForUpdate()
                ->get();

            if ($ranges->isEmpty()) {
                throw NumberingRangeException::noRangeAvailable($documentType, $tenant->name);
            }

            foreach ($ranges as $range) {
                $next = $range->nextNumber();

                if ($next <= $range->to_number) {
                    $range->current_number = $next;
                    $range->save();
                    return $range->formatNumber($next);
                }
            }

            throw NumberingRangeException::rangeExhausted($ranges->first()->prefix);
        });
    }

    public function hasAvailableRange(Tenant $tenant, string $documentType, $issueDate = null): bool
    {
        $issueDate = $issueDate ? Carbon::parse($issueDate) : now();

        return NumberingRange::query()
            ->where('tenant_id', $tenant->id)
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->availableOn($issueDate)
            ->exists();
    }

    public function getExhaustionAlerts(Tenant $tenant, float $threshold = 90.0): array
    {
        return $this->evaluateAlerts($tenant, $threshold, dispatchEvents: true);
    }

    public function getExhaustionAlertsQuietly(Tenant $tenant, float $threshold = 90.0): array
    {
        return $this->evaluateAlerts($tenant, $threshold, dispatchEvents: false);
    }

    protected function evaluateAlerts(Tenant $tenant, float $threshold, bool $dispatchEvents): array
    {
        $alerts = [];

        $ranges = NumberingRange::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get();

        foreach ($ranges as $range) {
            $percentage = $range->usagePercentage();

            if ($range->isExhausted()) {
                $message = "Rango {$range->prefix} agotado";
                $alerts[] = [
                    'level' => 'critical',
                    'range' => $range,
                    'message' => $message,
                ];
                if ($dispatchEvents) {
                    event(new \App\Events\NumberingRangeAlert(
                        $tenant, $range,
                        \App\Events\NumberingRangeAlert::LEVEL_CRITICAL,
                        $message
                    ));
                }
                continue;
            }

            if ($percentage >= $threshold) {
                $message = "Rango {$range->prefix} al {$percentage}% ({$range->availableCount()} restantes)";
                $alerts[] = [
                    'level' => 'warning',
                    'range' => $range,
                    'message' => $message,
                ];
                if ($dispatchEvents) {
                    event(new \App\Events\NumberingRangeAlert(
                        $tenant, $range,
                        \App\Events\NumberingRangeAlert::LEVEL_WARNING,
                        $message
                    ));
                }
            }

            if ($range->expiration_date && $range->expiration_date->lte(now()->addDays(30))) {
                $message = "Rango {$range->prefix} vence el {$range->expiration_date->format('Y-m-d')}";
                $alerts[] = [
                    'level' => 'warning',
                    'range' => $range,
                    'message' => $message,
                ];
                if ($dispatchEvents) {
                    event(new \App\Events\NumberingRangeAlert(
                        $tenant, $range,
                        \App\Events\NumberingRangeAlert::LEVEL_WARNING,
                        $message
                    ));
                }
            }
        }

        return $alerts;
    }

    public function validateNoOverlap(NumberingRange $range): void
    {
        $others = NumberingRange::query()
            ->where('tenant_id', $range->tenant_id)
            ->where('document_type', $range->document_type)
            ->where('id', '!=', $range->id ?? 0)
            ->get();

        foreach ($others as $other) {
            if ($range->overlapsWith($other)) {
                throw NumberingRangeException::rangeOverlaps($range->prefix);
            }
        }
    }

    public function createDefaultRanges(Tenant $tenant): void
    {
        $year = (int) date('Y');
        $nextYear = $year + 1;

        $existingInvoice = $range = NumberingRange::query()
            ->where('tenant_id', $tenant->id)
            ->where('document_type', NumberingRange::TYPE_INVOICE)
            ->exists();

        if (!$existingInvoice) {
            $invoicePrefix = $tenant->invoice_prefix ?: 'INV';
            NumberingRange::create([
                'tenant_id' => $tenant->id,
                'document_type' => NumberingRange::TYPE_INVOICE,
                'prefix' => $invoicePrefix,
                'from_number' => 1,
                'to_number' => 99999,
                'current_number' => 0,
                'resolution_number' => 'RES-' . $year . '-001',
                'resolution_date' => now()->toDateString(),
                'expiration_date' => Carbon::create($nextYear, 12, 31)->toDateString(),
                'technical_key' => null,
                'is_active' => true,
                'notes' => 'Rango por defecto creado automáticamente',
            ]);
        }

        $existingCreditNote = NumberingRange::query()
            ->where('tenant_id', $tenant->id)
            ->where('document_type', NumberingRange::TYPE_CREDIT_NOTE)
            ->exists();

        if (!$existingCreditNote) {
            $creditNotePrefix = $tenant->credit_note_prefix ?: 'NC';
            NumberingRange::create([
                'tenant_id' => $tenant->id,
                'document_type' => NumberingRange::TYPE_CREDIT_NOTE,
                'prefix' => $creditNotePrefix,
                'from_number' => 1,
                'to_number' => 9999,
                'current_number' => 0,
                'resolution_number' => 'RES-' . $year . '-002',
                'resolution_date' => now()->toDateString(),
                'expiration_date' => Carbon::create($nextYear, 12, 31)->toDateString(),
                'technical_key' => null,
                'is_active' => true,
                'notes' => 'Rango por defecto creado automáticamente',
            ]);
        }
    }
}