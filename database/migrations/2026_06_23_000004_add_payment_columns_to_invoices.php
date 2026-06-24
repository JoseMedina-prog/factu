<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('paid_amount', 14, 2)->default(0)->after('total');
            $table->decimal('balance', 14, 2)->default(0)->after('paid_amount');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'overpaid'])
                ->default('unpaid')
                ->after('balance');

            $table->index(['tenant_id', 'payment_status']);
            $table->index(['tenant_id', 'due_date', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'payment_status']);
            $table->dropIndex(['tenant_id', 'due_date', 'payment_status']);
            $table->dropColumn(['paid_amount', 'balance', 'payment_status']);
        });
    }
};