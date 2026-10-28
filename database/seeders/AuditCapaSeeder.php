<?php

namespace Database\Seeders;

use App\Models\AuditFinding;
use App\Models\AuditProgram;
use App\Models\AuditSchedule;
use App\Models\CapaAction;
use App\Models\CapaCase;
use App\Models\Clinic;
use App\Models\FiscalYear;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditCapaSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@simmak.ppkp.local')->first();
        $clinic = Clinic::where('organization_id', $organization?->id)->where('clinic_type', 'klinik_utama')->first()
            ?? Clinic::where('organization_id', $organization?->id)->first();

        if (! $organization || ! $admin || ! $clinic) {
            return;
        }

        $fiscalYear = FiscalYear::firstOrCreate(
            ['organization_id' => $organization->id, 'code' => 'FY2025'],
            [
                'name' => 'Tahun Anggaran 2025',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'is_current' => true,
                'is_active' => true,
            ]
        );

        $program = AuditProgram::firstOrCreate(
            ['organization_id' => $organization->id, 'program_code' => 'AUD-2025-01'],
            [
                'fiscal_year_id' => $fiscalYear->id,
                'name' => 'Program Audit Internal Mutu 2025',
                'objective' => 'Memastikan kepatuhan terhadap standar akreditasi FKTP/FKTL dan kebijakan mutu PPKP DKI Jakarta.',
                'status' => 'active',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $schedule = AuditSchedule::firstOrCreate(
            ['organization_id' => $organization->id, 'schedule_code' => 'SCH-2025-001'],
            [
                'audit_program_id' => $program->id,
                'clinic_id' => $clinic->id,
                'title' => 'Audit Internal Klinik Utama — Q2 2025',
                'scope' => 'Tata kelola klinis, keselamatan pasien, manajemen dokumen mutu',
                'scheduled_start' => now()->subWeeks(2)->toDateString(),
                'scheduled_end' => now()->addWeeks(1)->toDateString(),
                'status' => 'ongoing',
                'location' => $clinic->name,
                'lead_auditor_id' => $admin->id,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $findingsData = [
            ['code' => 'TMN-2025-0001', 'title' => 'Dokumen SOP tidak terbarui', 'severity' => 'major', 'status' => 'in_capa'],
            ['code' => 'TMN-2025-0002', 'title' => 'Identifikasi pasien tidak konsisten', 'severity' => 'critical', 'status' => 'open'],
            ['code' => 'TMN-2025-0003', 'title' => 'Pencatatan suhu vaksin tidak lengkap', 'severity' => 'minor', 'status' => 'open'],
            ['code' => 'TMN-2025-0004', 'title' => 'APD tidak tersedia di ruang triase', 'severity' => 'major', 'status' => 'closed'],
        ];

        foreach ($findingsData as $data) {
            AuditFinding::firstOrCreate(
                ['organization_id' => $organization->id, 'finding_code' => $data['code']],
                [
                    'audit_schedule_id' => $schedule->id,
                    'clinic_id' => $clinic->id,
                    'title' => $data['title'],
                    'severity' => $data['severity'],
                    'description' => 'Temuan hasil audit internal pada '.$clinic->name,
                    'requirement_reference' => 'Standar Akreditasi FKTP — Bab 2',
                    'status' => $data['status'],
                    'assigned_to' => $admin->id,
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]
            );
        }

        $findingWithCapa = AuditFinding::where('finding_code', 'TMN-2025-0001')->first();

        if ($findingWithCapa) {
            $capa = CapaCase::firstOrCreate(
                ['organization_id' => $organization->id, 'case_number' => 'TL-2025-0001'],
                [
                    'clinic_id' => $clinic->id,
                    'source_type' => 'audit_finding',
                    'source_id' => $findingWithCapa->id,
                    'priority' => 'high',
                    'problem_statement' => $findingWithCapa->title.': Dokumen SOP pelayanan belum direview sesuai jadwal.',
                    'status' => 'action',
                    'owner_id' => $admin->id,
                    'target_close_date' => now()->addDays(21)->toDateString(),
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]
            );

            CapaAction::firstOrCreate(
                ['capa_case_id' => $capa->id, 'action_type' => 'corrective'],
                [
                    'description' => 'Review dan perbarui seluruh SOP pelayanan rawat jalan',
                    'assigned_to' => $admin->id,
                    'due_date' => now()->addDays(14)->toDateString(),
                    'status' => 'in_progress',
                ]
            );

            CapaAction::firstOrCreate(
                ['capa_case_id' => $capa->id, 'action_type' => 'preventive'],
                [
                    'description' => 'Jadwalkan reminder otomatis review dokumen 30 hari sebelum kedaluwarsa',
                    'assigned_to' => $admin->id,
                    'due_date' => now()->addDays(21)->toDateString(),
                    'status' => 'pending',
                ]
            );
        }
    }
}
