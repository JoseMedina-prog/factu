<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    public function create(array $data): Supplier
    {
        $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id;
        if (!$tenantId) {
            throw new \InvalidArgumentException('No se pudo determinar el tenant para crear el proveedor.');
        }
        $data['tenant_id'] = $tenantId;
        $data['is_active'] = $data['is_active'] ?? true;

        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);
        return $supplier->fresh();
    }

    public function toggleActive(Supplier $supplier): Supplier
    {
        $supplier->update(['is_active' => !$supplier->is_active]);
        return $supplier->fresh();
    }

    public function delete(Supplier $supplier): bool
    {
        return $supplier->delete();
    }

    public function recordPurchase(Supplier $supplier, array $data, $user): PurchaseInvoice
    {
        return DB::transaction(function () use ($supplier, $data, $user) {
            $invoice = PurchaseInvoice::create([
                'tenant_id' => $supplier->tenant_id,
                'supplier_id' => $supplier->id,
                'created_by' => $user->id,
                'number' => $data['number'],
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? $data['number'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'received_date' => $data['received_date'] ?? now()->toDateString(),
                'status' => PurchaseInvoice::STATUS_RECEIVED,
                'payment_status' => PurchaseInvoice::PAYMENT_UNPAID,
                'currency' => $data['currency'] ?? 'COP',
                'notes' => $data['notes'] ?? null,
                'attachment_path' => $data['attachment_path'] ?? null,
                'paid_amount' => 0,
            ]);

            foreach ($data['items'] as $itemData) {
                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => $itemData['product_id'] ?? null,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'tax' => $itemData['tax'] ?? 0,
                    'retention' => $itemData['retention'] ?? 0,
                    'subtotal' => $itemData['quantity'] * $itemData['unit_price'],
                    'total' => 0,
                ]);
            }

            $invoice->calculateTotals();

            if (!empty($data['auto_register_stock'])) {
                $this->registerStockForInvoice($invoice, $user);
            }

            return $invoice->fresh(['items', 'supplier']);
        });
    }

    protected function registerStockForInvoice(PurchaseInvoice $invoice, $user): void
    {
        $inventoryService = app(InventoryService::class);

        foreach ($invoice->items as $item) {
            if (!$item->product_id) {
                continue;
            }
            $product = \App\Models\Product::find($item->product_id);
            if (!$product || !$product->track_inventory) {
                continue;
            }

            $inventoryService->registerEntry(
                $product,
                (float) $item->quantity,
                (float) $item->unit_price * (1 + (float) $item->tax / 100),
                $user,
                $invoice->number,
                null,
                $invoice,
            );
        }
    }

    public function getAccountsPayable(int $tenantId, ?string $asOf = null): array
    {
        $asOf = $asOf ? \Carbon\Carbon::parse($asOf) : now();

        $invoices = PurchaseInvoice::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('payment_status', ['unpaid', 'partial', 'overpaid'])
            ->where('status', PurchaseInvoice::STATUS_RECEIVED)
            ->with('supplier:id,name,document,email,phone')
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->get();

        $grouped = [
            'current' => collect(),
            '0-30' => collect(),
            '31-60' => collect(),
            '61-90' => collect(),
            '90+' => collect(),
        ];

        $totals = [
            'current' => 0.0,
            '0-30' => 0.0,
            '31-60' => 0.0,
            '61-90' => 0.0,
            '90+' => 0.0,
            'total' => 0.0,
        ];

        $bySupplier = [];

        foreach ($invoices as $invoice) {
            $hasDueDate = $invoice->due_date !== null;
            $isOverdue = $hasDueDate && $invoice->due_date->lt($asOf);
            $key = $isOverdue ? $invoice->agingBucket() : 'current';
            $grouped[$key] = $grouped[$key] ?? collect();
            $grouped[$key]->push($invoice);
            $totals[$key] = ($totals[$key] ?? 0) + (float) $invoice->balance;
            $totals['total'] += (float) $invoice->balance;

            $supKey = $invoice->supplier_id;
            if (!isset($bySupplier[$supKey])) {
                $bySupplier[$supKey] = [
                    'supplier' => $invoice->supplier,
                    'total' => 0.0,
                    'count' => 0,
                ];
            }
            $bySupplier[$supKey]['total'] += (float) $invoice->balance;
            $bySupplier[$supKey]['count']++;
        }

        $bySupplier = array_values($bySupplier);
        usort($bySupplier, fn ($a, $b) => $b['total'] <=> $a['total']);

        return [
            'groups' => $grouped,
            'totals' => $totals,
            'by_supplier' => array_values($bySupplier),
            'as_of' => $asOf->toDateString(),
            'invoice_count' => $invoices->count(),
            'supplier_count' => count($bySupplier),
        ];
    }
}