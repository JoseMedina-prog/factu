<?php

namespace App\Services;

use App\Events\LowStockReached;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryService
{
    public function decrementForInvoice(Invoice $invoice, ?User $user = null): array
    {
        $results = ['movements' => [], 'warnings' => []];

        foreach ($invoice->items as $item) {
            if (!$item->product_id) {
                continue;
            }

            $product = Product::find($item->product_id);
            if (!$product || !$product->track_inventory) {
                continue;
            }

            $quantity = (float) $item->quantity;
            $currentStock = (float) $product->stock;

            if ($quantity <= 0) {
                continue;
            }

            $stockBefore = $currentStock;
            $stockAfter = $currentStock - $quantity;

            $movement = $this->createMovement(
                product: $product,
                type: InventoryMovement::TYPE_EXIT,
                quantity: $quantity,
                stockBefore: $stockBefore,
                stockAfter: $stockAfter,
                unitCost: (float) $product->cost,
                reason: "Venta - Factura {$invoice->number}",
                reference: $invoice->number,
                user: $user,
                invoice: $invoice
            );

            $results['movements'][] = $movement;

            if ($stockAfter < 0) {
                $results['warnings'][] = "Producto {$product->name}: stock quedó en {$stockAfter} (negativo).";
            }

            $this->checkLowStock($product, $stockBefore, $stockAfter);
        }

        return $results;
    }

    public function incrementForCreditNote(\App\Models\CreditNote $creditNote, ?User $user = null): array
    {
        $results = ['movements' => []];

        foreach ($creditNote->items as $item) {
            if (!$item->product_id) {
                continue;
            }

            $product = Product::find($item->product_id);
            if (!$product || !$product->track_inventory) {
                continue;
            }

            $quantity = (float) $item->quantity;
            if ($quantity <= 0) {
                continue;
            }

            $stockBefore = (float) $product->stock;
            $stockAfter = $stockBefore + $quantity;

            $movement = $this->createMovement(
                product: $product,
                type: InventoryMovement::TYPE_ENTRY,
                quantity: $quantity,
                stockBefore: $stockBefore,
                stockAfter: $stockAfter,
                unitCost: (float) $product->cost,
                reason: "Devolución - Nota Crédito {$creditNote->number}",
                reference: $creditNote->number,
                user: $user,
                creditNote: $creditNote
            );

            $results['movements'][] = $movement;
        }

        return $results;
    }

    public function registerEntry(Product $product, float $quantity, float $unitCost = null, ?User $user = null, ?string $reference = null, ?string $notes = null): InventoryMovement
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        return DB::transaction(function () use ($product, $quantity, $unitCost, $user, $reference, $notes) {
            $locked = Product::lockForUpdate()->findOrFail($product->id);

            $stockBefore = (float) $locked->stock;
            $stockAfter = $stockBefore + $quantity;
            $effectiveCost = $unitCost ?? (float) $locked->cost;

            $movement = $this->createMovement(
                product: $locked,
                type: InventoryMovement::TYPE_ENTRY,
                quantity: $quantity,
                stockBefore: $stockBefore,
                stockAfter: $stockAfter,
                unitCost: $effectiveCost,
                reason: 'Entrada manual',
                reference: $reference,
                notes: $notes,
                user: $user
            );

            if ($unitCost !== null) {
                $locked->cost = $unitCost;
            }

            $locked->save();

            return $movement;
        });
    }

    public function registerExit(Product $product, float $quantity, ?User $user = null, ?string $reason = null, ?string $notes = null): InventoryMovement
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        return DB::transaction(function () use ($product, $quantity, $user, $reason, $notes) {
            $locked = Product::lockForUpdate()->findOrFail($product->id);

            if (!$locked->track_inventory) {
                throw new \InvalidArgumentException("El producto {$locked->name} no rastrea inventario.");
            }

            $stockBefore = (float) $locked->stock;
            $stockAfter = $stockBefore - $quantity;

            $movement = $this->createMovement(
                product: $locked,
                type: InventoryMovement::TYPE_EXIT,
                quantity: $quantity,
                stockBefore: $stockBefore,
                stockAfter: $stockAfter,
                unitCost: (float) $locked->cost,
                reason: $reason ?? 'Salida manual',
                reference: null,
                notes: $notes,
                user: $user
            );

            $this->checkLowStock($locked, $stockBefore, $stockAfter);

            return $movement;
        });
    }

    public function registerAdjustment(Product $product, float $newStock, ?string $reason = null, ?User $user = null): InventoryMovement
    {
        return DB::transaction(function () use ($product, $newStock, $reason, $user) {
            $locked = Product::lockForUpdate()->findOrFail($product->id);

            if (!$locked->track_inventory) {
                throw new \InvalidArgumentException("El producto {$locked->name} no rastrea inventario.");
            }

            $stockBefore = (float) $locked->stock;
            $stockAfter = (float) $newStock;
            $difference = $stockAfter - $stockBefore;

            return $this->createMovement(
                product: $locked,
                type: InventoryMovement::TYPE_ADJUSTMENT,
                quantity: $difference,
                stockBefore: $stockBefore,
                stockAfter: $stockAfter,
                unitCost: (float) $locked->cost,
                reason: $reason ?? 'Ajuste de inventario',
                reference: null,
                notes: null,
                user: $user
            );
        });
    }

    public function registerLoss(Product $product, float $quantity, string $reason, ?User $user = null): InventoryMovement
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        return DB::transaction(function () use ($product, $quantity, $reason, $user) {
            $locked = Product::lockForUpdate()->findOrFail($product->id);

            if (!$locked->track_inventory) {
                throw new \InvalidArgumentException("El producto {$locked->name} no rastrea inventario.");
            }

            $stockBefore = (float) $locked->stock;
            $stockAfter = $stockBefore - $quantity;

            return $this->createMovement(
                product: $locked,
                type: InventoryMovement::TYPE_LOSS,
                quantity: $quantity,
                stockBefore: $stockBefore,
                stockAfter: $stockAfter,
                unitCost: (float) $locked->cost,
                reason: $reason,
                reference: null,
                notes: null,
                user: $user
            );
        });
    }

    public function getMovementsForProduct(Product $product, ?string $startDate = null, ?string $endDate = null)
    {
        $query = InventoryMovement::query()
            ->where('product_id', $product->id)
            ->with('user:id,name')
            ->orderByDesc('movement_date')
            ->orderByDesc('id');

        if ($startDate && $endDate) {
            $query->whereBetween('movement_date', [$startDate, $endDate]);
        }

        return $query->paginate(20);
    }

    public function getInventoryValuation(Tenant $tenant): array
    {
        $products = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('track_inventory', true)
            ->get();

        $totalUnits = $products->sum(fn ($p) => (float) $p->stock);
        $totalValue = $products->sum(fn ($p) => (float) $p->stock * (float) $p->cost);

        $lowStock = $products->filter(fn ($p) => $p->isLowStock())->values();
        $outOfStock = $products->filter(fn ($p) => $p->isOutOfStock())->values();

        return [
            'product_count' => $products->count(),
            'total_units' => $totalUnits,
            'total_value' => $totalValue,
            'low_stock_count' => $lowStock->count(),
            'out_of_stock_count' => $outOfStock->count(),
            'low_stock_products' => $lowStock,
            'out_of_stock_products' => $outOfStock,
            'all_products' => $products,
        ];
    }

    public function getStockStats(Tenant $tenant, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth()->toDateString();
        $endDate = $endDate ?? now()->endOfMonth()->toDateString();

        $movements = InventoryMovement::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->get();

        $entries = $movements->where('type', InventoryMovement::TYPE_ENTRY);
        $exits = $movements->whereIn('type', [InventoryMovement::TYPE_EXIT, InventoryMovement::TYPE_LOSS]);

        return [
            'entries_count' => $entries->count(),
            'exits_count' => $exits->count(),
            'entries_value' => $entries->sum('total_cost'),
            'exits_value' => $exits->sum('total_cost'),
            'period' => ['start' => $startDate, 'end' => $endDate],
        ];
    }

    protected function createMovement(
        Product $product,
        string $type,
        float $quantity,
        float $stockBefore,
        float $stockAfter,
        float $unitCost,
        ?string $reason = null,
        ?string $reference = null,
        ?string $notes = null,
        ?User $user = null,
        ?Invoice $invoice = null,
        ?\App\Models\CreditNote $creditNote = null
    ): InventoryMovement {
        $totalCost = abs($quantity) * $unitCost;

        $movement = InventoryMovement::create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'invoice_id' => $invoice?->id,
            'credit_note_id' => $creditNote?->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'reason' => $reason,
            'reference' => $reference,
            'movement_date' => now()->toDateString(),
            'notes' => $notes,
        ]);

        $product->stock = $stockAfter;
        $product->save();

        return $movement;
    }

    protected function checkLowStock(Product $product, float $stockBefore, float $stockAfter): void
    {
        if (!$product->track_inventory) {
            return;
        }

        $minStock = (float) $product->min_stock;
        if ($minStock <= 0) {
            return;
        }

        $wasLowBefore = $stockBefore <= $minStock;
        $isLowNow = $stockAfter <= $minStock;

        if (!$wasLowBefore && $isLowNow && $product->tenant) {
            event(new LowStockReached(
                $product->tenant,
                $product,
                $stockAfter,
                $minStock
            ));
        }
    }
}