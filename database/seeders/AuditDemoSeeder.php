<?php

namespace Database\Seeders;

use App\Models\AuditAssignment;
use App\Models\AuditExecution;
use App\Models\AuditProgram;
use App\Models\AuditTeamMember;
use App\Models\Clinic;
use App\Models\FiscalYear;
use App\Models\InstrumentVersion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data demonstrasi modul Audit Internal.
 *
 * Hanya untuk lingkungan non-produksi. Tidak memuat data pasien.
 */
class AuditDemoSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('code', 'PPKP-DKI')->first();
        $admin = User::where('email', 'admin@simmak.ppkp.local')->first()
            ?? User::where('email', 'mutu@simmak.ppkp.local')->first();
        $auditor = User::where('email', 'auditor@simmak.ppkp.local')->first();

        if (! $organization || ! $admin) {
            return;
        }

        $clinic = Clinic::where('organization_id', $organization->id)
            ->where('code', 'KP06')
            ->first()
            ?? Clinic::where('organization_id', $organization->id)->where('is_auditable', true)->first();

        if (! $clinic) {
            return;
        }

        $fiscalYear = FiscalYear::where('organization_id', $organization->id)
            ->where('is_current', true)
            ->first()
            ?? FiscalYear::where('organization_id', $organization->id)->first();

        $program = AuditProgram::firstOrCreate(
            ['organization_id' => $organization->id, 'program_code' => 'AUD-DEMO-2026-01'],
            [
                'fiscal_year_id' => $fiscalYear?->id,
                'name' => 'Program Audit Internal Klinik (Data Demonstrasi)',
                'objective' => 'Contoh alur audit internal: perencanaan, penugasan, pelaksanaan tabel EP, temuan, hingga CAPA.',
                'audit_type' => 'periodic',
                'scope_notes' => 'Seluruh elemen penilaian TKK, PMKP, dan PKP sesuai instrumen versi UNVERIFIED.',
                'status' => 'approved',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $assignment = AuditAssignment::firstOrCreate(
            ['organization_id' => $organization->id, 'assignment_code' => 'ASG-DEMO-2026-01'],
            [
                'audit_program_id' => $program->id,
                'clinic_id' => $clinic->id,
                'title' => 'Penugasan Audit Internal '.$clinic->name.' (Data Demonstrasi)',
                'scope_notes' => 'Pemeriksaan seluruh EP pada instrumen aktif.',
                'lead_auditor_id' => $auditor?->id ?? $admin->id,
                'scheduled_start' => now()->toDateString(),
                'scheduled_end' => now()->addDays(14)->toDateString(),
                'status' => 'active',
                'conflict_check_status' => 'resolved',
                'activated_at' => now(),
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        foreach (array_filter([$auditor?->id, $admin->id]) as $userId) {
            AuditTeamMember::firstOrCreate(
                ['audit_assignment_id' => $assignment->id, 'user_id' => $userId],
                [
                    'organization_id' => $organization->id,
                    'team_role' => $userId === $assignment->lead_auditor_id ? 'lead_auditor' : 'auditor',
                    'independence_declared' => true,
                    'independence_declared_at' => now(),
                    'conflict_declared' => false,
                    'active' => true,
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]
            );
        }

        $version = InstrumentVersion::where('status', 'active')
            ->whereHas('standard', fn ($query) => $query->where('organization_id', $organization->id))
            ->orderBy('created_at')
            ->first();

        if (! $version) {
            return;
        }

        AuditExecution::firstOrCreate(
            ['organization_id' => $organization->id, 'execution_code' => 'EXEC-DEMO-2026-01'],
            [
                'audit_program_id' => $program->id,
                'audit_assignment_id' => $assignment->id,
                'clinic_id' => $clinic->id,
                'instrument_version_id' => $version->id,
                'title' => 'Pelaksanaan Audit '.$clinic->name.' (Data Demonstrasi)',
                'status' => 'in_progress',
                'started_at' => now(),
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );
    }
}
