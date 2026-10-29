<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persetujuan akhir Kepala Pusat atas jadwal penugasan audit.
 *
 * Status workflow yang sudah ada (draft → proposed → active → completed/cancelled)
 * dipertahankan; ditambah dua status baru:
 * - pending approval memakai status `proposed` (sudah "diajukan"),
 * - `approved` = disetujui/publikasi (muncul di kalender pengguna terkait),
 * - `rejected` = ditolak (tidak dipublikasikan, alasan disimpan).
 *
 * Kolom baru nullable sehingga data lama tidak berubah. Penugasan yang sudah
 * `active` sebelum aturan ini tetap dianggap sudah dipublikasikan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_assignments', 'approved_by')) {
                $table->foreignUuid('approved_by')->nullable()->after('activated_at')
                    ->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('audit_assignments', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('audit_assignments', 'rejected_by')) {
                $table->foreignUuid('rejected_by')->nullable()->after('approved_at')
                    ->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('audit_assignments', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }

            if (! Schema::hasColumn('audit_assignments', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
        });
    }

    public function down(): void
    {
        // Jejak persetujuan/penolakan dipertahankan; kolom hanya dilepas di dev.
        Schema::table('audit_assignments', function (Blueprint $table) {
            foreach (['rejection_reason', 'rejected_at', 'approved_at'] as $column) {
                if (Schema::hasColumn('audit_assignments', $column)) {
                    $table->dropColumn($column);
                }
            }

            foreach (['rejected_by', 'approved_by'] as $column) {
                if (Schema::hasColumn('audit_assignments', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });
    }
};
