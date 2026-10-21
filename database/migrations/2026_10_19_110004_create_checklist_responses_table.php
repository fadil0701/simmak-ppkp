<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('audit_execution_id')->constrained('audit_executions')->cascadeOnDelete();
            $table->foreignUuid('standard_criterion_id')->nullable()->constrained('standard_criteria')->nullOnDelete();
            $table->string('response_value', 40)->nullable(); // compliant, non_compliant, partial, not_applicable, not_assessable
            $table->text('assessment_notes')->nullable();
            $table->string('method_used', 80)->nullable();
            $table->text('sample_description')->nullable();
            $table->text('not_applicable_reason')->nullable();
            $table->text('not_assessable_reason')->nullable();
            $table->foreignUuid('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['audit_execution_id', 'standard_criterion_id']);
            $table->index(['audit_execution_id']);
            $table->index(['standard_criterion_id']);
            $table->index(['response_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_responses');
    }
};
