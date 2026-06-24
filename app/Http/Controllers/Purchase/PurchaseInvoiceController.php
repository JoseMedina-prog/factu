<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Services\PurchasePaymentService;
use App\Services\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseInvoiceController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService,
        protected PurchasePaymentService $paymentService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseInvoice::class);

        $purchases = PurchaseInvoice::with(['supplier:id,name,document', 'creator:id,name'])
            ->when($request->search, fn ($q) => $q->where('number', 'like', '%' . $request->search . '%'))
            ->when($request->supplier_id, fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->payment_status, fn ($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->from, fn ($q) => $q->whereDate('issue_date', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('issue_date', '<=', $request->to))
            ->orderByDesc('issue_date')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => PurchaseInvoice::count(),
            'unpaid' => PurchaseInvoice::where('payment_status', 'unpaid')->count(),
            'partial' => PurchaseInvoice::where('payment_status', 'partial')->count(),
            'paid' => PurchaseInvoice::where('payment_status', 'paid')->count(),
            'total_amount' => (float) PurchaseInvoice::sum('total'),
            'outstanding' => (float) PurchaseInvoice::whereIn('payment_status', ['unpaid', 'partial'])->sum('balance'),
        ];

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        return view('purchase.index', compact('purchases', 'stats', 'suppliers'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', PurchaseInvoice::class);

        $supplier = null;
        if ($request->supplier_id) {
            $supplier = Supplier::findOrFail($request->supplier_id);
            $this->authorize('view', $supplier);
        }

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name', 'document']);
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'price', 'tax', 'cost', 'unit_of_measure']);

        return view('purchase.create', compact('supplier', 'suppliers', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PurchaseInvoice::class);

        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'number' => 'required|string|max:50',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'received_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'auto_register_stock' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0|max:100',
            'items.*.retention' => 'nullable|numeric|min:0|max:100',
        ]);

        $supplier = Supplier::findOrFail($data['supplier_id']);
        $this->authorize('view', $supplier);

        $data['auto_register_stock'] = $request->boolean('auto_register_stock');

        $purchase = $this->supplierService->recordPurchase($supplier, $data, $request->user());

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', "Factura de compra {$purchase->number} registrada. Saldo: $" . number_format($purchase->balance, 0, ',', '.'));
    }

    public function show(PurchaseInvoice $purchase): View
    {
        $this->authorize('view', $purchase);
        $purchase->load(['supplier', 'items.product', 'payments.creator', 'creator']);

        return view('purchase.show', compact('purchase'));
    }

    public function edit(PurchaseInvoice $purchase): View
    {
        $this->authorize('update', $purchase);
        $purchase->load('items');
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'price', 'tax', 'unit_of_measure']);

        return view('purchase.edit', compact('purchase', 'suppliers', 'products'));
    }

    public function update(Request $request, PurchaseInvoice $purchase): RedirectResponse
    {
        $this->authorize('update', $purchase);

        $data = $request->validate([
            'number' => 'required|string|max:50',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'received_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $purchase->update($data);

        return redirect()->route('purchases.show', $purchase)->with('success', 'Factura actualizada.');
    }

    public function destroy(PurchaseInvoice $purchase): RedirectResponse
    {
        $this->authorize('delete', $purchase);
        $purchase->items()->delete();
        $purchase->delete();

        return redirect()->route('purchases.index')->with('success', 'Factura eliminada.');
    }

    public function cancel(Request $request, PurchaseInvoice $purchase): RedirectResponse
    {
        $this->authorize('cancel', $purchase);

        $purchase->update([
            'status' => PurchaseInvoice::STATUS_CANCELLED,
            'balance' => 0,
            'payment_status' => PurchaseInvoice::PAYMENT_PAID,
        ]);

        return back()->with('success', 'Factura cancelada.');
    }
}