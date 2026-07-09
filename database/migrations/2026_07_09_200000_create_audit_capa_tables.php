<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('fiscal_year_id')->nullable()->constrained('fiscal_years')->nullOnDelete();
            $table->string('program_code', 80);
            $table->string('name');
            $table->text('objective')->nullable();
            $table->string('status', 50)->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'program_code']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('audit_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('audit_program_id')->constrained('audit_programs')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->string('schedule_code', 80);
            $table->string('title');
            $table->text('scope')->nullable();
            $table->date('scheduled_start');
            $table->date('scheduled_end');
            $table->date('actual_start')->nullable();
            $table->date('actual_end')->nullable();
            $table->string('status', 50)->default('scheduled');
            $table->string('location')->nullable();
            $table->foreignUuid('lead_auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'schedule_code']);
            $table->index(['clinic_id', 'status', 'scheduled_start']);
        });

        Schema::create('audit_findings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('audit_schedule_id')->constrained('audit_schedules')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->string('finding_code', 80);
            $table->string('title');
            $table->string('severity', 50)->default('minor');
            $table->text('description')->nullable();
            $table->text('requirement_reference')->nullable();
            $table->string('status', 50)->default('open');
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('identified_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'finding_code']);
            $table->index(['organization_id', 'status', 'severity']);
        });

        Schema::create('capa_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->string('case_number', 80);
            $table->string('source_type', 80)->default('audit_finding');
            $table->uuid('source_id')->nullable();
            $table->string('priority', 50)->default('medium');
            $table->text('problem_statement');
            $table->string('status', 50)->default('open');
            $table->foreignUuid('owner_id')->constrained('users')->restrictOnDelete();
            $table->date('target_close_date');
            $table->date('closed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['organization_id', 'case_number']);
            $table->index(['organization_id', 'status', 'priority']);
        });

        Schema::create('capa_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('capa_case_id')->constrained('capa_cases')->cascadeOnDelete();
            $table->string('action_type', 30);
            $table->text('description');
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status', 50)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['capa_case_id', 'action_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capa_actions');
        Schema::dropIfExists('capa_cases');
        Schema::dropIfExists('audit_findings');
        Schema::dropIfExists('audit_schedules');
        Schema::dropIfExists('audit_programs');
    }
};
