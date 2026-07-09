<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignUuid('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('step_order')->default(1);
            $table->string('status', 50)->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'status'], 'ix_doc_rev_status');
        });

        Schema::create('audit_checklists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('audit_schedule_id')->constrained('audit_schedules')->cascadeOnDelete();
            $table->string('title');
            $table->string('status', 50)->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('audit_checklist_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('checklist_id')->constrained('audit_checklists')->cascadeOnDelete();
            $table->string('item_code', 50)->nullable();
            $table->text('item_text');
            $table->boolean('is_checked')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('audit_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('audit_schedule_id')->constrained('audit_schedules')->cascadeOnDelete();
            $table->string('report_number', 80);
            $table->text('executive_summary')->nullable();
            $table->unsignedSmallInteger('findings_count')->default(0);
            $table->string('status', 50)->default('draft');
            $table->date('report_date');
            $table->foreignUuid('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'report_number'], 'ux_audit_rpt_num');
        });

        Schema::create('capa_root_cause_analyses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('capa_case_id')->constrained('capa_cases')->cascadeOnDelete();
            $table->string('method', 30);
            $table->json('content');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['capa_case_id', 'method'], 'ux_capa_rca_method');
        });

        Schema::create('capa_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('capa_case_id')->constrained('capa_cases')->cascadeOnDelete();
            $table->date('verification_date');
            $table->string('result', 50);
            $table->text('notes')->nullable();
            $table->boolean('is_effective')->default(false);
            $table->foreignUuid('verified_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('risk_matrices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->json('likelihood_labels')->nullable();
            $table->json('impact_labels')->nullable();
            $table->json('level_thresholds')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('risk_mitigation_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('risk_item_id')->constrained('risk_items')->cascadeOnDelete();
            $table->text('plan_description');
            $table->string('status', 50)->default('planned');
            $table->date('target_date')->nullable();
            $table->foreignUuid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quality_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->foreignUuid('fiscal_year_id')->nullable()->constrained('fiscal_years')->nullOnDelete();
            $table->string('program_code', 50);
            $table->string('name');
            $table->text('objective')->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'program_code'], 'ux_qual_prog_code');
        });

        Schema::create('quality_indicators', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('quality_program_id')->constrained('quality_programs')->cascadeOnDelete();
            $table->string('indicator_code', 50);
            $table->string('name');
            $table->string('unit', 50)->nullable();
            $table->decimal('target_value', 10, 2)->nullable();
            $table->string('direction', 20)->default('higher_better');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quality_measurements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('quality_indicator_id')->constrained('quality_indicators')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->date('measurement_date');
            $table->decimal('value', 10, 2);
            $table->text('notes')->nullable();
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['quality_indicator_id', 'measurement_date'], 'ix_qual_meas_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_measurements');
        Schema::dropIfExists('quality_indicators');
        Schema::dropIfExists('quality_programs');
        Schema::dropIfExists('risk_mitigation_plans');
        Schema::dropIfExists('risk_matrices');
        Schema::dropIfExists('capa_verifications');
        Schema::dropIfExists('capa_root_cause_analyses');
        Schema::dropIfExists('audit_reports');
        Schema::dropIfExists('audit_checklist_items');
        Schema::dropIfExists('audit_checklists');
        Schema::dropIfExists('document_reviews');
    }
};
