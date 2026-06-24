<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryMovement extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_ENTRY = 'entry';
    public const TYPE_EXIT = 'exit';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_LOSS = 'loss';

    public const TYPES = [
        self::TYPE_ENTRY => 'Entrada',
        self::TYPE_EXIT => 'Salida',
        self::TYPE_ADJUSTMENT => 'Ajuste',
        self::TYPE_TRANSFER => 'Transferencia',
        self::TYPE_LOSS => 'Pérdida/Merma',
    ];

    public const SIGN = [
        self::TYPE_ENTRY => 1,
        self::TYPE_EXIT => -1,
        self::TYPE_ADJUSTMENT => 0,
        self::TYPE_TRANSFER => -1,
        self::TYPE_LOSS => -1,
    ];

    protected $fillable = [
        'tenant_id',
        'product_id',
        'user_id',
        'invoice_id',
        'credit_note_id',
        'purchase_invoice_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'unit_cost',
        'total_cost',
        'reason',
        'reference',
        'movement_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'stock_before' => 'decimal:2',
            'stock_after' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'movement_date' => 'date',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function scopeEntries(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ENTRY);
    }

    public function scopeExits(Builder $query): Builder
    {
        return $query->whereIn('type', [self::TYPE_EXIT, self::TYPE_LOSS]);
    }

    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeBetweenDates(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('movement_date', [$start, $end]);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getSignedQuantityAttribute(): float
    {
        if ($this->type === self::TYPE_ADJUSTMENT) {
            return (float) $this->quantity;
        }
        return (float) $this->quantity * (self::SIGN[$this->type] ?? 0);
    }
}