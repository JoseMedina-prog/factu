<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    public const DOC_TYPE_NIT = 'NIT';
    public const DOC_TYPE_CC = 'CC';
    public const DOC_TYPE_CE = 'CE';

    protected $fillable = [
        'tenant_id',
        'name',
        'document',
        'document_type',
        'contact_name',
        'email',
        'phone',
        'address',
        'city',
        'bank_name',
        'bank_account',
        'bank_account_type',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function outstandingBalance(): float
    {
        return (float) $this->purchaseInvoices()
            ->whereIn('payment_status', ['unpaid', 'partial', 'overpaid'])
            ->sum('balance');
    }

    public function documentDisplay(): string
    {
        if (!$this->document) {
            return '—';
        }
        return $this->document_type . ' ' . $this->document;
    }
}