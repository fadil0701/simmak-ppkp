<?php

namespace App\Http\Controllers;

use App\Models\RegulatorySource;
use App\Services\AuditStandardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Register sumber regulasi (master data hukum/acuan audit).
 *
 * Status verifikasi sengaja dibedakan dari penyuntingan biasa: data baru selalu
 * masuk sebagai UNVERIFIED (lihat AuditStandardService::createRegulatorySource)
 * dan hanya bisa naik status lewat aksi verifikasi yang mewajibkan catatan bukti.
 */
class RegulatorySourceController extends Controller
{
    public const STATUSES = [
        'UNVERIFIED' => 'Belum Diverifikasi',
        'VERIFIED_IN_FORCE' => 'Terverifikasi & Berlaku',
        'AMENDED' => 'Telah Diubah',
        'REPLACED' => 'Diganti Dokumen Lain',
        'REVOKED' => 'Dicabut',
    ];

    public function __construct(private readonly AuditStandardService $auditStandardService) {}

    public static function statusLabel(?string $status): string
    {
        $normalized = strtoupper(trim((string) $status));

        return self::STATUSES[$normalized] ?? ($normalized !== '' ? $normalized : '—');
    }

    public static function statusBadgeClass(?string $status): string
    {
        return match (strtoupper(trim((string) $status))) {
            'VERIFIED_IN_FORCE' => 'border-sim-success bg-sim-success-bg text-sim-success',
            'AMENDED', 'REPLACED' => 'border-sim-warning bg-sim-warning-bg text-sim-warning',
            'REVOKED' => 'border-sim-danger bg-sim-danger-bg text-sim-danger',
            default => 'border-sim-neutral bg-sim-neutral-bg text-sim-neutral',
        };
    }

    public function index(Request $request): View
    {
        $query = RegulatorySource::query()
            ->withCount('standards')
            ->orderBy('document_number');

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where(function ($q) use ($term) {
                $q->where('document_number', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhere('issuer', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            $status = strtoupper($request->string('status')->toString());
            // UPPER() dipakai agar data lama berhuruf kecil tetap tersaring
            // dan tetap kompatibel MySQL maupun PostgreSQL.
            $query->whereRaw('UPPER(verification_status) = ?', [$status]);
        }

        $sources = $query->paginate(15)->withQueryString();

        return view('audit.regulatory.index', compact('sources'));
    }

    public function create(): View
    {
        return view('audit.regulatory.form', [
            'source' => null,
            'statuses' => self::STATUSES,
        ]);
    }

    public function edit(Request $request, RegulatorySource $source): View
    {
        return view('audit.regulatory.form', [
            'source' => $source,
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $this->auditStandardService->createRegulatorySource($validated, $request->user());

        return redirect()->route('audit.regulatory.index')
            ->with('status', 'Sumber regulasi tersimpan dengan status Belum Diverifikasi. Verifikasi dilakukan terpisah lewat aksi Verifikasi.');
    }

    public function update(Request $request, RegulatorySource $source): RedirectResponse
    {
        $validated = $request->validate($this->rules($source->id));

        // Hanya metadata dokumen; status verifikasi tidak ikut tersunting di sini.
        $source->update($validated);

        return redirect()->route('audit.regulatory.index')
            ->with('status', 'Sumber regulasi berhasil diperbarui.');
    }

    public function destroy(Request $request, RegulatorySource $source): RedirectResponse
    {
        if ($source->standards()->exists()) {
            return back()->with('error', 'Sumber regulasi tidak dapat dihapus karena masih dipakai standar audit.');
        }

        $source->delete();

        return redirect()->route('audit.regulatory.index')
            ->with('status', 'Sumber regulasi berhasil dihapus.');
    }

    public function verify(Request $request, RegulatorySource $source): RedirectResponse
    {
        $validated = $request->validate([
            'verification_status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'verification_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $status = strtoupper($validated['verification_status']);
        $notes = trim((string) ($validated['verification_notes'] ?? ''));

        // Status selain UNVERIFIED adalah klaim kepatuhan → wajib bukti tertulis.
        if ($status !== 'UNVERIFIED' && $notes === '') {
            throw ValidationException::withMessages([
                'verification_notes' => 'Catatan bukti verifikasi wajib diisi untuk status selain Belum Diverifikasi.',
            ]);
        }

        $this->auditStandardService->verifyRegulatorySource(
            $source,
            [
                'verification_status' => $status,
                'verification_notes' => $notes !== '' ? $notes : null,
            ],
            $request->user(),
        );

        return back()->with('status', 'Status verifikasi sumber regulasi diperbarui.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?string $ignoreId = null): array
    {
        $unique = Rule::unique('regulatory_sources', 'document_number');

        if ($ignoreId !== null) {
            $unique = $unique->ignore($ignoreId);
        }

        return [
            'document_number' => ['required', 'string', 'max:120', $unique],
            'title' => ['required', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'verification_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
