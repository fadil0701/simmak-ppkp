<?php

namespace Tests\Feature\Audit;

use App\Models\AuditAssignment;
use App\Models\AuditProgram;
use App\Models\AuditProgramDocument;
use App\Models\AuditTeamMember;
use App\Models\Clinic;
use App\Models\FiscalYear;
use App\Models\Organization;
use App\Models\User;
use App\Support\SimmakRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProgramReferenceDocumentTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private Clinic $clinicA;

    private Clinic $clinicB;

    private FiscalYear $fiscalYear;

    private User $planner;

    private User $auditor;

    private User $auditi;

    private User $otherAuditi;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->org = Organization::create([
            'code' => 'PPKP-DKI',
            'name' => 'PPKP DKI',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $this->clinicA = Clinic::create([
            'organization_id' => $this->org->id,
            'code' => 'KA',
            'name' => 'Klinik A',
            'clinic_type' => 'pratama',
            'is_auditable' => true,
            'is_active' => true,
        ]);
        $this->clinicB = Clinic::create([
            'organization_id' => $this->org->id,
            'code' => 'KB',
            'name' => 'Klinik B',
            'clinic_type' => 'pratama',
            'is_auditable' => true,
            'is_active' => true,
        ]);

        $this->fiscalYear = FiscalYear::create([
            'organization_id' => $this->org->id,
            'code' => 'FY2026',
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        SimmakRoles::sync();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->planner = $this->makeUser('mutu@test.local', SimmakRoles::KETUA_TIM_MUTU, $this->clinicA);
        $this->auditor = $this->makeUser('auditor@test.local', SimmakRoles::AUDITOR, $this->clinicA);
        $this->auditi = $this->makeUser('auditi@test.local', SimmakRoles::KEPALA_KLINIK, $this->clinicA);
        $this->otherAuditi = $this->makeUser('auditi-b@test.local', SimmakRoles::KEPALA_KLINIK, $this->clinicB);
    }

    private function makeUser(string $email, string $role, Clinic $clinic): User
    {
        $user = User::create([
            'organization_id' => $this->org->id,
            'name' => $email,
            'email' => $email,
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
            'default_clinic_id' => $clinic->id,
        ]);
        $user->clinics()->attach($clinic->id, ['is_default' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function fakePdf(string $name): UploadedFile
    {
        $content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    /** @test */
    public function program_can_store_multiple_reference_documents_and_keep_them_on_update(): void
    {
        $this->actingAs($this->planner)
            ->post(route('audit.programs.store'), [
                'name' => 'Program dengan dokumen acuan',
                'fiscal_year_id' => $this->fiscalYear->id,
                'documents' => [
                    ['title' => 'Instrumen Klinik', 'description' => 'Acuan pemeriksaan', 'file' => $this->fakePdf('instrumen.pdf')],
                    ['title' => 'SPO Pelayanan', 'description' => null, 'file' => $this->fakePdf('spo.pdf')],
                ],
            ])
            ->assertRedirect();

        $program = AuditProgram::query()->where('name', 'Program dengan dokumen acuan')->firstOrFail();
        $this->assertSame(2, $program->documents()->count());
        $first = $program->documents()->orderBy('title')->first();
        $this->assertSame('Instrumen Klinik', $first->title);
        $this->assertTrue(Storage::disk('local')->exists($first->storage_path));

        $this->actingAs($this->planner)
            ->put(route('audit.programs.update', $program), [
                'name' => 'Program dengan dokumen acuan',
                'fiscal_year_id' => $this->fiscalYear->id,
                'documents' => [
                    [
                        'id' => $first->id,
                        'title' => 'Instrumen Klinik (revisi judul)',
                        'description' => 'Acuan pemeriksaan',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(2, $program->documents()->count());
        $this->assertSame('Instrumen Klinik (revisi judul)', $first->fresh()->title);
        $this->assertTrue(Storage::disk('local')->exists($first->fresh()->storage_path));
    }

    /** @test */
    public function assigned_auditor_and_clinic_auditi_can_access_documents_on_published_schedule(): void
    {
        $program = AuditProgram::create([
            'organization_id' => $this->org->id,
            'program_code' => 'PRG-DOC-01',
            'name' => 'Program Acuan',
            'status' => 'approved',
            'created_by' => $this->planner->id,
            'updated_by' => $this->planner->id,
        ]);

        $document = AuditProgramDocument::create([
            'organization_id' => $this->org->id,
            'audit_program_id' => $program->id,
            'title' => 'Pedoman Audit Internal',
            'description' => 'Untuk tim dan auditi',
            'storage_disk' => 'local',
            'storage_path' => 'program_documents/pedoman.pdf',
            'original_filename' => 'pedoman.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 120,
            'checksum' => hash('sha256', 'pdf'),
            'uploaded_by' => $this->planner->id,
            'uploaded_at' => now(),
            'updated_by' => $this->planner->id,
        ]);
        Storage::disk('local')->put($document->storage_path, "%PDF-1.4\n%%EOF\n");

        $assignment = AuditAssignment::create([
            'organization_id' => $this->org->id,
            'audit_program_id' => $program->id,
            'clinic_id' => $this->clinicA->id,
            'assignment_code' => 'TUGAS-DOC-01',
            'title' => 'Jadwal Klinik A',
            'lead_auditor_id' => $this->auditor->id,
            'scheduled_start' => '2026-11-10',
            'scheduled_end' => '2026-11-12',
            'status' => 'approved',
            'conflict_check_status' => 'resolved',
            'created_by' => $this->planner->id,
            'updated_by' => $this->planner->id,
        ]);

        AuditTeamMember::create([
            'organization_id' => $this->org->id,
            'audit_assignment_id' => $assignment->id,
            'user_id' => $this->auditor->id,
            'team_role' => 'lead_auditor',
            'independence_declared' => true,
            'conflict_declared' => false,
            'active' => true,
            'created_by' => $this->planner->id,
            'updated_by' => $this->planner->id,
        ]);

        $this->actingAs($this->auditor)
            ->get(route('audit.assignments.show', $assignment))
            ->assertOk()
            ->assertSee('Dokumen Acuan Program Audit')
            ->assertSee('Pedoman Audit Internal');

        $this->actingAs($this->auditor)
            ->get(route('audit.calendar', ['year' => 2026, 'month' => 11]))
            ->assertOk()
            ->assertSee('Dokumen Acuan Audit')
            ->assertSee('Pedoman Audit Internal');

        $this->actingAs($this->auditor)
            ->get(route('audit.program-documents.download', $document))
            ->assertOk();

        $this->actingAs($this->auditi)
            ->get(route('audit.program-documents.download', $document))
            ->assertOk();

        $this->actingAs($this->otherAuditi)
            ->get(route('audit.program-documents.download', $document))
            ->assertForbidden();

        $assignment->update(['status' => 'proposed']);

        $this->actingAs($this->auditor)
            ->get(route('audit.program-documents.download', $document))
            ->assertForbidden();
    }
}
