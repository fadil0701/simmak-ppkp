<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menandai unit kerja yang menjadi sasaran audit internal.
     *
     * Manajemen Pusat (kantor pusat) bukan unit kerja yang diaudit secara
     * internal, sehingga ditandai `is_auditable = false`. Klinik/Satpelkes
     * (pratama & utama) bersifat auditable.
     */
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->boolean('is_auditable')->default(true)->after('is_main_clinic');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn('is_auditable');
        });
    }
};
