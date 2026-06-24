<?php

namespace App\Services;

use App\Models\CreditNote;
use Barryvdh\DomPDF\Facade\Pdf;

class CreditNotePdfService
{
    public function generate(CreditNote $creditNote): \Illuminate\Http\Response
    {
        if (!$creditNote->relationLoaded('client') || !$creditNote->relationLoaded('items') || !$creditNote->relationLoaded('tenant') || !$creditNote->relationLoaded('invoice')) {
            $creditNote->load('client', 'items', 'tenant', 'invoice');
        }

        $pdf = Pdf::loadView('pdf.credit-note', [
            'creditNote' => $creditNote,
            'company' => $creditNote->tenant,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('nota_credito_' . ($creditNote->number ?? 'borrador') . '.pdf');
    }

    public function stream(CreditNote $creditNote): \Illuminate\Http\Response
    {
        if (!$creditNote->relationLoaded('client') || !$creditNote->relationLoaded('items') || !$creditNote->relationLoaded('tenant') || !$creditNote->relationLoaded('invoice')) {
            $creditNote->load('client', 'items', 'tenant', 'invoice');
        }

        $pdf = Pdf::loadView('pdf.credit-note', [
            'creditNote' => $creditNote,
            'company' => $creditNote->tenant,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('nota_credito_' . ($creditNote->number ?? 'borrador') . '.pdf');
    }
}
