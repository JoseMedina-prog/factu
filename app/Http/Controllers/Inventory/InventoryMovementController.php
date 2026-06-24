<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreAdjustmentRequest;
use App\Http\Requests\Inventory\StoreEntryRequest;
use App\Http\Requests\Inventory\StoreExitRequest;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryMovementController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function index(Request $request): View
    {
        $tenantId = auth()->user()->tenant_id;

        $movements = InventoryMovement::with(['product:id,name,sku,unit_of_measure', 'user:id,name', 'invoice:id,number'])
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->start_date, fn ($q) => $q->whereDate('movement_date', '>=', $request->start_date))
            ->when($request->end_date, fn ($q) => $q->whereDate('movement_date', '<=', $request->end_date))
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $tenant = Tenant::find($tenantId);
        $stats = $this->inventoryService->getStockStats(
            $tenant,
            $request->start_date,
            $request->end_date
        );

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return view('inventory.index', compact('movements', 'stats', 'products'));
    }

    public function valuation(Request $request): View
    {
        $tenant = auth()->user()->tenant;
        $data = $this->inventoryService->getInventoryValuation($tenant);

        return view('inventory.valuation', $data);
    }

    public function productHistory(Request $request, Product $product): View
    {
        $movements = $this->inventoryService->getMovementsForProduct(
            $product,
            $request->start_date,
            $request->end_date
        );

        return view('inventory.product-history', compact('product', 'movements'));
    }

    public function storeEntry(StoreEntryRequest $request): RedirectResponse
    {
        $product = Product::findOrFail($request->product_id);

        try {
            $movement = $this->inventoryService->registerEntry(
                product: $product,
                quantity: (float) $request->quantity,
                unitCost: $request->unit_cost !== null ? (float) $request->unit_cost : null,
                user: $request->user(),
                reference: $request->reference,
                notes: $request->notes
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('inventory.index')
            ->with('success', "Entrada de {$movement->quantity} unidades registrada.");
    }

    public function storeExit(StoreExitRequest $request): RedirectResponse
    {
        $product = Product::findOrFail($request->product_id);

        try {
            $movement = $this->inventoryService->registerExit(
                $product,
                (float) $request->quantity,
                $request->user(),
                $request->reason,
                $request->notes
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('inventory.index')
            ->with('success', "Salida de {$movement->quantity} unidades registrada.");
    }

    public function storeAdjustment(StoreAdjustmentRequest $request): RedirectResponse
    {
        $product = Product::findOrFail($request->product_id);

        try {
            $movement = $this->inventoryService->registerAdjustment(
                $product,
                (float) $request->new_stock,
                $request->reason,
                $request->user()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('inventory.index')
            ->with('success', "Stock ajustado a {$movement->stock_after} unidades.");
    }
}