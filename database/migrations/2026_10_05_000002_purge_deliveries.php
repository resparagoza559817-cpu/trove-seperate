<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // The FK drop below is MySQL-specific; skipped on SQLite (used by the test suite).
        if (DB::getDriverName() === 'mysql' && Schema::hasTable('inventory_logs') && Schema::hasColumn('inventory_logs', 'delivery_id')) {
            // Drop foreign key using exact constraint name first
            DB::statement('ALTER TABLE `inventory_logs` DROP FOREIGN KEY `inventory_logs_ibfk_2`;');

            Schema::table('inventory_logs', function (Blueprint $table) {$table->dropColumn('delivery_id');
            });
        }

        // Drop legacy delivery tables (child first, then parent)
        Schema::dropIfExists('delivery_items');
        Schema::dropIfExists('deliveries');
    }

    public function down(): void {
        // Legacy tables are intentionally not recreated.
    }
};