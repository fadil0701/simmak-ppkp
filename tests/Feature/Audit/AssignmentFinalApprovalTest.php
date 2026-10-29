<?php

namespace Tests\Feature\Audit;

use App\Models\AuditAssignment;
use App\Models\AuditProgram;
use App\Models\AuditTeamMember;
use App\Models\AuditTrail;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use App\Support\SimmakRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AssignmentFinalApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected Clinic $clinicA;

    protected Clinic $clinicB;

    protected AuditProgram $program;

    protected User $kepalaPusat;

    protected User $kasatpel;

    protected User $auditor;

    protected User $auditi;

    protected User $otherAuditor;

    protected function setUp(): void
    {
        parent::setUp();

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

        SimmakRoles::sync();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->program = AuditProgram::create([
            'organization_id' => $this->org->id,
            'program_code' => 'PRG-FA',
            'name' => 'Program Persetujuan Jadwal',
            'status' => 'approved',
        ]);

        $this->kepalaPusat = $this->makeUser('kepala-pusat@test.local', SimmakRoles::KEPALA_PUSAT, $this->clinicA);
        $this->kasatpel = $this->makeUser('kasatpel@test.local', SimmakRoles::KASATPEL_UKP, $this->clinicA);
        $this->auditor = $this->makeUser('auditor@test.local', SimmakRoles::AUDITOR, $this->clinicA);
        $this->auditi = $this->makeUser('auditi@test.local', SimmakRoles::KEPALA_KLINIK, $this->clinicA);
        $this->otherAuditor = $this->makeUser('auditor-b@test.local', SimmakRoles::AUDITOR, $this->clinicB);
    }

    protected function makeUser(string $email, string $role, Clinic $clinic): User
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

    protected function makeAssignment(string $status, User $lead, Clinic $clinic, string $code = 'TUGAS-FA-001'): AuditAssignment
    {
        $assignment = AuditAssignment::create([
            'organization_id' => $this->org->id,
            'audit_program_id' => $this->program->id,
            'clinic_id' => $clinic->id,
            'assignment_code' => $code,
            'title' => 'Jadwal '.$code,
            'lead_auditor_id' => $lead->id,
            'scheduled_start' => '2026-11-10',
            'scheduled_end' => '2026-11-12',
            'status' => $status,
            'conflict_check_status' => 'resolved',
            'created_by' => $this->kasatpel->id,
            'updated_by' => $this->kasatpel->id,
        ]);

        AuditTeamMember::create([
            'organization_id' => $this->org->id,
            'audit_assignment_id' => $assignment->id,
            'user_id' => $lead->id,
            'team_role' => 'lead_auditor',
            'independence_declared' => true,
            'conflict_declared' => false,
            'active' => true,
            'created_by' => $this->kasatpel->id,
            'updated_by' => $this->kasatpel->id,
        ]);

        return $assignment;
    }

    /** @test */
    public function kepala_pusat_approves_and_publishes_the_schedule(): void
    {
        $assignment = $this->makeAssignment('proposed', $this->auditor, $this->clinicA);

        $this->actingAs($this->kepalaPusat)
            ->post('/audit/assignments/'.$assignment->id.'/approve')
            ->assertRedirect();

        $assignment->refresh();
        $this->assertSame('approved', $assignment->status);
        $this->assertSame($this->kepalaPusat->id, $assignment->approved_by);
        $this->assertNotNull($assignment->approved_at);
        $this->assertTrue($assignment->isPublished());

        $this->assertDatabaseHas('approval_events', [
            'entity_type' => 'audit_assignment',
            'entity_id' => $assignment->id,
            'decision' => 'approved',
            'actor_user_id' => $this->kepalaPusat->id,
        ]);

        $this->assertTrue(
            AuditTrail::query()->where('event', 'audit.assignment.approved')
                ->where('auditable_id', $assignment->id)
                ->exists()
        );

        $this->actingAs($this->auditor)
            ->get('/audit/calendar?year=2026&month=11')
            ->assertOk()
            ->assertSee('TUGAS-FA-001');
    }

    /** @test */
    public function rejection_blocks_publication_and_stores_reason(): void
    {
        $assignment = $this->makeAssignment('proposed', $this->auditor, $this->clinicA);

        $this->actingAs($this->kepalaPusat)
            ->post('/audit/assignments/'.$assignment->id.'/reject', [
                'reason' => 'Tanggal bentrok dengan program lain.',
            ])
            ->assertRedirect();

        $assignment->refresh();
        $this->assertSame('rejected', $assignment->status);
        $this->assertSame('Tanggal bentrok dengan program lain.', $assignment->rejection_reason);
        $this->assertSame($this->kepalaPusat->id, $assignment->rejected_by);
        $this->assertFalse($assignment->isPublished());

        $this->actingAs($this->auditor)
            ->get('/audit/calendar?year=2026&month=11')
            ->assertOk()
            ->assertDontSee('TUGAS-FA-001');

        $this->actingAs($this->kasatpel)
            ->post('/audit/assignments/'.$assignment->id.'/submit')
            ->assertRedirect();

        $this->assertSame('proposed', $assignment->fresh()->status);
        $this->assertNull($assignment->fresh()->rejection_reason);
    }

    /** @test */
    public function unpublished_schedule_is_hidden_from_assigned_auditor_and_auditi(): void
    {
        $assignment = $this->makeAssignment('proposed', $this->auditor, $this->clinicA);

        $this->actingAs($this->auditor)
            ->get('/audit/assignments')
            ->assertOk()
            ->assertDontSee('TUGAS-FA-001');

        $this->actingAs($this->auditor)
            ->get('/audit/assignments/'.$assignment->id)
            ->assertForbidden();

        $this->actingAs($this->auditi)
            ->get('/audit/assignments/'.$assignment->id)
            ->assertForbidden();

        $this->actingAs($this->auditor)
            ->get('/audit/calendar?year=2026&month=11')
            ->assertDontSee('TUGAS-FA-001');
    }

    /** @test */
    public function kasatpel_cannot_publish_or_skip_final_approval(): void
    {
        $assignment = $this->makeAssignment('proposed', $this->auditor, $this->clinicA);

        $this->actingAs($this->kasatpel)
            ->post('/audit/assignments/'.$assignment->id.'/approve')
            ->assertForbidden();

        $this->actingAs($this->kasatpel)
            ->post('/audit/assignments/'.$assignment->id.'/activate')
            ->assertForbidden();

        $this->assertSame('proposed', $assignment->fresh()->status);
    }

    /** @test */
    public function published_schedule_is_visible_only_to_involved_users(): void
    {
        $mine = $this->makeAssignment('approved', $this->auditor, $this->clinicA, 'TUGAS-FA-MINE');
        $theirs = $this->makeAssignment('approved', $this->otherAuditor, $this->clinicB, 'TUGAS-FA-THEIRS');

        $this->actingAs($this->auditor)
            ->get('/audit/assignments')
            ->assertOk()
            ->assertSee('TUGAS-FA-MINE')
            ->assertDontSee('TUGAS-FA-THEIRS');

        $this->actingAs($this->auditor)
            ->get('/audit/assignments/'.$theirs->id)
            ->assertForbidden();

        $this->actingAs($this->auditi)
            ->get('/audit/assignments/'.$mine->id)
            ->assertOk()
            ->assertSee('TUGAS-FA-MINE');

        $this->actingAs($this->auditi)
            ->get('/audit/assignments/'.$theirs->id)
            ->assertForbidden();

        $this->actingAs($this->kepalaPusat)
            ->get('/audit/assignments/'.$theirs->id)
            ->assertOk();
    }
}
