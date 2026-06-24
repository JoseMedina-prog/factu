<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Services\PurchasePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchasePaymentController extends Controller
{
    public function __construct(
        protected PurchasePaymentService $paymentService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchasePayment::class);

        $payments = PurchasePayment::with(['supplier:id,name', 'purchaseInvoice:id,number', 'creator:id,name'])
            ->when($request->supplier_id, fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->from, fn ($q) => $q->whereDate('payment_date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('payment_date', '<=', $request->to))
            ->orderByDesc('payment_date')
            ->paginate(15)
            ->withQueryString();

        $tenantId = auth()->user()->tenant_id;
        $stats = $this->paymentService->getStats($tenantId, $request->from, $request->to);

        return view('purchase-payment.index', compact('payments', 'stats'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', PurchasePayment::class);

        $invoice = null;
        if ($request->purchase_invoice_id) {
            $invoice = PurchaseInvoice::with('supplier')->findOrFail($request->purchase_invoice_id);
            $this->authorize('view', $invoice);
        }

        return view('purchase-payment.create', compact('invoice'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PurchasePayment::class);

        $request->validate([
            'purchase_invoice_id' => 'required|exists:purchase_invoices,id',
        ]);

        $invoice = PurchaseInvoice::findOrFail($request->purchase_invoice_id);

        try {
            $payment = $this->paymentService->register($request, $invoice, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.show', $invoice)
            ->with('success', "Pago de \${$payment->amount} registrado.");
    }

    public function show(PurchasePayment $purchasePayment): View
    {
        $this->authorize('view', $purchasePayment);
        $purchasePayment->load(['supplier', 'purchaseInvoice', 'creator', 'confirmer']);

        return view('purchase-payment.show', compact('purchasePayment'));
    }

    public function confirm(PurchasePayment $purchasePayment): RedirectResponse
    {
        $this->authorize('confirm', $purchasePayment);

        try {
            $this->paymentService->confirm($purchasePayment, auth()->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pago confirmado.');
    }

    public function cancel(Request $request, PurchasePayment $purchasePayment): RedirectResponse
    {
        $this->authorize('cancel', $purchasePayment);

        $request->validate(['reason' => 'nullable|string|max:500']);

        try {
            $this->paymentService->cancel($purchasePayment, $request->user(), $request->reason);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pago cancelado.');
    }

    public function destroy(PurchasePayment $purchasePayment): RedirectResponse
    {
        $this->authorize('delete', $purchasePayment);

        $invoiceId = $purchasePayment->purchase_invoice_id;
        $this->paymentService->delete($purchasePayment);

        if ($invoiceId) {
            return redirect()->route('purchases.show', $invoiceId)->with('success', 'Pago eliminado.');
        }

        return redirect()->route('purchase-payments.index')->with('success', 'Pago eliminado.');
    }
}