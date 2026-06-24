<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    public const PLAN_FREE = 'free';
    public const PLAN_BASIC = 'basic';
    public const PLAN_PRO = 'pro';

    public const PLANS = [
        self::PLAN_FREE => [
            'label' => 'Gratis',
            'max_users' => 1,
            'max_invoices_per_month' => 20,
            'max_clients' => 50,
        ],
        self::PLAN_BASIC => [
            'label' => 'Básico',
            'max_users' => 3,
            'max_invoices_per_month' => 200,
            'max_clients' => 500,
        ],
        self::PLAN_PRO => [
            'label' => 'Pro',
            'max_users' => 999,
            'max_invoices_per_month' => 9999,
            'max_clients' => 9999,
        ],
    ];

    protected $fillable = [
        'name',
        'nit',
        'address',
        'phone',
        'email',
        'is_active',
        'logo_path',
        'default_tax_rate',
        'invoice_prefix',
        'credit_note_prefix',
        'plan',
        'plan_expires_at',
        'max_users',
        'max_invoices_per_month',
        'max_clients',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'default_tax_rate' => 'decimal:2',
            'plan_expires_at' => 'datetime',
            'max_users' => 'integer',
            'max_invoices_per_month' => 'integer',
            'max_clients' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function integrationLogs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class);
    }

    public function numberingRanges(): HasMany
    {
        return $this->hasMany(NumberingRange::class);
    }

    public function getDefaultTaxRateAttribute($value): float
    {
        if ($value === null || $value === '') {
            return 19.00;
        }
        return (float) $value;
    }

    public function getPlanLabelAttribute(): string
    {
        return self::PLANS[$this->plan]['label'] ?? $this->plan;
    }

    public function isSubscriptionActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->plan === self::PLAN_PRO) {
            return true;
        }

        if ($this->plan_expires_at === null) {
            return true;
        }

        return $this->plan_expires_at->isFuture();
    }

    public function activeNumberingRanges(string $documentType)
    {
        return $this->numberingRanges()
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->orderBy('from_number');
    }

    public function applyPlanDefaults(): void
    {
        $defaults = self::PLANS[$this->plan] ?? self::PLANS[self::PLAN_FREE];
        $this->max_users = $defaults['max_users'];
        $this->max_invoices_per_month = $defaults['max_invoices_per_month'];
        $this->max_clients = $defaults['max_clients'];
    }
}