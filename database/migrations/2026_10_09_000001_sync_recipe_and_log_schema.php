<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brings fresh installs in line with the live Trove database, which was
 * adjusted by hand:  recipe table = product_raw_materials (quantity_needed),
 * inventory_logs note column = ref_note.  Every step is guarded, so on a
 * database that already matches (the live one) this migration changes nothing.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('product_materials') && ! Schema::hasTable('product_raw_materials')) {
            Schema::rename('product_materials', 'product_raw_materials');
        }
        if (Schema::hasTable('product_raw_materials')
            && Schema::hasColumn('product_raw_materials', 'quantity_used')
            && ! Schema::hasColumn('product_raw_materials', 'quantity_needed')) {
            Schema::table('product_raw_materials', function (Blueprint $t) {
                $t->renameColumn('quantity_used', 'quantity_needed');
            });
        }

        if (Schema::hasTable('inventory_logs')
            && Schema::hasColumn('inventory_logs', 'reference')
            && ! Schema::hasColumn('inventory_logs', 'ref_note')) {
            Schema::table('inventory_logs', function (Blueprint $t) {
                $t->renameColumn('reference', 'ref_note');
            });
        }

        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'description')) {
            Schema::table('products', function (Blueprint $t) {
                $t->text('description')->nullable()->after('product_name');
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: this only aligns schemas and is not reversible.
    }
};
