<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::with(['client:id,name', 'invoice:id,number', 'creator:id,name'])
            ->when($request->client_id, fn ($q) => $q->where('client_id', $request->client_id))
            ->when($request->method, fn ($q) => $q->where('method', $request->method))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->start_date, fn ($q) => $q->whereDate('payment_date', '>=', $request->start_date))
            ->when($request->end_date, fn ($q) => $q->whereDate('payment_date', '<=', $request->end_date))
            ->orderByDesc('payment_date')
            ->paginate(15)
            ->withQueryString();

        $tenantId = auth()->user()->tenant_id;
        $stats = $this->paymentService->getStats(
            $tenantId,
            $request->start_date,
            $request->end_date
        );

        return view('payment.index', compact('payments', 'stats'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $invoice = null;
        $clients = Client::orderBy('name')->get();

        if ($request->invoice_id) {
            $invoice = Invoice::with('client')->findOrFail($request->invoice_id);
            $this->authorize('view', $invoice);

            if ($invoice->isFullyPaid()) {
                return redirect()
                    ->route('invoices.show', $invoice)
                    ->with('info', "La factura {$invoice->number} ya está pagada completamente.");
            }
        }

        return view('payment.create', compact('invoice', 'clients'));
    }

    public function store(\App\Http\Requests\Payment\StorePaymentRequest $request): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $invoice = Invoice::findOrFail($request->invoice_id);

        try {
            $payment = $this->paymentService->register($request, $invoice, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', "Pago de \${$payment->amount} registrado correctamente.");
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['client', 'invoice', 'creator', 'confirmer']);

        return view('payment.show', compact('payment'));
    }

    public function confirm(Payment $payment): RedirectResponse
    {
        $this->authorize('confirm', $payment);

        try {
            $this->paymentService->confirm($payment, auth()->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pago confirmado correctamente.');
    }

    public function cancel(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('cancel', $payment);

        $request->validate(['reason' => 'nullable|string|max:500']);

        try {
            $this->paymentService->cancel($payment, $request->user(), $request->reason);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pago cancelado correctamente.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('delete', $payment);

        $invoiceId = $payment->invoice_id;
        $this->paymentService->delete($payment);

        if ($invoiceId) {
            return redirect()->route('invoices.show', $invoiceId)
                ->with('success', 'Pago eliminado.');
        }

        return redirect()->route('payments.index')
            ->with('success', 'Pago eliminado.');
    }
}