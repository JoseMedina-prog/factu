<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Services\ClientService;
use App\Services\FactusService;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected ClientService $clientService,
        protected ProductService $productService,
        protected FactusService $factusService,
        protected InvoicePdfService $pdfService
    ) {}

    public function index(Request $request): View
    {
        $invoices = Invoice::with('client')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->search, fn($q) => $q->where('number', 'like', '%' . $request->search . '%'))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $stats = $this->invoiceService->getInvoiceStats();

        return view('invoice.index', compact('invoices', 'stats'));
    }

    public function create(Request $request): View
    {
        $clients = $this->clientService->getActiveClients();
        $products = $this->productService->getActiveProducts();
        $productsForJs = $products->mapWithKeys(fn($p) => [$p->id => [
            'name' => $p->name,
            'price' => (float) $p->price,
            'tax' => (float) $p->tax,
        ]])->all();

        return view('invoice.create', compact('clients', 'products', 'productsForJs'));
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->invoiceService->create($request);
        return redirect()->route('invoices.index')->with('success', 'Factura creada correctamente');
    }

    public function show(Request $request, Invoice $invoice): View
    {
        $invoice->load(['client', 'items.product', 'integrationLogs', 'payments.creator', 'payments.confirmer']);
        return view('invoice.show', compact('invoice'));
    }

    public function edit(Request $request, Invoice $invoice): View
    {
        $this->authorize('update', $invoice);

        $clients = $this->clientService->getActiveClients();
        $products = $this->productService->getActiveProducts();
        $invoice->load('items');

        return view('invoice.edit', compact('invoice', 'clients', 'products'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->reference_code || $invoice->is_validated) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Esta factura ya fue validada por la DIAN y no se puede editar. Para correcciones, crea una Nota Crédito.');
        }

        $this->invoiceService->update($invoice, $request);
        return redirect()->route('invoices.index')->with('success', 'Factura actualizada correctamente');
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        if ($invoice->reference_code || $invoice->is_validated) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Esta factura ya fue validada por la DIAN. Para eliminarla, primero elimínala en Factus.');
        }

        $this->invoiceService->delete($invoice);
        return redirect()->route('invoices.index')->with('success', 'Factura eliminada correctamente');
    }

    public function send(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('send', $invoice);

        $result = $this->factusService->validateInvoice($invoice);

        if ($result['success'] ?? false) {
            $cufe = $result['cufe'] ?? null;
            $message = $cufe
                ? 'Factura validada exitosamente. CUFE: ' . substr($cufe, 0, 20) . '...'
                : 'Factura validada exitosamente';
            return redirect()->route('invoices.show', $invoice)->with('success', $message);
        }

        $errors = $result['errors'] ?? [];
        $errorMessage = $result['message'] ?? 'Error al validar la factura';
        if (!empty($errors) && is_array($errors)) {
            $first = reset($errors);
            if (is_array($first)) {
                $errorMessage = implode(', ', array_map(fn($e) => is_array($e) ? implode(': ', $e) : $e, $errors));
            }
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('error', 'No se pudo validar: ' . $errorMessage)
            ->with('factus_errors', $errors);
    }

    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('cancel', $invoice);

        if (!$invoice->reference_code) {
            return redirect()->route('invoices.index')
                ->with('error', 'Esta factura no ha sido enviada a Factus. Puedes eliminarla localmente desde el listado.');
        }

        $result = $this->factusService->deleteInvoice($invoice);

        if ($result['success'] ?? false) {
            return redirect()->route('invoices.index')
                ->with('success', 'Factura eliminada de Factus (no había sido entregada al cliente). Sigue registrada ante la DIAN como referencia, pero ya no es válida.');
        }

        if ($result['blocked'] ?? false) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', $result['message'])
                ->with('factus_block_reason', 'notified');
        }

        \Illuminate\Support\Facades\Log::error('Factus deleteInvoice failed', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
            'tenant_id' => $invoice->tenant_id,
            'result' => $result,
        ]);

        $userMessage = $result['message']
            ?? 'No pudimos comunicarnos con Factus. Intenta nuevamente en unos minutos.';
        return redirect()->route('invoices.show', $invoice)
            ->with('error', 'No se pudo eliminar: ' . $userMessage);
    }

    public function refreshStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('view', $invoice);

        $status = $this->factusService->checkInvoiceStatus($invoice);

        if (!isset($status['error'])) {
            $invoice->update(['factus_response' => $status]);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with(isset($status['error']) ? 'error' : 'success',
                isset($status['error']) ? 'Error al consultar: ' . $status['error'] : 'Estado actualizado');
    }

    public function downloadFactusPdf(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $pdfContent = $this->factusService->downloadInvoicePdf($invoice);

        if ($pdfContent === null) {
            return $this->pdfService->stream($invoice);
        }

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="factura-' . $invoice->number . '.pdf"',
        ]);
    }

    public function downloadFactusXml(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $xmlContent = $this->factusService->downloadInvoiceXml($invoice);

        if ($xmlContent === null) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'No se pudo descargar el XML desde Factus');
        }

        return response($xmlContent, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="factura-' . $invoice->number . '.xml"',
        ]);
    }

    public function downloadPdf(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);
        return $this->pdfService->generate($invoice);
    }

    public function streamPdf(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);
        return $this->pdfService->stream($invoice);
    }
}
