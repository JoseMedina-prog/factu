<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PurchasePayment;
use App\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AccountsPayableController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchasePayment::class);

        $tenantId = auth()->user()->tenant_id;
        $data = $this->supplierService->getAccountsPayable($tenantId, $request->as_of);

        return view('accounts-payable.index', array_merge($data, [
            'asOf' => $data['as_of'],
        ]));
    }

    public function export(Request $request): Response
    {
        $this->authorize('viewAny', PurchasePayment::class);

        $tenantId = auth()->user()->tenant_id;
        $asOf = $request->get('as_of', now()->toDateString());
        $data = $this->supplierService->getAccountsPayable($tenantId, $asOf);

        $csv = "Número;Proveedor;Emisión;Vencimiento;Días vencidos;Saldo;Cubo\n";
        foreach ($data['groups'] as $bucket => $invoices) {
            foreach ($invoices as $invoice) {
                $csv .= implode(';', [
                    $invoice->number,
                    $invoice->supplier->name ?? 'N/A',
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
            'Content-Disposition' => "attachment; filename=\"cuentas-por-pagar-{$asOf}.csv\"",
        ]);
    }
}