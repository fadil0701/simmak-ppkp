<?php

namespace App\Services;

use App\Models\AuditProgram;
use App\Models\AuditProgramDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuditProgramDocumentService
{
    public function __construct(private AuditEvidenceService $files) {}

    public function syncFromRequest(AuditProgram $program, Request $request, User $user): void
    {
        $rows = $request->input('documents', []);
        if (! is_array($rows) || $rows === []) {
            return;
        }

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $description = trim((string) ($row['description'] ?? ''));
            $id = filled($row['id'] ?? null) ? (string) $row['id'] : null;
            $file = $request->file('documents.'.$index.'.file');

            if ($title === '' && $id === null && $file === null) {
                continue;
            }

            if ($title === '') {
                throw ValidationException::withMessages([
                    'documents.'.$index.'.title' => 'Judul dokumen wajib diisi.',
                ]);
            }

            if ($id !== null) {
                $document = AuditProgramDocument::query()
                    ->where('audit_program_id', $program->id)
                    ->whereKey($id)
                    ->first();

                if ($document === null) {
                    throw ValidationException::withMessages([
                        'documents.'.$index.'.id' => 'Dokumen acuan tidak ditemukan pada program ini.',
                    ]);
                }

                $payload = [
                    'title' => $title,
                    'description' => $description !== '' ? $description : null,
                    'updated_by' => $user->id,
                ];

                if ($file !== null) {
                    $stored = $this->files->storeValidatedUpload(
                        $file,
                        $user,
                        $this->folder($program),
                        'documents.'.$index.'.file'
                    );
                    $this->deleteStoredFile($document);
                    $payload = array_merge($payload, $stored, ['uploaded_by' => $user->id, 'uploaded_at' => now()]);
                }

                $document->update($payload);

                continue;
            }

            if ($file === null) {
                throw ValidationException::withMessages([
                    'documents.'.$index.'.file' => 'File dokumen wajib diisi untuk dokumen baru.',
                ]);
            }

            $stored = $this->files->storeValidatedUpload(
                $file,
                $user,
                $this->folder($program),
                'documents.'.$index.'.file'
            );

            AuditProgramDocument::create(array_merge($stored, [
                'organization_id' => $program->organization_id,
                'audit_program_id' => $program->id,
                'title' => $title,
                'description' => $description !== '' ? $description : null,
                'uploaded_by' => $user->id,
                'uploaded_at' => now(),
                'updated_by' => $user->id,
            ]));
        }
    }

    public function fileExists(AuditProgramDocument $document): bool
    {
        try {
            return Storage::disk($document->storage_disk)->exists($document->storage_path);
        } catch (Throwable) {
            return false;
        }
    }

    private function folder(AuditProgram $program): string
    {
        return 'program_documents/'.$program->organization_id.'/'.$program->id;
    }

    private function deleteStoredFile(AuditProgramDocument $document): void
    {
        try {
            if (Storage::disk($document->storage_disk)->exists($document->storage_path)) {
                Storage::disk($document->storage_disk)->delete($document->storage_path);
            }
        } catch (Throwable) {
            // Metadata tetap diganti; berkas lama yang gagal dihapus tidak membatalkan unggahan baru.
        }
    }
}
