<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laporan audit sebelumnya hanya menempel pada eksekusi audit, sehingga laporan
 * milik satu tindak lanjut bercampur dengan laporan audit lain pada satu daftar.
 *
 * Migrasi ini menambahkan konteks tindak lanjut pada laporan:
 * - capa_action_id    : laporan milik satu tindakan tindak lanjut (per tindakan).
 * - audit_finding_id  : laporan milik satu temuan tertentu.
 * - original_filename : nama berkas asli untuk laporan yang diunggah pengguna.
 *
 * Semua kolom nullable dan tidak mengubah baris lama (laporan lama tetap
 * menjadi laporan tingkat eksekusi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_reports', 'capa_action_id')) {
                $table->foreignUuid('capa_action_id')->nullable()->after('audit_execution_id')
                    ->constrained('capa_actions')->nullOnDelete();
                $table->index(['capa_action_id'], 'audit_reports_capa_action_id_index');
            }

            if (! Schema::hasColumn('audit_reports', 'audit_finding_id')) {
                $table->foreignUuid('audit_finding_id')->nullable()->after('capa_action_id')
                    ->constrained('audit_findings')->nullOnDelete();
                $table->index(['audit_finding_id'], 'audit_reports_audit_finding_id_index');
            }

            if (! Schema::hasColumn('audit_reports', 'original_filename')) {
                $table->string('original_filename', 255)->nullable()->after('file_path');
            }
        });
    }

    public function down(): void
    {
        // Data laporan yang sudah tertaut tindak lanjut dipertahankan; kolom
        // hanya dilepas bila migrasi benar-benar dibatalkan di lingkungan dev.
        Schema::table('audit_reports', function (Blueprint $table) {
            if (Schema::hasColumn('audit_reports', 'capa_action_id')) {
                $table->dropIndex('audit_reports_capa_action_id_index');
                $table->dropConstrainedForeignId('capa_action_id');
            }

            if (Schema::hasColumn('audit_reports', 'audit_finding_id')) {
                $table->dropIndex('audit_reports_audit_finding_id_index');
                $table->dropConstrainedForeignId('audit_finding_id');
            }

            if (Schema::hasColumn('audit_reports', 'original_filename')) {
                $table->dropColumn('original_filename');
            }
        });
    }
};
