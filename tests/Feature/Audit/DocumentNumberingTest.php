<?php

namespace Tests\Feature\Audit;

use App\Models\AuditProgram;
use App\Models\Clinic;
use App\Models\DocumentNumberSequence;
use App\Models\Organization;
use App\Models\User;
use App\Services\DocumentNumberService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentNumberingTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private User $user;

    private DocumentNumberService $numbers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'code' => 'PPKP-DKI',
            'name' => 'PPKP DKI',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $clinic = Clinic::create([
            'organization_id' => $this->org->id,
            'code' => 'KP01',
            'name' => 'Klinik Pratama',
            'clinic_type' => 'pratama',
            'is_main_clinic' => false,
            'is_active' => true,
        ]);

        foreach (['audit.program.view', 'audit.program.create', 'audit.program.update'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('guard_name', 'web')->pluck('name'));

        $this->user = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Admin Nomor',
            'email' => 'nomor@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
            'default_clinic_id' => $clinic->id,
        ]);
        $this->user->clinics()->attach($clinic->id, ['is_default' => true]);
        $this->user->assignRole($role);

        $this->numbers = app(DocumentNumberService::class);
    }

    /** @test */
    public function sequential_allocate_never_repeats_and_skips_existing_numbers(): void
    {
        AuditProgram::create([
            'organization_id' => $this->org->id,
            'program_code' => 'PROG-'.now()->format('Y').'-0007',
            'name' => 'Program lama',
            'status' => 'draft',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $first = $this->numbers->allocate($this->org->id, DocumentNumberService::PROGRAM);
        $second = $this->numbers->allocate($this->org->id, DocumentNumberService::PROGRAM);

        $this->assertSame('PROG-'.now()->format('Y').'-0008', $first);
        $this->assertSame('PROG-'.now()->format('Y').'-0009', $second);
        $this->assertNotSame($first, $second);

        $row = DocumentNumberSequence::query()
            ->where('organization_id', $this->org->id)
            ->where('series', 'PROG-'.now()->format('Y'))
            ->firstOrFail();
        $this->assertSame(9, $row->last_value);
    }

    /** @test */
    public function program_store_ignores_client_supplied_code_and_issues_unique_numbers(): void
    {
        $this->actingAs($this->user)->post('/audit/programs', [
            'name' => 'Program A',
            'program_code' => 'PROG-HACK-0001',
        ])->assertRedirect();

        $this->actingAs($this->user)->post('/audit/programs', [
            'name' => 'Program B',
            'program_code' => 'PROG-HACK-0001',
        ])->assertRedirect();

        $codes = AuditProgram::query()->orderBy('created_at')->pluck('program_code')->all();
        $this->assertCount(2, $codes);
        $this->assertNotSame('PROG-HACK-0001', $codes[0]);
        $this->assertNotSame('PROG-HACK-0001', $codes[1]);
        $this->assertNotSame($codes[0], $codes[1]);
        $this->assertMatchesRegularExpression('/^PROG-\d{4}-\d{4}$/', $codes[0]);
        $this->assertMatchesRegularExpression('/^PROG-\d{4}-\d{4}$/', $codes[1]);
    }

    /** @test */
    public function sequence_series_is_unique_per_organization(): void
    {
        DocumentNumberSequence::create([
            'organization_id' => $this->org->id,
            'series' => 'AKL-'.now()->format('Y'),
            'last_value' => 1,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        DocumentNumberSequence::create([
            'organization_id' => $this->org->id,
            'series' => 'AKL-'.now()->format('Y'),
            'last_value' => 2,
        ]);
    }

    /** @test */
    public function deleted_numbers_are_not_reused(): void
    {
        $year = now()->format('Y');
        $program = AuditProgram::create([
            'organization_id' => $this->org->id,
            'program_code' => 'PROG-'.$year.'-0005',
            'name' => 'Akan dihapus',
            'status' => 'draft',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
        $program->delete();

        $next = $this->numbers->allocate($this->org->id, DocumentNumberService::PROGRAM);
        $this->assertSame('PROG-'.$year.'-0006', $next);
    }
}
