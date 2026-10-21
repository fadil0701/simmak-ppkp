<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Temuan audit kini diturunkan dari eksekusi audit (audit_execution_id) sesuai
 * alur Audit Internal, sehingga kolom lama audit_schedule_id tidak lagi wajib.
 *
 * Mengikuti pola yang sama dengan migrasi
 * 2026_10_19_160001_extend_audit_reports_for_reporting yang membuat
 * audit_reports.audit_schedule_id menjadi nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('audit_findings', 'audit_schedule_id')) {
            Schema::table('audit_findings', function (Blueprint $table) {
                $table->uuid('audit_schedule_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan ke NOT NULL agar data yang sudah ada tidak gagal.
    }
};
