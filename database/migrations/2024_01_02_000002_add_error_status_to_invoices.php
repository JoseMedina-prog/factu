<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft', 'pending', 'sent', 'approved', 'rejected', 'cancelled', 'error') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("UPDATE invoices SET status = 'rejected' WHERE status = 'error'");
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft', 'pending', 'sent', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'draft'");
        }
    }
};
