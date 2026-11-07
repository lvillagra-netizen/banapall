<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folio_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 30);
            $table->string('prefix', 10);
            $table->unsignedBigInteger('start_number');
            $table->unsignedBigInteger('end_number');
            $table->unsignedBigInteger('next_number');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['document_type', 'prefix', 'start_number']);
            $table->index(['document_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folio_ranges');
    }
};
