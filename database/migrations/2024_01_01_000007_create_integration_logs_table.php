<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('provider');
            $table->string('action');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->enum('status', ['pending', 'success', 'error'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at')->useCurrent();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'provider']);
            $table->index(['tenant_id', 'invoice_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
