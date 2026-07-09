<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accreditation_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('issuing_body')->default('KARS');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accreditation_chapters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accreditation_type_id')->constrained('accreditation_types')->cascadeOnDelete();
            $table->string('chapter_number', 20);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['accreditation_type_id', 'chapter_number'], 'ux_acc_ch_type_num');
        });

        Schema::create('accreditation_standards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chapter_id')->constrained('accreditation_chapters')->cascadeOnDelete();
            $table->string('standard_code', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['chapter_id', 'standard_code'], 'ux_acc_std_ch_code');
        });

        Schema::create('accreditation_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignUuid('accreditation_type_id')->constrained('accreditation_types')->restrictOnDelete();
            $table->string('program_name');
            $table->string('cycle', 50);
            $table->date('start_date');
            $table->date('target_survey_date');
            $table->string('status', 50)->default('planning');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'clinic_id', 'status'], 'ix_acc_prog_org');
        });

        Schema::create('accreditation_self_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('program_id')->constrained('accreditation_programs')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->date('assessment_date');
            $table->foreignUuid('assessor_id')->constrained('users')->restrictOnDelete();
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->string('status', 50)->default('draft');
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'clinic_id'], 'ix_acc_sa_org');
        });

        Schema::create('accreditation_self_assessment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('self_assessment_id')->constrained('accreditation_self_assessments')->cascadeOnDelete();
            $table->foreignUuid('standard_id')->constrained('accreditation_standards')->restrictOnDelete();
            $table->decimal('score', 5, 2)->default(0);
            $table->string('compliance_status', 50)->default('not_assessed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['self_assessment_id', 'standard_id'], 'ux_acc_sa_item_std');
        });

        Schema::create('accreditation_gap_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('program_id')->constrained('accreditation_programs')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignUuid('standard_id')->nullable()->constrained('accreditation_standards')->nullOnDelete();
            $table->text('gap_description');
            $table->string('severity', 30);
            $table->string('status', 50)->default('open');
            $table->text('recommended_action')->nullable();
            $table->date('target_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['organization_id', 'program_id', 'severity'], 'ix_acc_gap_org');
        });

        Schema::create('accreditation_evidence', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('program_id')->constrained('accreditation_programs')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignUuid('standard_id')->nullable()->constrained('accreditation_standards')->nullOnDelete();
            $table->string('title');
            $table->string('status', 50)->default('required');
            $table->foreignUuid('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'program_id', 'status'], 'ix_acc_evd_org');
        });

        Schema::create('accreditation_mock_surveys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('program_id')->constrained('accreditation_programs')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->date('survey_date');
            $table->decimal('score', 5, 2)->default(0);
            $table->string('surveyor_name')->nullable();
            $table->string('status', 50)->default('completed');
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accreditation_mock_surveys');
        Schema::dropIfExists('accreditation_evidence');
        Schema::dropIfExists('accreditation_gap_items');
        Schema::dropIfExists('accreditation_self_assessment_items');
        Schema::dropIfExists('accreditation_self_assessments');
        Schema::dropIfExists('accreditation_programs');
        Schema::dropIfExists('accreditation_standards');
        Schema::dropIfExists('accreditation_chapters');
        Schema::dropIfExists('accreditation_types');
    }
};
