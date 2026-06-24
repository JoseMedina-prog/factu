<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\FactusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Invoice $invoice
    ) {
        $this->onQueue('invoices');
    }

    public function handle(FactusService $factusService): void
    {
        if ($this->invoice->reference_code) {
            Log::info('SendInvoiceJob: Invoice ' . $this->invoice->number . ' already validated, skipping.');
            return;
        }

        if (!in_array($this->invoice->status, ['draft', 'pending'], true)) {
            Log::info('SendInvoiceJob: Invoice ' . $this->invoice->number . ' in status ' . $this->invoice->status . ', skipping.');
            return;
        }

        Log::info('SendInvoiceJob: Validating invoice ' . $this->invoice->number . ' in Factus');

        try {
            $result = $factusService->validateInvoice($this->invoice);

            if ($result['success'] ?? false) {
                Log::info('SendInvoiceJob: Invoice ' . $this->invoice->number . ' validated. CUFE: ' . ($result['cufe'] ?? 'N/A'));
            } else {
                Log::warning('SendInvoiceJob: Invoice ' . $this->invoice->number . ' failed: ' . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Throwable $e) {
            Log::error('SendInvoiceJob: Invoice ' . $this->invoice->number . ' exception: ' . $e->getMessage());
            throw $e;
        }
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendInvoiceJob: Invoice ' . $this->invoice->number . ' permanently failed', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->invoice->update(['status' => 'error']);
    }
}

