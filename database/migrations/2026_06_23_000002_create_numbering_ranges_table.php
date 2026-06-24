<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbering_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('document_type', ['invoice', 'credit_note'])->index();
            $table->string('prefix', 10);
            $table->unsignedBigInteger('from_number');
            $table->unsignedBigInteger('to_number');
            $table->unsignedBigInteger('current_number')->default(0);
            $table->string('resolution_number', 100)->nullable();
            $table->date('resolution_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('technical_key', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'document_type', 'is_active']);
            $table->index(['tenant_id', 'document_type', 'expiration_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_ranges');
    }
};