<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentQrCode;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Support\Str;

class DocumentService
{
    public function __construct(private AuditTrailService $auditTrail) {}

    public function generateDocumentNumber(User $user, string $typeCode): string
    {
        $prefix = strtoupper($typeCode);
        $year = now()->format('Y');
        $seq = Document::where('organization_id', $user->organization_id)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $seq);
    }

    public function createVersion(Document $document, User $user, array $data = []): DocumentVersion
    {
        $nextVersion = ($document->versions()->max('version_number') ?? 0) + 1;

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => $nextVersion,
            'status' => $data['status'] ?? 'draft',
            'change_summary' => $data['change_summary'] ?? 'Versi awal',
            'attachment_id' => $data['attachment_id'] ?? null,
            'file_name' => $data['file_name'] ?? null,
            'created_by' => $user->id,
        ]);

        $document->update(['current_version_id' => $version->id]);

        return $version;
    }

    public function publish(Document $document, User $user): Document
    {
        $document->update([
            'status' => 'published',
            'updated_by' => $user->id,
            'effective_date' => $document->effective_date ?? now()->toDateString(),
        ]);

        if ($document->currentVersion) {
            $document->currentVersion->update([
                'status' => 'published',
                'published_at' => now(),
            ]);
        }

        $this->ensureQrCode($document);

        $this->auditTrail->log('document.published', $document, metadata: ['user_id' => $user->id]);

        return $document->fresh();
    }

    public function ensureQrCode(Document $document): DocumentQrCode
    {
        $existing = $document->qrCode;
        if ($existing) {
            return $existing;
        }

        return DocumentQrCode::create([
            'document_id' => $document->id,
            'document_version_id' => $document->current_version_id,
            'qr_token' => Str::random(32),
            'is_active' => true,
            'generated_at' => now(),
        ]);
    }
}
