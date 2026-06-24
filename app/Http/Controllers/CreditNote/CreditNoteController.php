<?php

namespace App\Http\Controllers\CreditNote;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreditNote\StoreCreditNoteRequest;
use App\Http\Requests\CreditNote\UpdateCreditNoteRequest;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Services\CreditNotePdfService;
use App\Services\CreditNoteService;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CreditNoteController extends Controller
{
    public function __construct(
        protected CreditNoteService $creditNoteService,
        protected CreditNotePdfService $pdfService,
        protected ProductService $productService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CreditNote::class);

        $creditNotes = CreditNote::with('client')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(15);

        $stats = $this->creditNoteService->getStats();

        return view('credit_note.index', compact('creditNotes', 'stats'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', CreditNote::class);

        $clients = Client::orderBy('name')->get();
        $products = $this->productService->getActiveProducts();
        $productsForJs = $products->mapWithKeys(fn($p) => [$p->id => [
            'name' => $p->name,
            'price' => (float) $p->price,
            'tax' => (float) $p->tax,
        ]])->all();
        $invoices = collect();

        if ($request->invoice_id) {
            $invoices = Invoice::where('client_id', $request->invoice_id)
                ->whereIn('status', ['approved', 'sent'])
                ->orderByDesc('issue_date')
                ->get();
        }

        return view('credit_note.create', compact('clients', 'products', 'productsForJs', 'invoices'));
    }

    public function store(StoreCreditNoteRequest $request): RedirectResponse
    {
        $this->authorize('create', CreditNote::class);

        $this->creditNoteService->create($request);
        return redirect()->route('credit-notes.index')->with('success', 'Nota de crédito creada correctamente');
    }

    public function show(CreditNote $creditNote): View
    {
        $this->authorize('view', $creditNote);
        $creditNote->load('items', 'client', 'user');
        return view('credit_note.show', compact('creditNote'));
    }

    public function edit(CreditNote $creditNote): View
    {
        $this->authorize('update', $creditNote);

        $clients = Client::orderBy('name')->get();
        $products = $this->productService->getActiveProducts();
        $creditNote->load('items');
        return view('credit_note.edit', compact('creditNote', 'clients', 'products'));
    }

    public function update(UpdateCreditNoteRequest $request, CreditNote $creditNote): RedirectResponse
    {
        $this->authorize('update', $creditNote);

        $this->creditNoteService->update($creditNote, $request);
        return redirect()->route('credit-notes.index')->with('success', 'Nota de crédito actualizada');
    }

    public function destroy(CreditNote $creditNote): RedirectResponse
    {
        $this->authorize('delete', $creditNote);

        $this->creditNoteService->delete($creditNote);
        return redirect()->route('credit-notes.index')->with('success', 'Nota de crédito eliminada');
    }

    public function approve(CreditNote $creditNote): RedirectResponse
    {
        $this->authorize('approve', $creditNote);

        $this->creditNoteService->approve($creditNote);
        return redirect()->back()->with('success', 'Nota de crédito aprobada');
    }

    public function cancel(CreditNote $creditNote): RedirectResponse
    {
        $this->authorize('cancel', $creditNote);

        $this->creditNoteService->cancel($creditNote);
        return redirect()->back()->with('success', 'Nota de crédito cancelada');
    }

    public function downloadPdf(Request $request, CreditNote $creditNote): Response
    {
        $this->authorize('view', $creditNote);
        return $this->pdfService->generate($creditNote);
    }

    public function streamPdf(Request $request, CreditNote $creditNote): Response
    {
        $this->authorize('view', $creditNote);
        return $this->pdfService->stream($creditNote);
    }
}
