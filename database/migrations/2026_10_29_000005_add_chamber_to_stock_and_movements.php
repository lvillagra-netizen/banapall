<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->foreignId('chamber_id')->nullable()->after('warehouse_id')->constrained('chambers')->nullOnDelete();
            $table->index(['warehouse_id', 'chamber_id', 'product_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('chamber_id')->nullable()->after('bin_id')->constrained('chambers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex(['warehouse_id', 'chamber_id', 'product_id']);
            $table->dropConstrainedForeignId('chamber_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chamber_id');
        });
    }
};