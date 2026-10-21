<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_standards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('source_document_id')->nullable()->constrained('regulatory_sources')->nullOnDelete();
            $table->string('code', 80);
            $table->string('title');
            $table->string('status', 40)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'status']);
            $table->index(['source_document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_standards');
    }
};
