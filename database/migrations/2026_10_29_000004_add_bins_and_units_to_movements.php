<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('bin_id')->nullable()->after('product_id')->constrained('bins')->nullOnDelete();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('bin_id')->nullable()->after('product_id')->constrained('bins')->nullOnDelete();
            $table->string('unit', 10)->default('kg')->after('quantity');
            $table->foreignId('approved_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bin_id');
            $table->dropColumn(['unit', 'approved_by', 'approved_at']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bin_id');
        });
    }
};