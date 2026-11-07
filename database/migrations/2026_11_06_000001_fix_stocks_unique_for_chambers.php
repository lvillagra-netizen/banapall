<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropUnique(['warehouse_id', 'product_id']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX stocks_warehouse_chamber_product_unique ON stocks (warehouse_id, COALESCE(chamber_id, 0), product_id)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS stocks_warehouse_chamber_product_unique');

        Schema::table('stocks', function (Blueprint $table) {
            $table->unique(['warehouse_id', 'product_id']);
        });
    }
};
