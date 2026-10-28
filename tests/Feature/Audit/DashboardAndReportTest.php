<?php

namespace Tests\Feature\Audit;

use App\Models\AuditFinding;
use App\Models\CapaCase;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditDashboardService;
use App\Support\AuditWorkScope;
use App\Support\SpreadsheetCell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardAndReportTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected Clinic $clinicA;

    protected Clinic $clinicB;

    protected User $auditor;

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
            'is_main_clinic' => false,
            'is_auditable' => true,
            'is_active' => true,
        ]);

        $this->clinicB = Clinic::create([
            'organization_id' => $this->org->id,
            'code' => 'KB',
            'name' => 'Klinik B',
            'clinic_type' => 'pratama',
            'is_main_clinic' => false,
            'is_auditable' => true,
            'is_active' => true,
        ]);

        foreach ([
            'audit.dashboard.view', 'dashboard.view', 'audit.view',
            'audit.finding.view', 'audit.finding.create',
            'capa.view', 'audit.action.view',
            'audit.execution.view', 'audit.assignment.view',
            'audit.report.view', 'audit.report.export',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('guard_name', 'web')->pluck('name'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->auditor = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Auditor A',
            'email' => 'auditor-a@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
            'default_clinic_id' => $this->clinicA->id,
        ]);
        $this->auditor->clinics()->attach($this->clinicA->id, ['is_default' => true]);
        $this->auditor->assignRole('auditor');
    }

    protected function makeFinding(Clinic $clinic, string $code, string $status = 'in_capa', string $severity = 'major'): AuditFinding
    {
        return AuditFinding::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $clinic->id,
            'finding_code' => $code,
            'title' => 'Temuan '.$code,
            'severity' => $severity,
            'status' => $status,
            'identified_at' => now(),
            'created_by' => $this->auditor->id,
            'updated_by' => $this->auditor->id,
        ]);
    }

    /** @test */
    public function dashboard_counts_match_scoped_queries(): void
    {
        $this->makeFinding($this->clinicA, 'TMN-A-1', 'in_capa', 'critical');
        $this->makeFinding($this->clinicA, 'TMN-A-2', 'closed', 'minor');
        $this->makeFinding($this->clinicB, 'TMN-B-1', 'in_capa', 'critical');

        CapaCase::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinicA->id,
            'case_number' => 'CAPA-LATE-1',
            'source_type' => 'audit_finding',
            'priority' => 'high',
            'problem_statement' => 'Terlambat',
            'status' => 'action',
            'owner_id' => $this->auditor->id,
            'target_close_date' => now()->subDay()->toDateString(),
            'created_by' => $this->auditor->id,
            'updated_by' => $this->auditor->id,
        ]);

        $stats = app(AuditDashboardService::class)->stats($this->auditor);

        $this->assertSame(1, $stats['findings_open']);
        $this->assertSame(1, $stats['findings_critical']);
        $this->assertSame(1, $stats['capa_overdue']);
        $this->assertSame(
            AuditWorkScope::findings(AuditFinding::query(), $this->auditor)->whereIn('status', AuditFinding::OPEN_STATUSES)->count(),
            $stats['findings_open']
        );

        $charts = app(AuditDashboardService::class)->chartSeries($this->auditor);
        $this->assertFalse($charts['findings']['empty']);
        $this->assertSame(1, $charts['findings']['values'][0]);

        $page = $this->actingAs($this->auditor)->get('/dashboard');
        $page->assertOk();
        $page->assertSee('Eksekusi menurut status');
        $page->assertSee('Temuan terbuka menurut tingkat risiko');
    }

    /** @test */
    public function findings_index_full_text_search_matches_title(): void
    {
        $this->makeFinding($this->clinicA, 'TMN-SRC-1', 'in_capa');
        AuditFinding::where('finding_code', 'TMN-SRC-1')->update([
            'title' => 'Kebijakan visum tidak tersedia',
            'description' => 'Tidak ada SK penetapan visi misi klinik',
        ]);
        $this->makeFinding($this->clinicA, 'TMN-SRC-2', 'in_capa');
        AuditFinding::where('finding_code', 'TMN-SRC-2')->update(['title' => 'Struktur organisasi lengkap']);

        $response = $this->actingAs($this->auditor)->get('/findings?q=visum');

        $response->assertOk();
        $response->assertSee('TMN-SRC-1');
        $response->assertDontSee('TMN-SRC-2');
    }

    /** @test */
    public function findings_csv_export_matches_open_filter_and_sanitizes_formulas(): void
    {
        $this->makeFinding($this->clinicA, 'TMN-CSV-1', 'in_capa');
        $this->makeFinding($this->clinicA, 'TMN-CSV-2', 'closed');
        AuditFinding::where('finding_code', 'TMN-CSV-1')->update(['title' => '=CMD()']);

        $response = $this->actingAs($this->auditor)->get('/findings/export?open=1');
        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('TMN-CSV-1', $csv);
        $this->assertStringNotContainsString('TMN-CSV-2', $csv);
        $this->assertStringContainsString("'=CMD()", $csv);
    }

    /** @test */
    public function spreadsheet_cell_prefixes_formula_characters(): void
    {
        $this->assertSame("'=1+1", SpreadsheetCell::sanitize('=1+1'));
        $this->assertSame("'+hid", SpreadsheetCell::sanitize('+hid'));
        $this->assertSame('aman', SpreadsheetCell::sanitize('aman'));
    }
}
