<?php

namespace App\Services;

use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function create(StoreInvoiceRequest $request): Invoice
    {
        $inventoryWarnings = [];

        $invoice = DB::transaction(function () use ($request, &$inventoryWarnings) {
            $data = $request->validated();
            $data['tenant_id'] = auth()->user()->tenant_id;
            $data['issue_date'] = $data['issue_date'] ?? now()->toDateString();

            $invoice = Invoice::create($data);
            $invoice->generateNumber();

            foreach ($data['items'] as $itemData) {
                $itemData['invoice_id'] = $invoice->id;
                $itemData['subtotal'] = $itemData['quantity'] * $itemData['unit_price'];
                $itemData['tax'] = $itemData['tax'] ?? 0;
                InvoiceItem::create($itemData);
            }

            $invoice->calculateTotals();

            $inventoryResult = $this->inventoryService->decrementForInvoice($invoice, $request->user());
            $inventoryWarnings = $inventoryResult['warnings'] ?? [];

            return $invoice->load('items', 'client');
        });

        if (!empty($inventoryWarnings)) {
            session()->flash('inventory_warnings', $inventoryWarnings);
        }

        return $invoice;
    }

    public function update(Invoice $invoice, UpdateInvoiceRequest $request): Invoice
    {
        return DB::transaction(function () use ($invoice, $request) {
            $data = $request->validated();

            if (isset($data['items'])) {
                $oldItems = $invoice->items()->get();

                $oldItemsByProduct = $oldItems->groupBy('product_id')->map(fn ($g) => $g->sum('quantity'));
                $newItemsByProduct = collect($data['items'])->groupBy('product_id')->map(fn ($g) => collect($g)->sum('quantity'));

                $invoice->items()->delete();
                foreach ($data['items'] as $itemData) {
                    $itemData['invoice_id'] = $invoice->id;
                    $itemData['subtotal'] = $itemData['quantity'] * $itemData['unit_price'];
                    $itemData['tax'] = $itemData['tax'] ?? 0;
                    InvoiceItem::create($itemData);
                }

                foreach ($oldItemsByProduct as $productId => $oldQty) {
                    $newQty = $newItemsByProduct[$productId] ?? 0;
                    $difference = $newQty - $oldQty;
                    if ($difference > 0 && $productId) {
                        $product = \App\Models\Product::find($productId);
                        if ($product && $product->track_inventory) {
                            $this->inventoryService->registerExit(
                                $product,
                                $difference,
                                $request->user(),
                                "Diferencia por edición de factura {$invoice->number}"
                            );
                        }
                    }
                }
                unset($data['items']);
            }

            $invoice->update($data);
            $invoice->calculateTotals();

            return $invoice->fresh()->load('items', 'client');
        });
    }

    public function delete(Invoice $invoice): bool
    {
        return DB::transaction(function () use ($invoice) {
            foreach ($invoice->items as $item) {
                if (!$item->product_id) {
                    continue;
                }
                $product = \App\Models\Product::find($item->product_id);
                if ($product && $product->track_inventory) {
                    $this->inventoryService->registerEntry(
                        $product,
                        (float) $item->quantity,
                        null,
                        auth()->user(),
                        "Reversión por eliminación de factura {$invoice->number}",
                        'Eliminación local de factura'
                    );
                }
            }

            $invoice->items()->delete();
            return $invoice->delete();
        });
    }

    public function changeStatus(Invoice $invoice, string $status): Invoice
    {
        $invoice->update(['status' => $status]);
        return $invoice->fresh();
    }

    public function getInvoicesByStatus(string $status)
    {
        return Invoice::$status()->with('client')->orderByDesc('created_at')->get();
    }

    public function getInvoiceStats(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $stats = Invoice::where('tenant_id', $tenantId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(status = "draft") as draft,
                SUM(status = "pending") as pending,
                SUM(status = "sent") as sent,
                SUM(CASE WHEN status IN ("pending", "sent", "approved") THEN total ELSE 0 END) as total_amount
            ')
            ->first();

        return [
            'total' => $stats->total ?? 0,
            'draft' => $stats->draft ?? 0,
            'pending' => $stats->pending ?? 0,
            'sent' => $stats->sent ?? 0,
            'total_amount' => $stats->total_amount ?? 0,
        ];
    }
}
