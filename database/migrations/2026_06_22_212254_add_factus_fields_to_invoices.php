<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('reference_code', 100)->nullable()->unique()->after('external_id');
            $table->string('cufe', 200)->nullable()->after('reference_code');
            $table->string('qr_link', 500)->nullable()->after('cufe');
            $table->string('status_factus', 50)->nullable()->after('qr_link');
            $table->boolean('is_validated')->default(false)->after('status_factus');
            $table->json('factus_response')->nullable()->after('is_validated');
            $table->timestamp('validated_at')->nullable()->after('factus_response');
            $table->index(['tenant_id', 'status_factus']);
            $table->index('reference_code');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status_factus']);
            $table->dropIndex(['reference_code']);
            $table->dropColumn([
                'reference_code',
                'cufe',
                'qr_link',
                'status_factus',
                'is_validated',
                'factus_response',
                'validated_at',
            ]);
        });
    }
};
