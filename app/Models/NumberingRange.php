<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NumberingRange extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_INVOICE = 'invoice';
    public const TYPE_CREDIT_NOTE = 'credit_note';

    public const TYPES = [
        self::TYPE_INVOICE => 'Factura',
        self::TYPE_CREDIT_NOTE => 'Nota Crédito',
    ];

    protected $fillable = [
        'tenant_id',
        'document_type',
        'prefix',
        'from_number',
        'to_number',
        'current_number',
        'resolution_number',
        'resolution_date',
        'expiration_date',
        'technical_key',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'from_number' => 'integer',
            'to_number' => 'integer',
            'current_number' => 'integer',
            'resolution_date' => 'date',
            'expiration_date' => 'date',
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

    public function scopeForDocumentType(Builder $query, string $type): Builder
    {
        return $query->where('document_type', $type);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailableOn(Builder $query, $date): Builder
    {
        $dateString = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;
        $nowString = now()->toDateString();

        return $query->where(function (Builder $q) use ($dateString) {
            $q->whereNull('expiration_date')
              ->orWhereDate('expiration_date', '>=', $dateString);
        })
        ->where(function (Builder $q) use ($nowString) {
            $q->whereNull('resolution_date')
              ->orWhereDate('resolution_date', '<=', $nowString);
        });
    }

    public function isExpired($date = null): bool
    {
        $date = $date ?? now();
        return $this->expiration_date !== null
            && $this->expiration_date->lt(\Carbon\Carbon::parse($date));
    }

    public function isExhausted(): bool
    {
        return $this->current_number >= $this->to_number;
    }

    public function availableCount(): int
    {
        return max(0, $this->to_number - $this->current_number);
    }

    public function usagePercentage(): float
    {
        if ($this->to_number <= 0) {
            return 0.0;
        }
        $used = $this->current_number - $this->from_number + 1;
        $total = $this->to_number - $this->from_number + 1;
        if ($total <= 0) {
            return 100.0;
        }
        return round(($used / $total) * 100, 2);
    }

    public function nextNumber(): int
    {
        return $this->current_number + 1;
    }

    public function formatNumber(int $number): string
    {
        return sprintf('%s-%s', $this->prefix, str_pad((string) $number, 6, '0', STR_PAD_LEFT));
    }

    public function overlapsWith(NumberingRange $other): bool
    {
        if ($this->document_type !== $other->document_type || $this->id === $other->id) {
            return false;
        }
        return $this->from_number <= $other->to_number && $other->from_number <= $this->to_number;
    }
}