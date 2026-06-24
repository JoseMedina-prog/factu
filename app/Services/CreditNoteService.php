<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditNoteService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function create(Request $request): CreditNote
    {
        return DB::transaction(function () use ($request) {
            $creditNote = CreditNote::create([
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => auth()->id(),
                'client_id' => $request->client_id,
                'invoice_id' => $request->invoice_id,
                'issue_date' => $request->issue_date ?? now()->toDateString(),
                'due_date' => $request->due_date,
                'notes' => $request->notes,
                'status' => 'draft',
            ]);

            $creditNote->generateNumber();

            foreach ($request->items as $itemData) {
                $subtotal = $itemData['quantity'] * $itemData['unit_price'];
                CreditNoteItem::create([
                    'credit_note_id' => $creditNote->id,
                    'product_id' => $itemData['product_id'] ?? null,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'tax_rate' => $itemData['tax_rate'] ?? 0,
                    'subtotal' => $subtotal,
                ]);
            }

            $creditNote->calculateTotals();

            $this->inventoryService->incrementForCreditNote($creditNote->fresh(), $request->user());

            return $creditNote->load('items', 'client');
        });
    }

    public function update(CreditNote $creditNote, Request $request): CreditNote
    {
        return DB::transaction(function () use ($creditNote, $request) {
            $creditNote->update([
                'client_id' => $request->client_id,
                'invoice_id' => $request->invoice_id,
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'notes' => $request->notes,
            ]);

            if ($request->items) {
                $creditNote->items()->delete();
                foreach ($request->items as $itemData) {
                    $subtotal = $itemData['quantity'] * $itemData['unit_price'];
                    CreditNoteItem::create([
                        'credit_note_id' => $creditNote->id,
                        'product_id' => $itemData['product_id'] ?? null,
                        'description' => $itemData['description'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'tax_rate' => $itemData['tax_rate'] ?? 0,
                        'subtotal' => $subtotal,
                    ]);
                }
            }

            $creditNote->calculateTotals();

            return $creditNote->fresh()->load('items', 'client');
        });
    }

    public function delete(CreditNote $creditNote): bool
    {
        return DB::transaction(function () use ($creditNote) {
            $creditNote->items()->delete();
            return $creditNote->delete();
        });
    }

    public function approve(CreditNote $creditNote): CreditNote
    {
        $creditNote->update(['status' => 'approved']);
        return $creditNote->fresh();
    }

    public function cancel(CreditNote $creditNote): CreditNote
    {
        $creditNote->update(['status' => 'cancelled']);
        return $creditNote->fresh();
    }

    public function getStats(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $stats = CreditNote::where('tenant_id', $tenantId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(status = "draft") as draft,
                SUM(status = "approved") as approved,
                SUM(status = "cancelled") as cancelled,
                SUM(CASE WHEN status = "approved" THEN total ELSE 0 END) as total_amount
            ')
            ->first();

        return [
            'total' => $stats->total ?? 0,
            'draft' => $stats->draft ?? 0,
            'approved' => $stats->approved ?? 0,
            'cancelled' => $stats->cancelled ?? 0,
            'total_amount' => $stats->total_amount ?? 0,
        ];
    }
}
