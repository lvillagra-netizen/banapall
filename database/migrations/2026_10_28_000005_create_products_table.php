<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('sku', 40)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('variety', 60)->nullable();
            $table->text('description')->nullable();
            $table->string('unit', 30)->default('caja');
            $table->decimal('package_weight_kg', 8, 2)->default(0);
            $table->boolean('is_organic')->default(true);
            $table->string('certifier', 80)->nullable();
            $table->string('certification_code', 80)->nullable();
            $table->decimal('min_stock', 12, 3)->default(0);
            $table->decimal('cost_clp', 14, 2)->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
