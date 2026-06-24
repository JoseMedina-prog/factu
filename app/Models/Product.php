<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_PRODUCT = 'product';
    public const TYPE_SERVICE = 'service';

    public const TYPES = [
        self::TYPE_PRODUCT => 'Producto',
        self::TYPE_SERVICE => 'Servicio',
    ];

    public const UNITS = [
        'UND' => 'Unidad',
        'KG' => 'Kilogramo',
        'GR' => 'Gramo',
        'LT' => 'Litro',
        'ML' => 'Mililitro',
        'MT' => 'Metro',
        'CM' => 'Centímetro',
        'M2' => 'Metro cuadrado',
        'M3' => 'Metro cúbico',
        'CJ' => 'Caja',
        'PQ' => 'Paquete',
        'DOC' => 'Docena',
        'HR' => 'Hora',
        'SRV' => 'Servicio',
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'price',
        'tax',
        'type',
        'is_active',
        'stock',
        'min_stock',
        'cost',
        'track_inventory',
        'unit_of_measure',
        'sku',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'tax' => 'decimal:2',
            'is_active' => 'boolean',
            'stock' => 'decimal:2',
            'min_stock' => 'decimal:2',
            'cost' => 'decimal:2',
            'track_inventory' => 'boolean',
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

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeProducts(Builder $query): Builder
    {
        return $query->where('type', 'product');
    }

    public function scopeServices(Builder $query): Builder
    {
        return $query->where('type', 'service');
    }

    public function scopeTracked(Builder $query): Builder
    {
        return $query->where('track_inventory', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('track_inventory', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->where('min_stock', '>', 0);
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('track_inventory', true)
            ->where('stock', '<=', 0);
    }

    public function getPriceWithTaxAttribute(): float
    {
        return (float) $this->price * (1 + $this->tax / 100);
    }

    public function getStockValueAttribute(): float
    {
        return (float) $this->stock * (float) $this->cost;
    }

    public function getUnitOfMeasureLabelAttribute(): string
    {
        return self::UNITS[$this->unit_of_measure] ?? $this->unit_of_measure;
    }

    public function isLowStock(): bool
    {
        if (!$this->track_inventory) {
            return false;
        }
        return (float) $this->min_stock > 0
            && (float) $this->stock <= (float) $this->min_stock;
    }

    public function isOutOfStock(): bool
    {
        if (!$this->track_inventory) {
            return false;
        }
        return (float) $this->stock <= 0;
    }

    public function hasStock(float $quantity): bool
    {
        if (!$this->track_inventory) {
            return true;
        }
        return (float) $this->stock >= $quantity;
    }
}