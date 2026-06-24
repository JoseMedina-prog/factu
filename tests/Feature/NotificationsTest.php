<?php

namespace Tests\Feature;

use App\Events\InvoiceOverdue;
use App\Events\InvoiceRejected;
use App\Events\InvoiceValidated;
use App\Events\NumberingRangeAlert;
use App\Events\PaymentRegistered;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\NumberingRange;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InvoiceOverdueNotification;
use App\Notifications\InvoiceRejectedNotification;
use App\Notifications\InvoiceValidatedNotification;
use App\Notifications\NumberingRangeAlertNotification;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $staff;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        app(\App\Services\PermissionService::class)->syncPermissions();

        $this->tenant = Tenant::factory()->create();
        $this->admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);
        $this->admin->assignRole(\App\Services\PermissionService::ROLE_ADMIN);

        $this->staff = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'staff',
        ]);
        $this->staff->assignRole(\App\Services\PermissionService::ROLE_STAFF);

        $this->client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'cliente@example.com',
        ]);

        app()->instance('current_tenant_id', $this->tenant->id);
    }

    protected function createInvoice(array $attrs = []): Invoice
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $invoice = Invoice::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 1000,
            'tax_total' => 190,
            'total' => 1190,
            'status' => 'sent',
            'is_validated' => true,
            'cufe' => 'test-cufe-123',
            'due_date' => now()->addDays(30)->toDateString(),
        ], $attrs));

        $invoice->items()->create([
            'product_id' => $product->id,
            'description' => 'Test',
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
            'tax' => 19,
        ]);

        return $invoice;
    }

    public function test_invoice_validated_event_dispatches_notification(): void
    {
        Notification::fake();
        $invoice = $this->createInvoice();

        event(new InvoiceValidated($invoice, ['cufe' => 'test-cufe-123']));

        Notification::assertSentOnDemand(InvoiceValidatedNotification::class);

        Notification::assertSentTo(
            $this->admin,
            InvoiceValidatedNotification::class
        );
    }

    public function test_invoice_rejected_event_dispatches_notification(): void
    {
        Notification::fake();
        $invoice = $this->createInvoice();

        event(new InvoiceRejected($invoice, ['error' => 'invalid_xml'], 'DIAN rejected'));

        Notification::assertSentTo(
            $this->admin,
            InvoiceRejectedNotification::class
        );
    }

    public function test_payment_registered_event_dispatches_notification(): void
    {
        Notification::fake();
        $invoice = $this->createInvoice();

        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'invoice_id' => $invoice->id,
            'created_by' => $this->admin->id,
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'method' => 'transfer',
            'status' => Payment::STATUS_CONFIRMED,
        ]);

        event(new PaymentRegistered($payment, $invoice));

        Notification::assertSentOnDemand(PaymentReceivedNotification::class);
    }

    public function test_invoice_overdue_event_dispatches_notification(): void
    {
        Notification::fake();
        $invoice = $this->createInvoice(['due_date' => now()->subDays(15)->toDateString()]);

        event(new InvoiceOverdue($invoice, 15));

        Notification::assertSentOnDemand(InvoiceOverdueNotification::class);
    }

    public function test_numbering_range_alert_event_dispatches_notification(): void
    {
        Notification::fake();

        $range = NumberingRange::create([
            'tenant_id' => $this->tenant->id,
            'document_type' => NumberingRange::TYPE_INVOICE,
            'prefix' => 'TEST',
            'from_number' => 1,
            'to_number' => 5,
            'current_number' => 5,
            'is_active' => true,
        ]);

        event(new NumberingRangeAlert(
            $this->tenant,
            $range,
            NumberingRangeAlert::LEVEL_CRITICAL,
            'Rango TEST agotado'
        ));

        Notification::assertSentTo(
            $this->admin,
            NumberingRangeAlertNotification::class
        );
    }

    public function test_notification_skips_client_without_email(): void
    {
        Notification::fake();
        $clientSinEmail = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => null,
        ]);
        $invoice = $this->createInvoice(['client_id' => $clientSinEmail->id]);

        event(new InvoiceValidated($invoice));

        Notification::assertSentTo($this->admin, InvoiceValidatedNotification::class);
        Notification::assertSentOnDemandTimes(InvoiceValidatedNotification::class, 0);
    }

    public function test_payment_notification_not_sent_for_pending_payments(): void
    {
        Notification::fake();
        $invoice = $this->createInvoice();

        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'invoice_id' => $invoice->id,
            'created_by' => $this->admin->id,
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'method' => 'transfer',
            'status' => Payment::STATUS_PENDING,
        ]);

        event(new PaymentRegistered($payment, $invoice));

        Notification::assertNothingSent();
    }

    public function test_notification_database_record(): void
    {
        $invoice = $this->createInvoice();

        event(new InvoiceValidated($invoice));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->admin->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_notification_includes_invoice_data(): void
    {
        Notification::fake();
        $invoice = $this->createInvoice(['number' => 'INV-000999']);

        event(new InvoiceValidated($invoice));

        Notification::assertSentTo(
            $this->admin,
            InvoiceValidatedNotification::class,
            function (InvoiceValidatedNotification $notification) use ($invoice) {
                $data = $notification->toArray($this->admin);
                return $data['invoice_id'] === $invoice->id
                    && $data['invoice_number'] === 'INV-000999'
                    && $data['type'] === 'invoice_validated';
            }
        );
    }

    public function test_send_overdue_command_dispatches_events(): void
    {
        Event::fake([InvoiceOverdue::class]);

        $this->createInvoice(['due_date' => now()->subDays(5)->toDateString()]);
        $this->createInvoice(['due_date' => now()->subDays(15)->toDateString()]);
        $this->createInvoice(['due_date' => now()->addDays(5)->toDateString()]);

        $this->artisan('notifications:send-overdue-invoices --days=1')
            ->assertExitCode(0);

        Event::assertDispatchedTimes(InvoiceOverdue::class, 2);
    }

    public function test_check_numbering_command(): void
    {
        $this->artisan('notifications:check-numbering-ranges --dry-run')
            ->assertExitCode(0);
    }

    public function test_multiple_admins_all_receive_notification(): void
    {
        Notification::fake();

        $secondAdmin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);
        $secondAdmin->assignRole(\App\Services\PermissionService::ROLE_ADMIN);

        $invoice = $this->createInvoice();

        event(new InvoiceValidated($invoice));

        Notification::assertSentTo($this->admin, InvoiceValidatedNotification::class);
        Notification::assertSentTo($secondAdmin, InvoiceValidatedNotification::class);
    }
}