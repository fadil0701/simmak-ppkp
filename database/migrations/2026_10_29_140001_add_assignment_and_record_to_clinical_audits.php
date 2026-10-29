<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit klinis diikat ke jadwal penugasan yang sudah disetujui.
 * Kolom baru nullable agar data lama tetap utuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_audits', function (Blueprint $table) {
            if (! Schema::hasColumn('clinical_audits', 'audit_assignment_id')) {
                $table->foreignUuid('audit_assignment_id')->nullable()->after('clinic_id')
                    ->constrained('audit_assignments')->nullOnDelete();
            }

            if (! Schema::hasColumn('clinical_audits', 'medical_record_ref')) {
                $table->string('medical_record_ref', 80)->nullable()->after('audit_date');
            }

            if (! Schema::hasColumn('clinical_audits', 'problem_analysis')) {
                $table->text('problem_analysis')->nullable()->after('nonconformity_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinical_audits', function (Blueprint $table) {
            if (Schema::hasColumn('clinical_audits', 'problem_analysis')) {
                $table->dropColumn('problem_analysis');
            }
            if (Schema::hasColumn('clinical_audits', 'medical_record_ref')) {
                $table->dropColumn('medical_record_ref');
            }
            if (Schema::hasColumn('clinical_audits', 'audit_assignment_id')) {
                $table->dropConstrainedForeignId('audit_assignment_id');
            }
        });
    }
};
