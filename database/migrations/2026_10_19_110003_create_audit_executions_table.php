<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('audit_program_id')->nullable()->constrained('audit_programs')->nullOnDelete();
            $table->foreignUuid('audit_assignment_id')->nullable()->constrained('audit_assignments')->nullOnDelete();
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->foreignUuid('instrument_version_id')->nullable()->constrained('instrument_versions')->nullOnDelete();
            $table->string('execution_code', 80);
            $table->string('title');
            $table->string('status', 40)->default('preparation'); // preparation, in_progress, submitted_for_review, finalized, cancelled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignUuid('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('report_number', 80)->nullable();
            $table->json('summary_snapshot')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'execution_code']);
            $table->index(['organization_id', 'status']);
            $table->index(['clinic_id', 'status']);
            $table->index(['instrument_version_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_executions');
    }
};
