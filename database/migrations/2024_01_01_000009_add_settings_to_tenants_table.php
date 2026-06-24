<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('email');
            $table->decimal('default_tax_rate', 5, 2)->default(19.00)->after('logo_path');
            $table->string('invoice_prefix', 10)->default('INV')->after('default_tax_rate');
            $table->string('credit_note_prefix', 10)->default('NC')->after('invoice_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'default_tax_rate', 'invoice_prefix', 'credit_note_prefix']);
        });
    }
};
