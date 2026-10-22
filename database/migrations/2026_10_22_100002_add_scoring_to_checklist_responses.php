<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skor maksimum per elemen penilaian (kolom "SKOR Maksimal" pada instrumen).
        Schema::table('standard_criteria', function (Blueprint $table) {
            $table->integer('max_score')->default(10)->after('scoring_rule');
        });

        // Nilai, fakta & analisis, serta rekomendasi diisi petugas pada tabel pemeriksaan.
        Schema::table('checklist_responses', function (Blueprint $table) {
            $table->integer('score')->nullable()->after('response_value');
            $table->integer('max_score')->nullable()->after('score');
            $table->text('facts_analysis')->nullable()->after('assessment_notes');
            $table->text('recommendation')->nullable()->after('facts_analysis');
        });

        // Riwayat perubahan ikut menyimpan nilai & rekomendasi.
        Schema::table('checklist_response_revisions', function (Blueprint $table) {
            $table->integer('score')->nullable()->after('response_value');
            $table->text('facts_analysis')->nullable()->after('assessment_notes');
            $table->text('recommendation')->nullable()->after('facts_analysis');
        });
    }

    public function down(): void
    {
        Schema::table('checklist_response_revisions', function (Blueprint $table) {
            $table->dropColumn(['score', 'facts_analysis', 'recommendation']);
        });

        Schema::table('checklist_responses', function (Blueprint $table) {
            $table->dropColumn(['score', 'max_score', 'facts_analysis', 'recommendation']);
        });

        Schema::table('standard_criteria', function (Blueprint $table) {
            $table->dropColumn('max_score');
        });
    }
};
