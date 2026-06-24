<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('number', 50)->comment('Número de factura del proveedor');
            $table->string('supplier_invoice_number')->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('received_date')->nullable();

            $table->enum('status', ['draft', 'received', 'cancelled'])->default('received');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'overpaid'])->default('unpaid');

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('retention_total', 14, 2)->default(0)->comment('Retenciones en la fuente/IVA');
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);

            $table->string('currency', 3)->default('COP');
            $table->string('attachment_path')->nullable()->comment('PDF/XML de la factura del proveedor');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'payment_status']);
            $table->index(['tenant_id', 'supplier_id']);
            $table->index('due_date');
            $table->unique(['tenant_id', 'supplier_id', 'number'], 'unique_supplier_invoice_per_tenant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};