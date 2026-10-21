<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_response_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('checklist_response_id')->constrained('checklist_responses')->cascadeOnDelete();
            $table->foreignUuid('audit_execution_id')->nullable()->constrained('audit_executions')->nullOnDelete();
            $table->foreignUuid('standard_criterion_id')->nullable()->constrained('standard_criteria')->nullOnDelete();
            $table->string('response_value', 40)->nullable();
            $table->text('assessment_notes')->nullable();
            $table->string('method_used', 80)->nullable();
            $table->text('sample_description')->nullable();
            $table->text('not_applicable_reason')->nullable();
            $table->text('not_assessable_reason')->nullable();
            $table->foreignUuid('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at')->nullable();
            $table->text('revision_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['checklist_response_id']);
            $table->index(['audit_execution_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_response_revisions');
    }
};
