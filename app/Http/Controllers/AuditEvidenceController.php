<?php

namespace App\Http\Controllers;

use App\Models\AuditExecution;
use App\Models\Clinic;
use App\Models\Evidence;
use App\Services\AuditEvidenceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AuditEvidenceController extends Controller
{
    public function __construct(private AuditEvidenceService $evidenceService) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $evidences = $this->evidenceService->getEvidenceForUser($user);

        // Berkas bisa hilang dari disk (volume storage tidak ter-mount, purge, dsb).
        // Tandai lebih awal supaya UI tidak menawarkan unduhan yang pasti gagal.
        $unavailableIds = $evidences->getCollection()
            ->reject(fn (Evidence $evidence) => $this->evidenceService->fileExists($evidence))
            ->pluck('id')
            ->all();

        return view('audit.evidence.index', compact('evidences', 'unavailableIds'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        // Super admin: seluruh eksekusi lintas organisasi, termasuk yang sudah
        // dikirim/difinalisasi (unggah bukti pasca-audit tetap tercatat).
        $executions = AuditExecution::query()
            ->when(! $user->hasUnrestrictedAccess(), fn ($query) => $query
                ->where('organization_id', $user->organization_id)
                ->where('status', 'in_progress'))
            ->with('clinic')
            ->get();

        $clinics = Clinic::forUserClinics($user)
            ->auditable()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('audit.evidence.create', compact('executions', 'clinics'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Evidence::class);

        $clinicIds = $request->user()->clinicScopeIds();

        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv,txt'],
            'clinic_id' => ['nullable', 'uuid', Rule::in($clinicIds->all())],
            'audit_execution_id' => ['nullable', 'uuid'],
            'evidence_type' => ['nullable', 'string', 'max:80'],
            'access_classification' => ['nullable', 'string', 'max:40', Rule::in(['internal', 'confidential', 'restricted'])],
            'description' => ['nullable', 'string'],
        ]);

        $this->evidenceService->store($request->file('file'), $request->user(), $request->only([
            'clinic_id', 'audit_execution_id', 'evidence_type', 'access_classification', 'description',
        ]));

        return redirect()->route('audit.evidence.index')->with('status', 'Bukti berhasil diunggah.');
    }

    public function show(Request $request, Evidence $evidence): View
    {
        $this->authorize('view', $evidence);

        $evidence->load(['clinic', 'execution', 'uploader']);

        $fileAvailable = $this->evidenceService->fileExists($evidence);

        return view('audit.evidence.show', compact('evidence', 'fileAvailable'));
    }

    public function download(Request $request, Evidence $evidence): StreamedResponse|RedirectResponse
    {
        $this->authorize('download', $evidence);

        if (! $this->evidenceService->fileExists($evidence)) {
            return $this->storageMiss($evidence, 'Berkas tidak ditemukan pada disk penyimpanan.');
        }

        try {
            return Storage::disk($evidence->storage_disk)->download(
                $this->evidenceService->download($evidence),
                $evidence->original_filename
            );
        } catch (Throwable $e) {
            // Metadata tidak terbaca, permission ditolak, atau berkas terhapus
            // tepat setelah pemeriksaan. Jangan sampai pengguna melihat 500.
            return $this->storageMiss($evidence, $e->getMessage(), $e);
        }
    }

    /**
     * Berkas hilang tidak boleh menjadi 500 — catat ke log dan kembalikan
     * pengguna ke halaman asal dengan pesan yang bisa ditindaklanjuti.
     */
    private function storageMiss(Evidence $evidence, string $reason, ?Throwable $error = null): RedirectResponse
    {
        Log::warning('Bukti audit tidak dapat diunduh: berkas tidak tersedia di penyimpanan.', [
            'evidence_id' => $evidence->id,
            'storage_disk' => $evidence->storage_disk,
            'storage_path' => $evidence->storage_path,
            'original_filename' => $evidence->original_filename,
            'reason' => $reason,
            'exception' => $error?->getMessage(),
        ]);

        return redirect()
            ->back(fallback: route('audit.evidence.index'))
            ->with('error', 'Berkas "'.$evidence->original_filename.'" tidak tersedia di penyimpanan. Unggah ulang bukti ini agar dapat diunduh dan diaudit kembali.');
    }

    public function destroy(Request $request, Evidence $evidence): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($evidence->organization_id), 403);

        try {
            $this->evidenceService->delete($evidence, $request->user());
        } catch (AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('audit.evidence.index')->with('status', 'Bukti berhasil dihapus.');
    }
}
