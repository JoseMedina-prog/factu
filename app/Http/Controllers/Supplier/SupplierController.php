<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::withSum(['purchaseInvoices as outstanding' => fn ($q) => $q->whereIn('payment_status', ['unpaid', 'partial', 'overpaid'])], 'balance')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($w) use ($request) {
                    $w->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('document', 'like', '%' . $request->search . '%');
                });
            })
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('supplier.index', compact('suppliers'));
    }

    public function create(): View
    {
        $this->authorize('create', Supplier::class);
        return view('supplier.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Supplier::class);

        $data = $request->validate([
            'name' => 'required|string|max:200',
            'document' => 'nullable|string|max:30',
            'document_type' => 'required|in:NIT,CC,CE',
            'contact_name' => 'nullable|string|max:200',
            'email' => 'nullable|email|max:200',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:200',
            'city' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'bank_account_type' => 'nullable|in:savings,checking',
            'notes' => 'nullable|string|max:1000',
        ]);

        $supplier = $this->supplierService->create($data);

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', "Proveedor {$supplier->name} creado.");
    }

    public function show(Supplier $supplier): View
    {
        $this->authorize('view', $supplier);

        $supplier->load(['purchaseInvoices' => fn ($q) => $q->orderByDesc('issue_date')->limit(20)]);

        $stats = [
            'total_invoices' => $supplier->purchaseInvoices()->count(),
            'outstanding' => $supplier->outstandingBalance(),
            'total_purchased' => (float) $supplier->purchaseInvoices()->sum('total'),
            'total_paid' => (float) $supplier->purchaseInvoices()->sum('paid_amount'),
        ];

        return view('supplier.show', compact('supplier', 'stats'));
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorize('update', $supplier);
        return view('supplier.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        $data = $request->validate([
            'name' => 'required|string|max:200',
            'document' => 'nullable|string|max:30',
            'document_type' => 'required|in:NIT,CC,CE',
            'contact_name' => 'nullable|string|max:200',
            'email' => 'nullable|email|max:200',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:200',
            'city' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'bank_account_type' => 'nullable|in:savings,checking',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $this->supplierService->update($supplier, $data);

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Proveedor actualizado.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        if ($supplier->purchaseInvoices()->exists()) {
            return back()->with('error', 'No se puede eliminar un proveedor con facturas asociadas.');
        }

        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Proveedor eliminado.');
    }
}