<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AccountsReceivableController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $tenantId = auth()->user()->tenant_id;
        $asOf = $request->get('as_of', now()->toDateString());

        $data = $this->paymentService->getAccountsReceivable($tenantId, $asOf);

        return view('accounts-receivable.index', array_merge($data, [
            'asOf' => $asOf,
        ]));
    }

    public function export(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $tenantId = auth()->user()->tenant_id;
        $asOf = $request->get('as_of', now()->toDateString());
        $data = $this->paymentService->getAccountsReceivable($tenantId, $asOf);

        $csv = "Número;Cliente;Emisión;Vencimiento;Días vencidos;Saldo;Cubo\n";
        foreach ($data['groups'] as $bucket => $invoices) {
            foreach ($invoices as $invoice) {
                $csv .= implode(';', [
                    $invoice->number,
                    $invoice->client->name ?? 'N/A',
                    $invoice->issue_date->format('Y-m-d'),
                    $invoice->due_date->format('Y-m-d'),
                    $invoice->daysOverdue(),
                    number_format($invoice->balance, 2, ',', '.'),
                    $bucket,
                ]) . "\n";
            }
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"cuentas-por-cobrar-{$asOf}.csv\"",
        ]);
    }
}