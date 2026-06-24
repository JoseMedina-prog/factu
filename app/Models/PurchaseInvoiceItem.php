<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{

    protected $fillable = [
        'purchase_invoice_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'tax',
        'retention',
        'subtotal',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax' => 'decimal:2',
            'retention' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getLineTotalAttribute(): float
    {
        $sub = (float) $this->subtotal;
        $tax = $sub * (float) $this->tax / 100;
        $ret = ($sub + $tax) * (float) $this->retention / 100;
        return round($sub + $tax - $ret, 2);
    }
}