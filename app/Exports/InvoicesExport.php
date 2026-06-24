<?php

namespace App\Exports;

use Illuminate\Support\Collection;

class InvoicesExport
{
    public function __construct(
        protected Collection $invoices
    ) {}

    public function download(string $filename = 'facturas.csv'): \Illuminate\Http\Response
    {
        $csv = $this->generate();

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    protected function generate(): string
    {
        $output = fopen('php://temp', 'r+');

        fputcsv($output, [
            'Número',
            'Fecha',
            'Cliente',
            'Subtotal',
            'IVA',
            'Total',
            'Estado',
        ], ';');

        foreach ($this->invoices as $invoice) {
            fputcsv($output, [
                $invoice->number ?? 'N/A',
                $invoice->issue_date?->format('d/m/Y') ?? 'N/A',
                $invoice->client?->name ?? 'N/A',
                number_format($invoice->subtotal ?? 0, 2, ',', '.'),
                number_format($invoice->tax_total ?? 0, 2, ',', '.'),
                number_format($invoice->total ?? 0, 2, ',', '.'),
                $invoice->status ?? 'N/A',
            ], ';');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
