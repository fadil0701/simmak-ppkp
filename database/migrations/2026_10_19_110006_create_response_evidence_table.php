<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('response_evidence', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('checklist_response_id')->constrained('checklist_responses')->cascadeOnDelete();
            $table->foreignUuid('evidence_id')->constrained('evidence')->cascadeOnDelete();
            $table->text('caption')->nullable();
            $table->timestamps();

            $table->unique(['checklist_response_id', 'evidence_id']);
            $table->index(['checklist_response_id']);
            $table->index(['evidence_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('response_evidence');
    }
};
