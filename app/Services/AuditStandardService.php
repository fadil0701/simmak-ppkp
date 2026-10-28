<?php

namespace App\Services;

use App\Models\AuditStandard;
use App\Models\InstrumentVersion;
use App\Models\RegulatorySource;
use App\Models\StandardCriterion;
use App\Models\StandardSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class AuditStandardService
{
    public function createRegulatorySource(array $data, User $user): RegulatorySource
    {
        return RegulatorySource::create([
            'document_number' => $data['document_number'],
            'title' => $data['title'],
            'issuer' => $data['issuer'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'published_at' => $data['published_at'] ?? null,
            'effective_from' => $data['effective_from'] ?? null,
            'effective_until' => $data['effective_until'] ?? null,
            'verification_status' => 'UNVERIFIED',
            'verified_at' => null,
            'verified_by' => null,
            'verification_notes' => $data['verification_notes'] ?? null,
        ]);
    }

    public function verifyRegulatorySource(RegulatorySource $source, array $data, User $user): RegulatorySource
    {
        $status = strtoupper(trim((string) ($data['verification_status'] ?? 'VERIFIED_IN_FORCE')));
        $unverified = $status === 'UNVERIFIED';

        // Kembali ke UNVERIFIED berarti mencabut klaim: jejak verifikasi dibersihkan.
        $source->update([
            'verification_status' => $status,
            'verified_at' => $unverified ? null : now(),
            'verified_by' => $unverified ? null : $user->id,
            'verification_notes' => $data['verification_notes'] ?? null,
        ]);

        return $source;
    }

    /** Status master standar yang sah. */
    public const STANDARD_STATUSES = ['draft', 'active', 'inactive', 'archived'];

    public function createStandard(array $data, User $user): AuditStandard
    {
        $status = $data['status'] ?? 'draft';

        if (! in_array($status, self::STANDARD_STATUSES, true)) {
            $status = 'draft';
        }

        return AuditStandard::create([
            'organization_id' => $user->organization_id,
            'source_document_id' => $data['source_document_id'] ?? null,
            'code' => $data['code'],
            'title' => $data['title'],
            'status' => $status,
        ]);
    }

    /**
     * Mengubah status master standar (draft → active → inactive/archived).
     */
    public function setStandardStatus(AuditStandard $standard, string $status): AuditStandard
    {
        if (! in_array($status, self::STANDARD_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Status standar tidak dikenal: '.$status.'.',
            ]);
        }

        // Standar hanya boleh diaktifkan bila sudah punya versi instrumen,
        // agar tidak ada standar "aktif" yang isinya kosong.
        if ($status === 'active' && $standard->instrumentVersions()->count() === 0) {
            throw ValidationException::withMessages([
                'status' => 'Standar belum memiliki versi instrumen. Tambahkan minimal satu versi instrumen sebelum mengaktifkan standar.',
            ]);
        }

        $standard->update(['status' => $status]);

        return $standard->refresh();
    }

    public function createInstrumentVersion(array $data, User $user): InstrumentVersion
    {
        $standard = AuditStandard::findOrFail($data['audit_standard_id']);

        return InstrumentVersion::create([
            'audit_standard_id' => $standard->id,
            'version_label' => $data['version_label'],
            'status' => 'draft',
            'effective_from' => $data['effective_from'] ?? null,
            'effective_until' => $data['effective_until'] ?? null,
            'source_snapshot' => $data['source_snapshot'] ?? null,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function submitForReview(InstrumentVersion $version, User $user): void
    {
        // Super admin boleh mengajukan versi dari status mana pun.
        if ($version->status !== 'draft' && ! $user->hasUnrestrictedAccess()) {
            throw new ModelNotFoundException('Hanya versi draft yang dapat diajukan tinjauan.');
        }
        $version->update(['status' => 'review']);
    }

    public function approveVersion(InstrumentVersion $version, User $user): void
    {
        // Super admin boleh mengesahkan versi dari status mana pun.
        if ($version->status !== 'review' && ! $user->hasUnrestrictedAccess()) {
            throw new ModelNotFoundException('Hanya versi pada status tinjauan yang dapat disahkan.');
        }
        $version->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }

    public function activateVersion(InstrumentVersion $version, User $user): void
    {
        // Super admin boleh mengaktifkan versi dari status mana pun.
        if ($version->status !== 'approved' && ! $user->hasUnrestrictedAccess()) {
            throw new ModelNotFoundException('Hanya versi yang sudah disahkan yang dapat diaktifkan.');
        }

        // Nonaktifkan versi aktif lain pada standar yang sama
        InstrumentVersion::where('audit_standard_id', $version->audit_standard_id)
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        $version->update(['status' => 'active']);

        // Versi instrumen aktif berarti standar induk sedang dipakai audit,
        // jadi status master standar otomatis menjadi aktif. Standar yang
        // sengaja diarsipkan tidak dibangkitkan kembali oleh efek samping ini.
        $standard = $version->standard;

        if ($standard && $standard->status !== 'archived') {
            $standard->update(['status' => 'active']);
        }
    }

    public function createSection(array $data, User $user): StandardSection
    {
        $version = InstrumentVersion::findOrFail($data['instrument_version_id']);

        return StandardSection::create([
            'instrument_version_id' => $version->id,
            'parent_id' => $data['parent_id'] ?? null,
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function updateSection(StandardSection $section, array $data, User $user): StandardSection
    {
        $section->update([
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => array_key_exists('description', $data) ? $data['description'] : $section->description,
            'sort_order' => $data['sort_order'] ?? $section->sort_order,
        ]);

        return $section->refresh();
    }

    public function createCriterion(array $data, User $user): StandardCriterion
    {
        $section = StandardSection::with('instrumentVersion')->findOrFail($data['standard_section_id']);

        return StandardCriterion::create([
            'standard_section_id' => $section->id,
            'code' => $data['code'],
            'title' => $data['title'],
            'requirement_text' => $data['requirement_text'] ?? null,
            'assessment_guidance' => $data['assessment_guidance'] ?? null,
            'evidence_guidance' => $data['evidence_guidance'] ?? null,
            'assessment_type' => $data['assessment_type'] ?? null,
            'scoring_rule' => $data['scoring_rule'] ?? null,
            'max_score' => $data['max_score'] ?? 10,
            'source_reference' => $data['source_reference'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'active' => $data['active'] ?? true,
        ]);
    }

    public function updateCriterion(StandardCriterion $criterion, array $data, User $user): StandardCriterion
    {
        $criterion->update([
            'code' => $data['code'],
            'title' => $data['title'],
            'requirement_text' => array_key_exists('requirement_text', $data) ? $data['requirement_text'] : $criterion->requirement_text,
            'assessment_guidance' => array_key_exists('assessment_guidance', $data) ? $data['assessment_guidance'] : $criterion->assessment_guidance,
            'evidence_guidance' => array_key_exists('evidence_guidance', $data) ? $data['evidence_guidance'] : $criterion->evidence_guidance,
            'max_score' => $data['max_score'] ?? $criterion->max_score,
            'sort_order' => $data['sort_order'] ?? $criterion->sort_order,
            'active' => array_key_exists('active', $data) ? (bool) $data['active'] : $criterion->active,
        ]);

        return $criterion->refresh();
    }

    public function getStandardsForOrganization(?string $organizationId)
    {
        return AuditStandard::forOrganization($organizationId)
            ->with('sourceDocument', 'instrumentVersions')
            ->orderBy('code')
            ->get();
    }

    public function getVersionsForStandard(string $standardId)
    {
        return InstrumentVersion::where('audit_standard_id', $standardId)
            ->orderByDesc('version_label')
            ->get();
    }
}
