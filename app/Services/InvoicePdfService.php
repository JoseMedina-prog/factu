<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfService
{
    public function generate(Invoice $invoice, bool $returnAsString = false)
    {
        $this->ensureRelationsLoaded($invoice);

        $pdf = $this->buildPdf($invoice);

        if ($returnAsString) {
            return $pdf->output();
        }

        return $pdf->download('factura_' . ($invoice->number ?? 'borrador') . '.pdf');
    }

    public function stream(Invoice $invoice, bool $returnAsString = false)
    {
        $this->ensureRelationsLoaded($invoice);

        $pdf = $this->buildPdf($invoice);

        if ($returnAsString) {
            return $pdf->output();
        }

        return $pdf->stream('factura_' . ($invoice->number ?? 'borrador') . '.pdf');
    }

    protected function ensureRelationsLoaded(Invoice $invoice): void
    {
        if (!$invoice->relationLoaded('client')
            || !$invoice->relationLoaded('items')
            || !$invoice->relationLoaded('tenant')) {
            $invoice->load('client', 'items', 'tenant');
        }
    }

    protected function buildPdf(Invoice $invoice)
    {
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $invoice->tenant,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }
}
