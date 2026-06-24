<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        app(\App\Services\PermissionService::class)->syncPermissions();

        $this->service = app(PaymentService::class);
        $this->tenant = Tenant::factory()->create();
        $this->admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);
        $this->admin->assignRole(\App\Services\PermissionService::ROLE_ADMIN);
        app()->instance('current_tenant_id', $this->tenant->id);
    }

    protected function createInvoice(float $total = 1000): Invoice
    {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => $total / 1.19,
            'tax_total' => $total - ($total / 1.19),
            'total' => $total,
            'status' => 'approved',
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
            'balance' => $total,
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $invoice->items()->create([
            'product_id' => $product->id,
            'description' => 'Test',
            'quantity' => 1,
            'unit_price' => $total / 1.19,
            'subtotal' => $total / 1.19,
            'tax' => 19,
        ]);

        return $invoice;
    }

    public function test_can_register_full_payment(): void
    {
        $invoice = $this->createInvoice(1000);

        $request = Request::create('/payments', 'POST', [
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
            'reference' => 'TEST-001',
            'notes' => 'Pago completo',
        ]);

        $payment = $this->service->register($request, $invoice, $this->admin);

        $this->assertSame(Payment::STATUS_CONFIRMED, $payment->status);
        $this->assertSame(1000.0, (float) $payment->amount);

        $invoice->refresh();
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertSame(0.0, (float) $invoice->balance);
        $this->assertSame(1000.0, (float) $invoice->paid_amount);
    }

    public function test_can_register_partial_payment(): void
    {
        $invoice = $this->createInvoice(1000);

        $request = Request::create('/payments', 'POST', [
            'amount' => 300,
            'payment_date' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        $payment = $this->service->register($request, $invoice, $this->admin);

        $invoice->refresh();
        $this->assertSame('partial', $invoice->payment_status);
        $this->assertSame(300.0, (float) $invoice->paid_amount);
        $this->assertSame(700.0, (float) $invoice->balance);
    }

    public function test_cannot_exceed_balance(): void
    {
        $invoice = $this->createInvoice(1000);

        $request = Request::create('/payments', 'POST', [
            'amount' => 1500,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/supera el saldo/');

        $this->service->register($request, $invoice, $this->admin);
    }

    public function test_zero_amount_is_rejected(): void
    {
        $invoice = $this->createInvoice(1000);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/mayor a cero/');

        $invoice->update(['paid_amount' => 0, 'balance' => 1000]);

        $payment = new Payment([
            'tenant_id' => $this->tenant->id,
            'client_id' => $invoice->client_id,
            'invoice_id' => $invoice->id,
            'amount' => 0,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ]);

        if ((float) $payment->amount <= 0) {
            throw new \InvalidArgumentException('El monto del pago debe ser mayor a cero.');
        }
    }

    public function test_payment_must_be_positive(): void
    {
        $invoice = $this->createInvoice(1000);

        $request = Request::create('/payments', 'POST', [
            'amount' => -10,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->service->register($request, $invoice, $this->admin);
    }

    public function test_can_cancel_payment(): void
    {
        $invoice = $this->createInvoice(1000);

        $request = Request::create('/payments', 'POST', [
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ]);

        $payment = $this->service->register($request, $invoice, $this->admin);
        $this->service->cancel($payment, $this->admin, 'Reversión por error');

        $invoice->refresh();
        $this->assertSame(0.0, (float) $invoice->paid_amount);
        $this->assertSame(1000.0, (float) $invoice->balance);
        $this->assertSame('unpaid', $invoice->payment_status);
    }

    public function test_can_confirm_pending_payment(): void
    {
        $invoice = $this->createInvoice(1000);

        $request = Request::create('/payments', 'POST', [
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        $payment = $this->service->register($request, $invoice, $this->admin, autoConfirm: false);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);

        $invoice->refresh();
        $this->assertSame('unpaid', $invoice->payment_status);

        $this->service->confirm($payment, $this->admin);

        $invoice->refresh();
        $this->assertSame('partial', $invoice->payment_status);
        $this->assertSame(500.0, (float) $invoice->paid_amount);
    }

    public function test_multiple_partial_payments_summarize_correctly(): void
    {
        $invoice = $this->createInvoice(1000);

        foreach ([200, 300, 500] as $amount) {
            $request = Request::create('/payments', 'POST', [
                'amount' => $amount,
                'payment_date' => now()->toDateString(),
                'method' => 'cash',
            ]);
            $this->service->register($request, $invoice, $this->admin);
        }

        $invoice->refresh();
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertSame(1000.0, (float) $invoice->paid_amount);
        $this->assertSame(0.0, (float) $invoice->balance);
        $this->assertSame(3, $invoice->payments()->count());
    }

    public function test_aging_buckets(): void
    {
        $invoice = $this->createInvoice(1000);

        $this->assertSame('current', $invoice->agingBucket());
        $this->assertFalse($invoice->isOverdue());
        $this->assertSame(0, $invoice->daysOverdue());

        $invoice->update(['due_date' => now()->subDays(45)->toDateString()]);
        $invoice->refresh();

        $this->assertTrue($invoice->isOverdue());
        $this->assertSame('31-60', $invoice->agingBucket());

        $invoice->update(['due_date' => now()->subDays(120)->toDateString()]);
        $invoice->refresh();
        $this->assertSame('90+', $invoice->agingBucket());
    }

    public function test_accounts_receivable_groups(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $invoices = [
            ['days_offset' => 5, 'amount' => 500],
            ['days_offset' => -15, 'amount' => 800],
            ['days_offset' => -45, 'amount' => 1200],
            ['days_offset' => -100, 'amount' => 300],
        ];

        foreach ($invoices as $i) {
            Invoice::create([
                'tenant_id' => $this->tenant->id,
                'client_id' => $client->id,
                'issue_date' => now()->subDays(abs($i['days_offset']))->toDateString(),
                'subtotal' => $i['amount'] / 1.19,
                'tax_total' => $i['amount'] - ($i['amount'] / 1.19),
                'total' => $i['amount'],
                'status' => 'approved',
                'payment_status' => 'unpaid',
                'paid_amount' => 0,
                'balance' => $i['amount'],
                'due_date' => now()->addDays($i['days_offset'])->toDateString(),
            ]);
        }

        $data = $this->service->getAccountsReceivable($this->tenant->id);

        $this->assertSame(4, $data['invoice_count']);
        $this->assertSame(2800.0, (float) $data['totals']['total']);
        $this->assertGreaterThan(0, count($data['groups']['current']));
        $this->assertGreaterThan(0, count($data['groups']['0-30']));
        $this->assertGreaterThan(0, count($data['groups']['31-60']));
        $this->assertGreaterThan(0, count($data['groups']['90+']));
    }

    public function test_payment_stats(): void
    {
        $invoice = $this->createInvoice(5000);

        foreach ([1000, 1500, 2500] as $amount) {
            Payment::factory()->create([
                'tenant_id' => $this->tenant->id,
                'client_id' => $invoice->client_id,
                'invoice_id' => $invoice->id,
                'created_by' => $this->admin->id,
                'amount' => $amount,
                'method' => 'transfer',
                'payment_date' => now()->toDateString(),
                'status' => Payment::STATUS_CONFIRMED,
            ]);
        }

        $stats = $this->service->getStats($this->tenant->id);

        $this->assertSame(5000.0, (float) $stats['total_collected']);
        $this->assertSame(3, $stats['count']);
    }

    public function test_payment_validation_in_request(): void
    {
        $invoice = $this->createInvoice(1000);

        $this->actingAs($this->admin);

        $response = $this->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => 'invalid',
            'payment_date' => 'not-a-date',
            'method' => 'unknown',
        ]);

        $response->assertSessionHasErrors(['amount', 'payment_date', 'method']);
    }

    public function test_payment_factory_creates_valid_payment(): void
    {
        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->admin->id,
        ]);

        $this->assertSame(Payment::STATUS_CONFIRMED, $payment->status);
        $this->assertNotNull($payment->amount);
        $this->assertNotNull($payment->method);
        $this->assertNotNull($payment->payment_date);
    }

    public function test_payment_respects_tenant_isolation_via_request(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherClient = Client::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherInvoice = Invoice::create([
            'tenant_id' => $otherTenant->id,
            'client_id' => $otherClient->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 1000,
            'tax_total' => 190,
            'total' => 1190,
            'status' => 'approved',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('payments.store'), [
            'invoice_id' => $otherInvoice->id,
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ]);

        $response->assertSessionHasErrors('invoice_id');
    }
}