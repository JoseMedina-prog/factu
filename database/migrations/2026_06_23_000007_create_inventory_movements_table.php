<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('credit_note_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('type', ['entry', 'exit', 'adjustment', 'transfer', 'loss'])->index();
            $table->decimal('quantity', 14, 2);
            $table->decimal('stock_before', 14, 2)->default(0);
            $table->decimal('stock_after', 14, 2)->default(0);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);

            $table->string('reason', 255)->nullable();
            $table->string('reference', 100)->nullable();
            $table->date('movement_date');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'product_id', 'movement_date']);
            $table->index(['tenant_id', 'type', 'movement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};