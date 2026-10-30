<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\ClinicalAudit;
use App\Models\ClinicalAuditItem;
use App\Models\Evidence;
use App\Models\User;
use App\Services\AuditEvidenceService;
use App\Services\AuditTrailService;
use App\Services\ClinicalAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Audit Klinis — penilaian kesesuaian layanan klinis terhadap standar/kriteria
 * yang sudah terdaftar di sistem (audit_standards / sumber regulasi).
 *
 * Temuan yang lahir dari audit klinis disimpan pada modul Temuan Audit bersama
 * (audit_findings.clinical_audit_id) dan ditindaklanjuti melalui modul Tindak
 * Lanjut (CAPA) yang sudah ada — tidak ada alur temuan/CAPA terpisah.
 */
class ClinicalAuditController extends Controller
{
    public function __construct(
        private AuditEvidenceService $evidenceService,
        private ClinicalAuditService $clinicalAudits,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $audits = $this->filteredQuery($request)
            ->with(['clinic', 'auditor', 'pic', 'standard'])
            ->withCount(['items', 'findings'])
            ->paginate(15)
            ->withQueryString();

        $base = $this->visibleQuery($user);

        $stats = [
            'total' => (clone $base)->count(),
            'draft' => (clone $base)->where('status', 'draft')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'closed' => (clone $base)->where('status', 'closed')->count(),
            'non_conform' => ClinicalAuditItem::query()
                ->whereIn('clinical_audit_id', (clone $base)->select('id'))
                ->where('result', 'non_conform')
                ->count(),
        ];

        $clinics = Clinic::forUserClinics($user)->auditable()->where('is_active', true)->orderBy('name')->get();

        return view('audit.clinical.index', compact('audits', 'stats', 'clinics'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ClinicalAudit::class);

        $user = $request->user();

        return view('audit.clinical.create', $this->formData($user, null));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ClinicalAudit::class);

        $user = $request->user();
        $validated = $this->validateAudit($request, $user->organization_id, creating: true);

        $audit = $this->clinicalAudits->create($user, $validated);

        (new AuditTrailService)->log(
            'audit.clinical.created',
            $audit,
            null,
            [
                'audit_number' => $audit->audit_number,
                'title' => $audit->title,
                'clinic_id' => $audit->clinic_id,
                'audit_assignment_id' => $audit->audit_assignment_id,
            ],
            ['actor' => $user->name]
        );

        return redirect()->route('audit.clinical.show', $audit)->with('status', 'Audit klinis berhasil dibuat.');
    }

    public function show(Request $request, ClinicalAudit $clinicalAudit): View
    {
        $this->authorize('view', $clinicalAudit);

        $clinicalAudit->load([
            'clinic', 'auditor', 'pic', 'standard.sourceDocument', 'creator', 'assignment.leadAuditor',
            'initialEvidences.uploader',
            'findings.capaCase', 'findings.owner',
        ]);

        return view('audit.clinical.show', [
            'audit' => $clinicalAudit,
        ]);
    }

    public function edit(Request $request, ClinicalAudit $clinicalAudit): View
    {
        $this->authorize('update', $clinicalAudit);

        $clinicalAudit->loadMissing(['assignment.clinic', 'assignment.leadAuditor', 'clinic', 'auditor']);

        return view('audit.clinical.edit', $this->formData($request->user(), $clinicalAudit));
    }

    public function update(Request $request, ClinicalAudit $clinicalAudit): RedirectResponse
    {
        $this->authorize('update', $clinicalAudit);

        $validated = $this->validateAudit($request, $clinicalAudit->organization_id, creating: false);

        $this->clinicalAudits->update($clinicalAudit, $request->user(), $validated);

        (new AuditTrailService)->log(
            'audit.clinical.updated',
            $clinicalAudit,
            null,
            ['audit_number' => $clinicalAudit->audit_number, 'title' => $clinicalAudit->title],
            ['actor' => $request->user()->name]
        );

        return redirect()->route('audit.clinical.show', $clinicalAudit)->with('status', 'Audit klinis berhasil diperbarui.');
    }

