<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Judul standar/instrumen harus tersedia sejak penugasan dibuat, sebelum
 * eksekusi audit lahir (lihat PERBAIKAN_MASTER_PEGAWAI_PENGGUNA_PENUGASAN.md §4.3).
 * Nullable + fallback ke executions agar data lama tetap tampil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_assignments', function (Blueprint $table) {
            $table->foreignUuid('instrument_version_id')
                ->nullable()
                ->constrained('instrument_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('instrument_version_id');
        });
    }
};
