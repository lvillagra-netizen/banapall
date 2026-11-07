<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('folio', 30);
            $table->string('status', 20)->default('emitido');
            $table->timestamp('issued_at')->nullable();
            $table->json('customer_snapshot')->nullable();
            $table->json('totals_snapshot')->nullable();
            $table->json('tax_snapshot')->nullable();
            $table->string('pdf_path')->nullable();
            $table->longText('xml_payload')->nullable();
            $table->string('sii_status', 20)->default('simulado');
            $table->string('accounting_status', 20)->default('registrado');
            $table->timestamps();

            $table->unique(['type', 'folio']);
            $table->index(['sale_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
