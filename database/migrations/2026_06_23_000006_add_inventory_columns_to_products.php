<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('stock', 14, 2)->default(0)->after('tax');
            $table->decimal('min_stock', 14, 2)->default(0)->after('stock');
            $table->decimal('cost', 14, 2)->default(0)->after('min_stock');
            $table->boolean('track_inventory')->default(true)->after('cost');
            $table->string('unit_of_measure', 20)->default('UND')->after('track_inventory');
            $table->string('sku', 50)->nullable()->after('unit_of_measure');

            $table->index(['tenant_id', 'track_inventory']);
            $table->index(['tenant_id', 'stock', 'min_stock']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'track_inventory']);
            $table->dropIndex(['tenant_id', 'stock', 'min_stock']);
            $table->dropColumn(['stock', 'min_stock', 'cost', 'track_inventory', 'unit_of_measure', 'sku']);
        });
    }
};