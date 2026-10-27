<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Satu pegawai dapat memegang lebih dari satu klinik/unit kerja.
 *
 * Kolom lama `employees.clinic_id`/`unit_id` tetap dipakai sebagai klinik utama
 * (sumber kebenaran tunggal untuk kompatibilitas), pivot ini menyimpan seluruh
 * klinik yang dipegang beserta unit kerja pada masing-masing klinik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_clinic_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignUuid('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['employee_id', 'clinic_id']);
            $table->index(['organization_id', 'clinic_id']);
        });

        $this->backfillPrimaryAssignments();
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_clinic_assignments');
    }

    /**
     * Salin klinik/unit pegawai yang sudah ada menjadi penugasan utama.
     * Idempoten: baris yang sudah ada dilewati (insertOrIgnore).
     */
    private function backfillPrimaryAssignments(): void
    {
        $now = now();

        DB::table('employees')
            ->whereNotNull('clinic_id')
            ->whereNull('deleted_at')
            ->select(['id', 'organization_id', 'clinic_id', 'unit_id'])
            ->orderBy('id')
            ->chunk(200, function ($employees) use ($now): void {
                $rows = [];

                foreach ($employees as $employee) {
                    $rows[] = [
                        'id' => (string) Str::uuid(),
                        'organization_id' => $employee->organization_id,
                        'employee_id' => $employee->id,
                        'clinic_id' => $employee->clinic_id,
                        'unit_id' => $employee->unit_id,
                        'is_primary' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('employee_clinic_assignments')->insertOrIgnore($rows);
                }
            });
    }
};
