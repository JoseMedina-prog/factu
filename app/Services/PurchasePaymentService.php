<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchasePaymentService
{
    public function register(Request $request, PurchaseInvoice $invoice, User $user, bool $autoConfirm = true): PurchasePayment
    {
        return DB::transaction(function () use ($request, $invoice, $user, $autoConfirm) {
            $data = $request->validate($this->rules($invoice));

            if ((float) $data['amount'] <= 0) {
                throw new \InvalidArgumentException('El monto del pago debe ser mayor a cero.');
            }

            $remaining = (float) $invoice->total - (float) $invoice->paid_amount;

            if ((float) $data['amount'] > $remaining + 0.01) {
                throw new \InvalidArgumentException(
                    "El pago (\${$data['amount']}) supera el saldo pendiente de la factura (\${$remaining})."
                );
            }

            $payment = PurchasePayment::create([
                'tenant_id' => $invoice->tenant_id,
                'supplier_id' => $invoice->supplier_id,
                'purchase_invoice_id' => $invoice->id,
                'created_by' => $user->id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'status' => $autoConfirm ? PurchasePayment::STATUS_CONFIRMED : PurchasePayment::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
                'confirmed_at' => $autoConfirm ? now() : null,
                'confirmed_by' => $autoConfirm ? $user->id : null,
            ]);

            if ($autoConfirm) {
                $invoice->recalculatePaymentStatus();
            }

            return $payment->load(['supplier', 'purchaseInvoice', 'creator', 'confirmer']);
        });
    }

    public function registerAdvance(Request $request, User $user): PurchasePayment
    {
        $data = $request->validate([
            'supplier_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('suppliers', 'id')->where('tenant_id', $user->tenant_id),
            ],
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'nullable|date',
            'method' => 'required|string|in:' . implode(',', array_keys(PurchasePayment::METHODS)),
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($data, $user) {
            return PurchasePayment::create([
                'tenant_id' => $user->tenant_id,
                'supplier_id' => $data['supplier_id'],
                'purchase_invoice_id' => null,
                'created_by' => $user->id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'status' => PurchasePayment::STATUS_CONFIRMED,
                'notes' => $data['notes'] ?? null,
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ]);
        });
    }

    public function confirm(PurchasePayment $payment, User $user): PurchasePayment
    {
        if ($payment->status !== PurchasePayment::STATUS_PENDING) {
            throw new \InvalidArgumentException('Solo se pueden confirmar pagos en estado pendiente.');
        }

        return DB::transaction(function () use ($payment, $user) {
            $payment->update([
                'status' => PurchasePayment::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ]);

            if ($payment->purchaseInvoice) {
                $payment->purchaseInvoice->recalculatePaymentStatus();
            }

            return $payment->fresh();
        });
    }

    public function cancel(PurchasePayment $payment, User $user, ?string $reason = null): PurchasePayment
    {
        if ($payment->status === PurchasePayment::STATUS_CANCELLED) {
            throw new \InvalidArgumentException('El pago ya está cancelado.');
        }

        return DB::transaction(function () use ($payment, $user, $reason) {
            $payment->update([
                'status' => PurchasePayment::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'notes' => $payment->notes . ($reason ? "\n[Cancelado: {$reason}]" : "\n[Cancelado por {$user->name}]"),
            ]);

            if ($payment->purchaseInvoice) {
                $payment->purchaseInvoice->recalculatePaymentStatus();
            }

            return $payment->fresh();
        });
    }

    public function delete(PurchasePayment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $invoice = $payment->purchaseInvoice;
            $result = $payment->delete();

            if ($invoice) {
                $invoice->recalculatePaymentStatus();
            }

            return $result;
        });
    }

    public function getStats(int $tenantId, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth()->toDateString();
        $endDate = $endDate ?? now()->endOfMonth()->toDateString();

        $query = PurchasePayment::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', PurchasePayment::STATUS_CONFIRMED);

        $totalPaid = (float) (clone $query)->sum('amount');
        $count = (clone $query)->count();

        $byMethod = (clone $query)
            ->selectRaw('method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('method')
            ->get();

        return [
            'total_paid' => $totalPaid,
            'count' => $count,
            'by_method' => $byMethod,
            'period' => ['start' => $startDate, 'end' => $endDate],
        ];
    }

    protected function rules(PurchaseInvoice $invoice): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'nullable|date|before_or_equal:today',
            'method' => 'required|string|in:' . implode(',', array_keys(PurchasePayment::METHODS)),
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}