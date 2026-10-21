<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finding_evidence', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('finding_id')->constrained('audit_findings')->cascadeOnDelete();
            $table->foreignUuid('evidence_id')->constrained('evidence')->cascadeOnDelete();
            $table->text('relationship_note')->nullable();
            $table->timestamps();

            $table->unique(['finding_id', 'evidence_id']);
            $table->index(['finding_id']);
            $table->index(['evidence_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_evidence');
    }
};
