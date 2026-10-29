<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Audit Klinis.
 *
 * Audit klinis adalah jenis audit tersendiri (bukan program/penugasan audit
 * internal) yang tetap memakai Temuan Audit, Tindak Lanjut (CAPA), Laporan, dan
 * Bukti Arsip yang sudah ada. Karena itu migrasi ini hanya menambah:
 * - clinical_audits       : kepala audit klinis (nomor, ruang lingkup, standar, hasil).
 * - clinical_audit_items  : elemen penilaian + hasil per elemen.
 * - audit_findings.clinical_audit_id : jejak temuan ke audit klinis asalnya.
 * - evidence.clinical_audit_id       : dokumentasi pendukung audit klinis.
 *
 * Kolom baru semuanya nullable sehingga data lama tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clinical_audits')) {
            Schema::create('clinical_audits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
                $table->string('audit_number', 80);
                $table->string('title');
                $table->date('audit_date');
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->foreignUuid('auditor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('pic_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('audit_standard_id')->nullable()->constrained('audit_standards')->nullOnDelete();
                $table->string('standard_reference', 255)->nullable();
                $table->text('scope')->nullable();
                $table->text('criteria')->nullable();
                $table->text('indicators')->nullable();
                $table->text('objective_evidence')->nullable();
                $table->text('assessment_result')->nullable();
                $table->unsignedTinyInteger('conformity_score')->nullable();
                $table->text('nonconformity_notes')->nullable();
                $table->text('notes')->nullable();
                $table->text('conclusion')->nullable();
                $table->string('status', 40)->default('draft'); // draft, in_progress, completed, closed
                $table->json('metadata')->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['organization_id', 'audit_number']);
                $table->index(['organization_id', 'status']);
                $table->index(['clinic_id']);
                $table->index(['audit_date']);
                $table->index(['auditor_id']);
            });
        }

        if (! Schema::hasTable('clinical_audit_items')) {
            Schema::create('clinical_audit_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->foreignUuid('clinical_audit_id')->constrained('clinical_audits')->cascadeOnDelete();
                $table->string('code', 60)->nullable();
                $table->text('element');
                $table->text('criteria')->nullable();
                $table->string('indicator', 255)->nullable();
                $table->text('objective_evidence')->nullable();
                $table->string('result', 20)->default('na'); // conform, partial, non_conform, na
                $table->unsignedTinyInteger('score')->nullable(); // skala kesesuaian 0/5/10
                $table->text('finding_note')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['clinical_audit_id'], 'clinical_audit_items_audit_id_index');
                $table->index(['result']);
            });
        }

        if (! Schema::hasColumn('audit_findings', 'clinical_audit_id')) {
            Schema::table('audit_findings', function (Blueprint $table) {
                $table->foreignUuid('clinical_audit_id')->nullable()->after('audit_execution_id')
                    ->constrained('clinical_audits')->nullOnDelete();
                $table->index(['clinical_audit_id'], 'audit_findings_clinical_audit_id_index');
            });
        }

        if (! Schema::hasColumn('evidence', 'clinical_audit_id')) {
            Schema::table('evidence', function (Blueprint $table) {
                $table->foreignUuid('clinical_audit_id')->nullable()->after('audit_execution_id')
                    ->constrained('clinical_audits')->nullOnDelete();
                $table->index(['clinical_audit_id'], 'evidence_clinical_audit_id_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('evidence', 'clinical_audit_id')) {
            Schema::table('evidence', function (Blueprint $table) {
                $table->dropIndex('evidence_clinical_audit_id_index');
                $table->dropConstrainedForeignId('clinical_audit_id');
            });
        }

        if (Schema::hasColumn('audit_findings', 'clinical_audit_id')) {
            Schema::table('audit_findings', function (Blueprint $table) {
                $table->dropIndex('audit_findings_clinical_audit_id_index');
                $table->dropConstrainedForeignId('clinical_audit_id');
            });
        }

        Schema::dropIfExists('clinical_audit_items');
        Schema::dropIfExists('clinical_audits');
    }
};
