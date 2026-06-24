<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function register(Request $request, Invoice $invoice, User $user, bool $autoConfirm = true): Payment
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

            $payment = Payment::create([
                'tenant_id' => $invoice->tenant_id,
                'client_id' => $invoice->client_id,
                'invoice_id' => $invoice->id,
                'created_by' => $user->id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'status' => $autoConfirm ? Payment::STATUS_CONFIRMED : Payment::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
                'confirmed_at' => $autoConfirm ? now() : null,
                'confirmed_by' => $autoConfirm ? $user->id : null,
            ]);

            if ($autoConfirm) {
                $invoice->recalculatePaymentStatus();
                event(new \App\Events\PaymentRegistered($payment, $invoice->fresh()));
            } else {
                event(new \App\Events\PaymentRegistered($payment, $invoice));
            }

            return $payment->load(['client', 'invoice', 'creator', 'confirmer']);
        });
    }

    public function registerAdvance(Request $request, User $user): Payment
    {
        $data = $request->validate([
            'client_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('clients', 'id')->where('tenant_id', $user->tenant_id),
            ],
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'nullable|date',
            'method' => 'required|string|in:' . implode(',', array_keys(Payment::METHODS)),
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($data, $user) {
            return Payment::create([
                'tenant_id' => $user->tenant_id,
                'client_id' => $data['client_id'],
                'invoice_id' => null,
                'created_by' => $user->id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'status' => Payment::STATUS_CONFIRMED,
                'notes' => $data['notes'] ?? null,
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ]);
        });
    }

    public function confirm(Payment $payment, User $user): Payment
    {
        if (!$payment->isPending()) {
            throw new \InvalidArgumentException('Solo se pueden confirmar pagos en estado pendiente.');
        }

        return DB::transaction(function () use ($payment, $user) {
            $payment->update([
                'status' => Payment::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ]);

            if ($payment->invoice) {
                $payment->invoice->recalculatePaymentStatus();
            }

            event(new \App\Events\PaymentRegistered($payment->fresh(), $payment->invoice?->fresh()));

            return $payment->fresh();
        });
    }

    public function cancel(Payment $payment, User $user, ?string $reason = null): Payment
    {
        if ($payment->isCancelled()) {
            throw new \InvalidArgumentException('El pago ya está cancelado.');
        }

        return DB::transaction(function () use ($payment, $user, $reason) {
            $payment->update([
                'status' => Payment::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'notes' => $payment->notes . ($reason ? "\n[Cancelado: {$reason}]" : "\n[Cancelado por {$user->name}]"),
            ]);

            if ($payment->invoice) {
                $payment->invoice->recalculatePaymentStatus();
            }

            return $payment->fresh();
        });
    }

    public function delete(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;
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

        $query = Payment::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', Payment::STATUS_CONFIRMED);

        $totalCollected = (clone $query)->sum('amount');
        $count = (clone $query)->count();

        $byMethod = (clone $query)
            ->selectRaw('method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('method')
            ->get();

        return [
            'total_collected' => (float) $totalCollected,
            'count' => $count,
            'by_method' => $byMethod,
            'period' => ['start' => $startDate, 'end' => $endDate],
        ];
    }

    public function getAccountsReceivable(int $tenantId, ?string $asOf = null): array
    {
        $asOf = $asOf ? \Carbon\Carbon::parse($asOf) : now();

        $invoices = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('payment_status', ['unpaid', 'partial', 'overpaid'])
            ->whereIn('status', ['approved', 'sent'])
            ->whereNotNull('due_date')
            ->with('client:id,name,document,email,phone')
            ->orderBy('due_date')
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

        foreach ($invoices as $invoice) {
            $isOverdue = $invoice->due_date->lt($asOf);
            $key = $isOverdue ? $invoice->agingBucket() : 'current';
            if (!isset($grouped[$key])) {
                $grouped[$key] = collect();
            }
            $grouped[$key]->push($invoice);
            $totals[$key] += (float) $invoice->balance;
            $totals['total'] += (float) $invoice->balance;
        }

        return [
            'groups' => $grouped,
            'totals' => $totals,
            'as_of' => $asOf->toDateString(),
            'invoice_count' => $invoices->count(),
        ];
    }

    protected function rules(Invoice $invoice): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'nullable|date|before_or_equal:today',
            'method' => 'required|string|in:' . implode(',', array_keys(Payment::METHODS)),
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}