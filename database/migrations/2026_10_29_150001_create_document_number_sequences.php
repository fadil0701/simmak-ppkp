<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Urutan nomor dokumen bisnis per organisasi dan seri (jenis + tahun).
 * Tidak mengubah nomor yang sudah tersimpan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_number_sequences')) {
            return;
        }

        Schema::create('document_number_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('series', 80);
            $table->unsignedInteger('last_value')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'series']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_number_sequences');
    }
};
