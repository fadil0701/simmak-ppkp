<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_criteria', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('standard_section_id')->constrained('standard_sections')->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('title');
            $table->text('requirement_text')->nullable();
            $table->text('assessment_guidance')->nullable();
            $table->text('evidence_guidance')->nullable();
            $table->string('assessment_type', 40)->nullable();
            $table->text('scoring_rule')->nullable(); // hanya jika instrumen atau kebijakan PPKP menentukannya
            $table->string('source_reference', 160)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['standard_section_id', 'code']);
            $table->index(['standard_section_id', 'active']);
            $table->index(['standard_section_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_criteria');
    }
};