    public function destroy(Request $request, ClinicalAudit $clinicalAudit): RedirectResponse
    {
        $this->authorize('delete', $clinicalAudit);

        if ($clinicalAudit->findings()->exists()) {
            return back()->with('error', 'Audit klinis tidak dapat dihapus karena sudah memiliki temuan audit.');
        }

        if ($clinicalAudit->initialEvidences()->exists()) {
            return back()->with('error', 'Audit klinis tidak dapat dihapus karena masih memiliki dokumentasi pendukung.');
        }

        (new AuditTrailService)->log(
            'audit.clinical.deleted',
            $clinicalAudit,
            ['audit_number' => $clinicalAudit->audit_number, 'title' => $clinicalAudit->title],
            null,
            ['actor' => $request->user()->name]
        );

        $clinicalAudit->delete();

        return redirect()->route('audit.clinical.index')->with('status', 'Audit klinis berhasil dihapus.');
    }

    /**
     * Perubahan status audit klinis (draf → berjalan → selesai → ditutup).
     */
    public function updateStatus(Request $request, ClinicalAudit $clinicalAudit): RedirectResponse
    {
        $this->authorize('update', $clinicalAudit);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['draft', 'in_progress', 'completed', 'closed'])],
        ]);

        $old = $clinicalAudit->status;
        $clinicalAudit->update([
            'status' => $validated['status'],
            'updated_by' => $request->user()->id,
        ]);

        (new AuditTrailService)->log(
            'audit.clinical.status_changed',
            $clinicalAudit,
            ['status' => $old],
            ['status' => $validated['status']],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Status audit klinis diperbarui.');
    }

    /**
     * Tambah elemen penilaian (indikator/kriteria + hasil) pada audit klinis.
     */
    public function storeItem(Request $request, ClinicalAudit $clinicalAudit): RedirectResponse
    {
        $this->authorize('update', $clinicalAudit);

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:60'],
            'element' => ['required', 'string'],
            'criteria' => ['nullable', 'string'],
            'indicator' => ['nullable', 'string', 'max:255'],
            'objective_evidence' => ['nullable', 'string'],
            'result' => ['required', 'string', Rule::in(ClinicalAuditItem::RESULTS)],
            'score' => ['nullable', 'integer', Rule::in(ClinicalAuditItem::SCORES)],
            'finding_note' => ['nullable', 'string'],
        ]);

        $item = $clinicalAudit->items()->create(array_merge($validated, [
            'organization_id' => $clinicalAudit->organization_id,
            'sort_order' => (int) $clinicalAudit->items()->max('sort_order') + 1,
        ]));

        $this->syncScore($clinicalAudit);

        (new AuditTrailService)->log(
            'audit.clinical.item_saved',
            $clinicalAudit,
            null,
            ['item_id' => $item->id, 'code' => $item->code, 'result' => $item->result],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Elemen penilaian ditambahkan.');
    }

    public function destroyItem(Request $request, ClinicalAudit $clinicalAudit, ClinicalAuditItem $item): RedirectResponse
    {
        $this->authorize('update', $clinicalAudit);

        abort_unless($item->clinical_audit_id === $clinicalAudit->id, 404);

        $item->delete();
        $this->syncScore($clinicalAudit);

        return back()->with('status', 'Elemen penilaian dihapus.');
    }

    /**
     * Dokumentasi pendukung audit klinis memakai tabel evidence yang sama
     * (muncul pula di menu Arsip Bukti dengan konteks audit klinis).
     */
    public function storeEvidence(Request $request, ClinicalAudit $clinicalAudit): RedirectResponse
    {
        $this->authorize('update', $clinicalAudit);

        abort_unless(
            $request->user()->can('audit.evidence.upload') || $request->user()->can('audit.clinical.manage'),
            403,
            'Tidak berwenang mengunggah bukti audit.'
        );

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv,txt'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $evidence = $this->evidenceService->store($request->file('file'), $request->user(), [
            'clinic_id' => $clinicalAudit->clinic_id,
            'clinical_audit_id' => $clinicalAudit->id,
            'evidence_type' => 'clinical_audit',
            'description' => $validated['description'] ?? 'Dokumentasi audit klinis '.$clinicalAudit->audit_number,
        ]);

        (new AuditTrailService)->log(
            'audit.clinical.evidence_uploaded',
            $clinicalAudit,
            null,
            ['evidence_id' => $evidence->id, 'original_filename' => $evidence->original_filename],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Dokumentasi pendukung berhasil diunggah.');
    }

    public function destroyEvidence(Request $request, ClinicalAudit $clinicalAudit, Evidence $evidence): RedirectResponse
    {
        $this->authorize('delete', $evidence);

        abort_unless($evidence->clinical_audit_id === $clinicalAudit->id, 404);

        $this->evidenceService->delete($evidence, $request->user());

        return back()->with('status', 'Dokumentasi pendukung dihapus.');
    }

    /**
     * Rata-rata skor elemen (skala 0/5/10) dipetakan ke persentase kesesuaian.
     */
    private function syncScore(ClinicalAudit $audit): void
    {
        $scored = $audit->items()->whereNotNull('score')->pluck('score');

        if ($scored->isEmpty()) {
            $audit->updateQuietly(['conformity_score' => null]);

            return;
        }

        $audit->updateQuietly([
            'conformity_score' => (int) round(((float) $scored->avg() / 10) * 100),
        ]);
    }

    private function filteredQuery(Request $request)
    {
        $query = $this->visibleQuery($request->user());

        // Pencarian memakai LIKE agar aman di MySQL (tanpa FULLTEXT index khusus)
        // maupun PostgreSQL.
        $term = trim($request->string('q')->toString());

        if ($term !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';

            $query->where(function ($inner) use ($like) {
                $inner->where('audit_number', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhere('scope', 'like', $like)
                    ->orWhere('criteria', 'like', $like)
                    ->orWhere('standard_reference', 'like', $like);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', $request->string('clinic_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('audit_date', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('audit_date', '<=', $request->date('to'));
        }

        return $query->orderByDesc('audit_date')->orderByDesc('created_at');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAudit(Request $request, string $orgId, bool $creating): array
    {
        $hasNonconformity = $request->boolean('has_nonconformity');

        $rules = [
            'medical_record_ref' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9.\\/\\-]{1,80}$/'],
            'has_nonconformity' => ['nullable', 'boolean'],
            'nonconformity_notes' => [$hasNonconformity ? 'required' : 'nullable', 'string'],
            'problem_analysis' => [$hasNonconformity ? 'required' : 'nullable', 'string'],
        ];

        if ($creating) {
            $rules['audit_assignment_id'] = ['required', 'uuid'];
        }

        $validated = $request->validate($rules);
        $validated['has_nonconformity'] = $hasNonconformity;

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(User $user, ?ClinicalAudit $audit): array
    {
        return [
            'audit' => $audit,
            'assignments' => $this->clinicalAudits->eligibleAssignments($user),
            'suggestedNumber' => $audit?->audit_number ?? $this->clinicalAudits->peekNumber($user->organization_id),
        ];
    }

    private function visibleQuery(User $user)
    {
        $query = ClinicalAudit::query();

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->hasCrossClinicAccess()) {
            return $query->where('organization_id', $user->organization_id);
        }

        $clinicIds = $user->clinicScopeIds()->all() ?: ['00000000-0000-0000-0000-000000000000'];

        return $query->where('organization_id', $user->organization_id)
            ->where(function ($inner) use ($user, $clinicIds) {
                $inner->whereIn('clinic_id', $clinicIds)
                    ->orWhere('auditor_id', $user->id)
                    ->orWhereHas('assignment.teamMembers', function ($members) use ($user) {
                        $members->where('user_id', $user->id)->where('active', true);
                    });
            });
    }

}
