<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('currency_code', 3);
            $table->decimal('price', 14, 2);
            $table->timestamps();

            $table->unique(['product_id', 'currency_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
