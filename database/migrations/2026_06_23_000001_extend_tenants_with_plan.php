<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('plan', 20)->default('free')->after('is_active');
            $table->timestamp('plan_expires_at')->nullable()->after('plan');
            $table->unsignedInteger('max_users')->default(2)->after('plan_expires_at');
            $table->unsignedInteger('max_invoices_per_month')->default(50)->after('max_users');
            $table->unsignedInteger('max_clients')->default(100)->after('max_invoices_per_month');

            $table->index(['plan', 'plan_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['plan', 'plan_expires_at']);
            $table->dropColumn([
                'plan',
                'plan_expires_at',
                'max_users',
                'max_invoices_per_month',
                'max_clients',
            ]);
        });
    }
};