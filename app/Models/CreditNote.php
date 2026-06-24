<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Scopes\TenantScope;

class CreditNote extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $fillable = [
        'tenant_id',
        'user_id',
        'client_id',
        'invoice_id',
        'number',
        'issue_date',
        'due_date',
        'notes',
        'subtotal',
        'tax',
        'total',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function generateNumber(): void
    {
        $tenant = \App\Models\Tenant::lockForUpdate()->findOrFail($this->tenant_id);

        $numberingService = app(\App\Services\NumberingService::class);

        $this->number = $numberingService->assignNextNumber(
            $tenant,
            \App\Models\NumberingRange::TYPE_CREDIT_NOTE,
            $this->issue_date
        );
        $this->save();
    }

    public function calculateTotals(): void
    {
        $this->loadMissing('items');

        $subtotal = 0;
        $tax = 0;

        foreach ($this->items as $item) {
            $itemSubtotal = (float) $item->subtotal;
            $itemTax = $itemSubtotal * ((float) $item->tax_rate / 100);
            $subtotal += $itemSubtotal;
            $tax += $itemTax;
        }

        $this->subtotal = $subtotal;
        $this->tax = $tax;
        $this->total = $subtotal + $tax;
    }
}
