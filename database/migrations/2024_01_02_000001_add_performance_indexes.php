<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexName = $this->getIndexName('credit_notes', 'number');

        if ($indexName) {
            if (DB::connection()->getDriverName() === 'sqlite') {
                DB::statement("DROP INDEX `{$indexName}`");
            } else {
                DB::statement("DROP INDEX `{$indexName}` ON credit_notes");
            }
        }

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->unique(['tenant_id', 'number'], 'credit_notes_tenant_number_unique');
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->index(['credit_note_id', 'product_id'], 'credit_note_items_credit_product_index');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['tenant_id', 'due_date'], 'invoices_tenant_due_date_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'users_role_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['tenant_id', 'name'], 'products_tenant_name_index');
        });
    }

    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropUnique('credit_notes_tenant_number_unique');
            $table->string('number')->unique()->change();
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->dropIndex('credit_note_items_credit_product_index');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_tenant_due_date_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_tenant_name_index');
        });
    }

    private function getIndexName(string $table, string $column): ?string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $result = DB::select("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$table}'
                AND COLUMN_NAME = '{$column}'
                AND NON_UNIQUE = 0
                LIMIT 1
            ");

            return $result[0]->INDEX_NAME ?? null;
        }

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if ((int) $index->unique !== 1) {
                    continue;
                }

                $info = DB::select("PRAGMA index_info('{$index->name}')");
                foreach ($info as $col) {
                    if ($col->name === $column) {
                        return $index->name;
                    }
                }
            }

            return null;
        }

        return null;
    }
};