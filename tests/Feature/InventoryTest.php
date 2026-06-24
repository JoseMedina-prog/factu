<?php

namespace Tests\Feature;

use App\Events\LowStockReached;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InventoryMovement;
use App\Models\NumberingRange;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Services\InventoryService;
use App\Services\NumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected InventoryService $service;
    protected NumberingService $numberingService;

    protected function setUp(): void
    {
        parent::setUp();
        app(\App\Services\PermissionService::class)->syncPermissions();

        $this->service = app(InventoryService::class);
        $this->numberingService = app(NumberingService::class);

        $this->tenant = Tenant::factory()->create();
        $this->admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);
        $this->admin->assignRole(\App\Services\PermissionService::ROLE_ADMIN);

        app()->instance('current_tenant_id', $this->tenant->id);
    }

    protected function createProduct(float $stock = 100, float $minStock = 10, float $cost = 50): Product
    {
        return Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'stock' => $stock,
            'min_stock' => $minStock,
            'cost' => $cost,
            'track_inventory' => true,
            'type' => Product::TYPE_PRODUCT,
        ]);
    }

    public function test_register_entry_increases_stock(): void
    {
        $product = $this->createProduct(50, 10);

        $movement = $this->service->registerEntry($product, 25, unitCost: 60, user: $this->admin, reference: 'PO-001');

        $this->assertSame(InventoryMovement::TYPE_ENTRY, $movement->type);
        $this->assertSame(25.0, (float) $movement->quantity);
        $this->assertSame(50.0, (float) $movement->stock_before);
        $this->assertSame(75.0, (float) $movement->stock_after);
        $this->assertSame('PO-001', $movement->reference);

        $product->refresh();
        $this->assertSame(75.0, (float) $product->stock);
        $this->assertSame(60.0, (float) $product->cost);
    }

    public function test_register_exit_decreases_stock(): void
    {
        $product = $this->createProduct(100, 10);

        $movement = $this->service->registerExit($product, 30, user: $this->admin, reason: 'Manual exit');

        $this->assertSame(InventoryMovement::TYPE_EXIT, $movement->type);
        $this->assertSame(30.0, (float) $movement->quantity);
        $this->assertSame(70.0, (float) $movement->stock_after);

        $product->refresh();
        $this->assertSame(70.0, (float) $product->stock);
    }

    public function test_register_adjustment_sets_absolute_stock(): void
    {
        $product = $this->createProduct(100, 10);

        $movement = $this->service->registerAdjustment($product, 45, reason: 'Conteo físico', user: $this->admin);

        $this->assertSame(InventoryMovement::TYPE_ADJUSTMENT, $movement->type);
        $this->assertSame(45.0, (float) $movement->stock_after);

        $product->refresh();
        $this->assertSame(45.0, (float) $product->stock);
    }

    public function test_exit_creates_negative_stock_allowed(): void
    {
        $product = $this->createProduct(5, 10);

        $movement = $this->service->registerExit($product, 10, user: $this->admin, reason: 'Sale');

        $product->refresh();
        $this->assertSame(-5.0, (float) $product->stock);
    }

    public function test_cannot_exit_untracked_product(): void
    {
        $product = Product::factory()->service()->create(['tenant_id' => $this->tenant->id]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->registerExit($product, 5, $this->admin, 'test');
    }

    public function test_zero_quantity_throws_exception(): void
    {
        $product = $this->createProduct(100, 10);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->registerEntry($product, 0);
    }

    public function test_invoice_creation_decrements_stock(): void
    {
        $product = $this->createProduct(50, 10);
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->numberingService->createDefaultRanges($this->tenant);

        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100,
            'tax_total' => 19,
            'total' => 119,
            'status' => 'draft',
        ]);
        $invoice->generateNumber();

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'description' => 'Test',
            'quantity' => 5,
            'unit_price' => 20,
            'subtotal' => 100,
            'tax' => 19,
        ]);

        $result = $this->service->decrementForInvoice($invoice, $this->admin);

        $this->assertCount(1, $result['movements']);

        $product->refresh();
        $this->assertSame(45.0, (float) $product->stock);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'invoice_id' => $invoice->id,
            'type' => 'exit',
            'quantity' => 5,
        ]);
    }

    public function test_credit_note_increments_stock(): void
    {
        $product = $this->createProduct(50, 10);
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->numberingService->createDefaultRanges($this->tenant);

        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100,
            'tax_total' => 19,
            'total' => 119,
            'status' => 'approved',
        ]);
        $invoice->generateNumber();

        $cn = new CreditNote([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'client_id' => $client->id,
            'invoice_id' => $invoice->id,
            'issue_date' => now()->toDateString(),
            'status' => 'approved',
        ]);
        $cn->generateNumber();

        CreditNoteItem::create([
            'credit_note_id' => $cn->id,
            'product_id' => $product->id,
            'description' => 'Test',
            'quantity' => 5,
            'unit_price' => 20,
            'tax_rate' => 19,
            'subtotal' => 100,
        ]);

        $result = $this->service->incrementForCreditNote($cn->fresh(), $this->admin);

        $product->refresh();
        $this->assertSame(55.0, (float) $product->stock);
        $this->assertCount(1, $result['movements']);
    }

    public function test_low_stock_event_fires_when_crossing_threshold(): void
    {
        Event::fake();
        $product = $this->createProduct(15, 10);

        $this->service->registerExit($product, 6, $this->admin, 'Sale');

        Event::assertDispatched(LowStockReached::class, function ($event) use ($product) {
            return $event->product->id === $product->id
                && abs($event->currentStock - 9.0) < 0.01
                && abs($event->minStock - 10.0) < 0.01;
        });
    }

    public function test_low_stock_event_does_not_repeat(): void
    {
        $product = $this->createProduct(8, 10);

        Event::fake([LowStockReached::class]);
        $this->service->registerExit($product, 1, $this->admin, 'Sale');

        Event::assertNotDispatched(LowStockReached::class);
    }

    public function test_low_stock_notification_sent_to_admins(): void
    {
        Notification::fake();
        $product = $this->createProduct(15, 10);

        $listener = new \App\Listeners\SendLowStockNotification();
        $listener->handle(new LowStockReached($this->tenant, $product, 9.0, 10.0));

        Notification::assertSentTo(
            $this->admin,
            LowStockNotification::class,
            function ($notification) use ($product) {
                return $notification->product->id === $product->id;
            }
        );
    }

    public function test_low_stock_listener_sends_notification_via_event(): void
    {
        Notification::fake();
        $product = $this->createProduct(15, 10);

        // Disparar el evento directamente y verificar que el listener registrado lo procesa
        event(new LowStockReached($this->tenant, $product, 9.0, 10.0));

        // En test sync queue, el listener ShouldQueue debería ejecutarse inmediatamente
        Notification::assertSentTo($this->admin, LowStockNotification::class);
    }

    public function test_inventory_valuation_calculates_correctly(): void
    {
        $this->createProduct(100, 10, 50);
        $this->createProduct(50, 10, 100);
        $this->createProduct(5, 10, 200);
        Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Agotado Test',
            'description' => 'Test',
            'price' => 100,
            'tax' => 19,
            'type' => Product::TYPE_PRODUCT,
            'is_active' => true,
            'stock' => 0,
            'min_stock' => 5,
            'cost' => 30,
            'track_inventory' => true,
            'unit_of_measure' => 'UND',
        ]);

        $valuation = $this->service->getInventoryValuation($this->tenant);

        $this->assertSame(2, $valuation['low_stock_count']);
        $this->assertSame(1, $valuation['out_of_stock_count']);
        $this->assertEquals(155.0, $valuation['total_units']);
        $this->assertEquals(11000.0, $valuation['total_value']);
    }

    public function test_service_product_with_track_inventory_false_is_ignored(): void
    {
        $serviceProduct = Product::factory()->service()->create([
            'tenant_id' => $this->tenant->id,
            'stock' => 0,
        ]);
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->numberingService->createDefaultRanges($this->tenant);

        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100,
            'tax_total' => 19,
            'total' => 119,
            'status' => 'draft',
        ]);
        $invoice->generateNumber();

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $serviceProduct->id,
            'description' => 'Service',
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
            'tax' => 19,
        ]);

        $result = $this->service->decrementForInvoice($invoice, $this->admin);

        $this->assertCount(0, $result['movements']);

        $serviceProduct->refresh();
        $this->assertSame(0.0, (float) $serviceProduct->stock);
    }

    public function test_movement_creates_inventory_movement_record(): void
    {
        $product = $this->createProduct(100, 10, 50);
        $movement = $this->service->registerEntry($product, 25, 55, $this->admin);

        $this->assertDatabaseHas('inventory_movements', [
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'user_id' => $this->admin->id,
            'type' => 'entry',
            'quantity' => 25,
            'stock_before' => 100,
            'stock_after' => 125,
            'unit_cost' => 55,
            'total_cost' => 25 * 55,
        ]);
    }

    public function test_movement_total_cost_calculation(): void
    {
        $product = $this->createProduct(100, 10, 50);
        $movement = $this->service->registerExit($product, 3, $this->admin, 'Sale');

        $this->assertSame(150.0, (float) $movement->total_cost);
    }

    public function test_product_scopes_low_stock(): void
    {
        $this->createProduct(50, 10);
        $this->createProduct(5, 10);
        $this->createProduct(0, 10);
        $this->createProduct(100, 0);

        $lowStock = Product::lowStock()->get();

        $this->assertCount(2, $lowStock);
    }

    public function test_get_movements_for_product(): void
    {
        $product = $this->createProduct(100, 10);
        $this->service->registerEntry($product, 50);
        $this->service->registerExit($product, 30);

        $movements = $this->service->getMovementsForProduct($product);

        $this->assertCount(2, $movements);
    }
}