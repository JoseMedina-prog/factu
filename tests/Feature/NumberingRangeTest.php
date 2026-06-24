<?php

namespace Tests\Feature;

use App\Exceptions\NumberingRangeException;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\NumberingRange;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberingRangeTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected NumberingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NumberingService::class);
        $this->tenant = Tenant::factory()->create();
        $this->service->createDefaultRanges($this->tenant);
        $this->admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);
        app()->instance('current_tenant_id', $this->tenant->id);
    }

    public function test_assigns_next_sequential_number_within_range(): void
    {
        $first = $this->service->assignNextNumber($this->tenant, NumberingRange::TYPE_INVOICE);
        $second = $this->service->assignNextNumber($this->tenant, NumberingRange::TYPE_INVOICE);
        $third = $this->service->assignNextNumber($this->tenant, NumberingRange::TYPE_INVOICE);

        $this->assertSame('INV-000001', $first);
        $this->assertSame('INV-000002', $second);
        $this->assertSame('INV-000003', $third);
    }

    public function test_throws_when_no_range_available(): void
    {
        $tenantSinRango = Tenant::factory()->create();

        $this->expectException(NumberingRangeException::class);
        $this->service->assignNextNumber($tenantSinRango, NumberingRange::TYPE_INVOICE);
    }

    public function test_throws_when_range_exhausted(): void
    {
        $rango = NumberingRange::where('tenant_id', $this->tenant->id)
            ->where('document_type', NumberingRange::TYPE_CREDIT_NOTE)
            ->first();
        $rango->update(['from_number' => 1, 'to_number' => 3, 'current_number' => 3]);

        $this->expectException(NumberingRangeException::class);
        $this->expectExceptionMessageMatches('/agotado/');
        $this->service->assignNextNumber($this->tenant, NumberingRange::TYPE_CREDIT_NOTE);
    }

    public function test_skips_inactive_ranges(): void
    {
        NumberingRange::where('tenant_id', $this->tenant->id)
            ->where('document_type', NumberingRange::TYPE_INVOICE)
            ->update(['is_active' => false]);

        $this->expectException(NumberingRangeException::class);
        $this->service->assignNextNumber($this->tenant, NumberingRange::TYPE_INVOICE);
    }

    public function test_skips_expired_ranges(): void
    {
        NumberingRange::where('tenant_id', $this->tenant->id)
            ->where('document_type', NumberingRange::TYPE_INVOICE)
            ->update(['expiration_date' => now()->subDays(1)->toDateString()]);

        $this->expectException(NumberingRangeException::class);
        $this->service->assignNextNumber($this->tenant, NumberingRange::TYPE_INVOICE);
    }

    public function test_uses_next_range_when_first_exhausted(): void
    {
        $existente = NumberingRange::where('tenant_id', $this->tenant->id)
            ->where('document_type', NumberingRange::TYPE_INVOICE)
            ->where('prefix', 'INV')
            ->first();
        $existente->update(['from_number' => 1, 'to_number' => 5, 'current_number' => 5]);

        NumberingRange::create([
            'tenant_id' => $this->tenant->id,
            'document_type' => NumberingRange::TYPE_INVOICE,
            'prefix' => 'INV2',
            'from_number' => 1,
            'to_number' => 5,
            'current_number' => 0,
            'is_active' => true,
        ]);

        $next = $this->service->assignNextNumber($this->tenant, NumberingRange::TYPE_INVOICE);

        $this->assertSame('INV2-000001', $next);
    }

    public function test_overlap_detection(): void
    {
        $existing = NumberingRange::create([
            'tenant_id' => $this->tenant->id,
            'document_type' => NumberingRange::TYPE_INVOICE,
            'prefix' => 'OVR',
            'from_number' => 100,
            'to_number' => 200,
            'current_number' => 0,
            'is_active' => true,
        ]);

        $new = NumberingRange::make([
            'tenant_id' => $this->tenant->id,
            'document_type' => NumberingRange::TYPE_INVOICE,
            'prefix' => 'OVR2',
            'from_number' => 150,
            'to_number' => 250,
        ]);

        $this->expectException(NumberingRangeException::class);
        $this->expectExceptionMessageMatches('/superpone/');
        $this->service->validateNoOverlap($new);
    }

    public function test_alerts_when_usage_above_threshold(): void
    {
        $rango = NumberingRange::where('tenant_id', $this->tenant->id)
            ->where('document_type', NumberingRange::TYPE_INVOICE)
            ->first();
        $rango->update(['from_number' => 1, 'to_number' => 10, 'current_number' => 9]);

        $alerts = $this->service->getExhaustionAlerts($this->tenant);

        $this->assertCount(1, $alerts);
        $this->assertSame('warning', $alerts[0]['level']);
        $this->assertStringContainsString('90', $alerts[0]['message']);
    }

    public function test_alerts_critical_when_exhausted(): void
    {
        $rango = NumberingRange::where('tenant_id', $this->tenant->id)
            ->where('document_type', NumberingRange::TYPE_INVOICE)
            ->first();
        $rango->update(['from_number' => 1, 'to_number' => 5, 'current_number' => 5]);

        $alerts = $this->service->getExhaustionAlerts($this->tenant);

        $this->assertCount(1, $alerts);
        $this->assertSame('critical', $alerts[0]['level']);
    }

    public function test_alerts_when_expiration_within_30_days(): void
    {
        $rango = NumberingRange::where('tenant_id', $this->tenant->id)
            ->where('document_type', NumberingRange::TYPE_INVOICE)
            ->first();
        $rango->update(['expiration_date' => now()->addDays(15)->toDateString()]);

        $alerts = $this->service->getExhaustionAlerts($this->tenant);

        $this->assertNotEmpty($alerts);
        $this->assertSame('warning', $alerts[0]['level']);
        $this->assertStringContainsString('vence', $alerts[0]['message']);
    }

    public function test_invoice_generates_number_using_numbering_service(): void
    {
        $client = \App\Models\Client::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = \App\Models\Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100,
            'tax_total' => 19,
            'total' => 119,
            'status' => 'draft',
        ]);
        $invoice->items()->create([
            'product_id' => $product->id,
            'description' => 'Test',
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
            'tax' => 19,
        ]);

        $invoice->generateNumber();

        $this->assertSame('INV-000001', $invoice->number);
    }

    public function test_credit_note_generates_number_using_numbering_service(): void
    {
        $client = \App\Models\Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $cn = new CreditNote([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100,
            'tax' => 19,
            'total' => 119,
            'status' => 'draft',
        ]);

        $cn->generateNumber();

        $this->assertSame('NC-000001', $cn->number);
        $this->assertNotNull($cn->id);
    }
}