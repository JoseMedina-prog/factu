<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_OVERPAID = 'overpaid';

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'created_by',
        'number',
        'supplier_invoice_number',
        'issue_date',
        'due_date',
        'received_date',
        'status',
        'payment_status',
        'subtotal',
        'tax_total',
        'retention_total',
        'total',
        'paid_amount',
        'balance',
        'currency',
        'attachment_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'received_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'retention_total' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function confirmedPayments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class)->where('status', PurchasePayment::STATUS_CONFIRMED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('payment_status', [self::PAYMENT_UNPAID, self::PAYMENT_PARTIAL])
            ->where('status', self::STATUS_RECEIVED);
    }

    public function scopeOverdue(Builder $query, ?string $asOf = null): Builder
    {
        $asOf = $asOf ? \Illuminate\Support\Carbon::parse($asOf) : now();
        return $this->scopePending($query)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $asOf->toDateString());
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->lt(now())
            && in_array($this->payment_status, [self::PAYMENT_UNPAID, self::PAYMENT_PARTIAL]);
    }

    public function daysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        return (int) $this->due_date->diffInDays(now());
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

    public function isFullyPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function hasOutstandingBalance(): bool
    {
        return in_array($this->payment_status, [self::PAYMENT_UNPAID, self::PAYMENT_PARTIAL, self::PAYMENT_OVERPAID]);
    }

    public function recalculatePaymentStatus(): void
    {
        $paidConfirmed = (float) $this->confirmedPayments()->sum('amount');
        $total = (float) $this->total;

        $this->paid_amount = round($paidConfirmed, 2);
        $this->balance = round($total - $paidConfirmed, 2);

        $this->payment_status = match (true) {
            $paidConfirmed <= 0 => self::PAYMENT_UNPAID,
            $paidConfirmed < $total => self::PAYMENT_PARTIAL,
            $paidConfirmed == $total => self::PAYMENT_PAID,
            default => self::PAYMENT_OVERPAID,
        };

        $this->save();
    }

    public function calculateTotals(): void
    {
        $this->loadMissing('items');
        $this->subtotal = $this->items->sum('subtotal');
        $this->tax_total = $this->items->sum(fn ($i) => $i->subtotal * $i->tax / 100);
        $this->retention_total = $this->items->sum(fn ($i) => ($i->subtotal + $i->subtotal * $i->tax / 100) * $i->retention / 100);
        $this->total = $this->subtotal + $this->tax_total - $this->retention_total;

        $this->paid_amount = $this->paid_amount ?? 0;
        $this->balance = $this->total - $this->paid_amount;

        $this->save();
    }
}