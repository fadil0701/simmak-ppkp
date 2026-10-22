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
        $source->update([
            'verification_status' => $data['verification_status'] ?? 'VERIFIED_IN_FORCE',
            'verified_at' => now(),
            'verified_by' => $user->id,
            'verification_notes' => $data['verification_notes'] ?? null,
        ]);

        return $source;
    }

    public function createStandard(array $data, User $user): AuditStandard
    {
        return AuditStandard::create([
            'organization_id' => $user->organization_id,
            'source_document_id' => $data['source_document_id'] ?? null,
            'code' => $data['code'],
            'title' => $data['title'],
            'status' => 'draft',
        ]);
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
        if ($version->status !== 'draft') {
            throw new ModelNotFoundException('Hanya versi draft yang dapat diajukan tinjauan.');
        }
        $version->update(['status' => 'review']);
    }

    public function approveVersion(InstrumentVersion $version, User $user): void
    {
        if ($version->status !== 'review') {
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
        if ($version->status !== 'approved') {
            throw new ModelNotFoundException('Hanya versi yang sudah disahkan yang dapat diaktifkan.');
        }

        // Nonaktifkan versi aktif lain pada standar yang sama
        InstrumentVersion::where('audit_standard_id', $version->audit_standard_id)
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        $version->update(['status' => 'active']);
    }

    public function createSection(array $data, User $user): StandardSection
    {
        $version = InstrumentVersion::findOrFail($data['instrument_version_id']);

        if ($version->isLocked()) {
            throw ValidationException::withMessages([
                'version' => 'Versi instrumen sudah dipakai eksekusi audit dan dikunci. Buat versi baru untuk mengubah isi kriteria/seksi.',
            ]);
        }

        return StandardSection::create([
            'instrument_version_id' => $version->id,
            'parent_id' => $data['parent_id'] ?? null,
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function createCriterion(array $data, User $user): StandardCriterion
    {
        $section = StandardSection::with('instrumentVersion')->findOrFail($data['standard_section_id']);
        $version = $section->instrumentVersion;

        if ($version && $version->isLocked()) {
            throw ValidationException::withMessages([
                'version' => 'Versi instrumen sudah dipakai eksekusi audit dan dikunci. Buat versi baru untuk mengubah isi kriteria/seksi.',
            ]);
        }

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

    public function getStandardsForOrganization(string $organizationId)
    {
        return AuditStandard::where('organization_id', $organizationId)
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
