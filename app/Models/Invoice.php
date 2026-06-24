<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'number',
        'reference_code',
        'issue_date',
        'due_date',
        'status',
        'status_factus',
        'is_validated',
        'subtotal',
        'tax_total',
        'total',
        'paid_amount',
        'balance',
        'payment_status',
        'notes',
        'external_id',
        'cufe',
        'qr_link',
        'factus_response',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'is_validated' => 'boolean',
            'factus_response' => 'array',
            'validated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function integrationLogs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function confirmedPayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('status', Payment::STATUS_CONFIRMED);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('is_validated', true);
    }

    public function scopePendingValidation(Builder $query): Builder
    {
        return $query->where('status', 'sent')
            ->where('is_validated', false);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'unpaid');
    }

    public function scopePartial(Builder $query): Builder
    {
        return $query->where('payment_status', 'partial');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeOverdue(Builder $query, ?string $asOf = null): Builder
    {
        $asOf = $asOf ? \Illuminate\Support\Carbon::parse($asOf) : now();
        return $query->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', $asOf->toDateString());
    }

    public function isFullyPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === 'partial';
    }

    public function hasOutstandingBalance(): bool
    {
        return in_array($this->payment_status, ['unpaid', 'partial', 'overpaid']);
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->lt(now())
            && in_array($this->payment_status, ['unpaid', 'partial']);
    }

    public function daysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        return (int) $this->due_date->diffInDays(now());
    }

    public function recalculatePaymentStatus(): void
    {
        $paidConfirmed = (float) $this->confirmedPayments()->sum('amount');
        $total = (float) $this->total;

        $this->paid_amount = round($paidConfirmed, 2);
        $this->balance = round($total - $paidConfirmed, 2);

        $this->payment_status = match (true) {
            $paidConfirmed <= 0 => 'unpaid',
            $paidConfirmed < $total => 'partial',
            $paidConfirmed == $total => 'paid',
            default => 'overpaid',
        };

        $this->save();
    }

    public function agingBucket(): string
    {
        if (!$this->isOverdue()) {
            return 'current';
        }

        $days = $this->daysOverdue();
        return match (true) {
            $days <= 30 => '0-30',
            $days <= 60 => '31-60',
            $days <= 90 => '61-90',
            default => '90+',
        };
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('subtotal');
        $this->tax_total = $this->items->sum(fn($item) => $item->subtotal * $item->tax / 100);
        $this->total = $this->subtotal + $this->tax_total;

        if ($this->paid_amount === null) {
            $this->paid_amount = 0;
        }
        $this->balance = $this->total - $this->paid_amount;

        if (!in_array($this->payment_status, ['paid', 'partial', 'overpaid', 'unpaid'])) {
            $this->payment_status = $this->paid_amount > 0 ? 'partial' : 'unpaid';
        }

        $this->save();
    }

    public function generateNumber(): string
    {
        $tenant = Tenant::lockForUpdate()->findOrFail($this->tenant_id);

        $numberingService = app(\App\Services\NumberingService::class);

        $this->number = $numberingService->assignNextNumber(
            $tenant,
            \App\Models\NumberingRange::TYPE_INVOICE,
            $this->issue_date
        );
        $this->save();

        return $this->number;
    }
}
