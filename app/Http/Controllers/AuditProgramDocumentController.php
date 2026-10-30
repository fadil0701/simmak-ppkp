<?php

namespace App\Http\Controllers;

use App\Models\AuditProgramDocument;
use App\Services\AuditProgramDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AuditProgramDocumentController extends Controller
{
    public function __construct(private AuditProgramDocumentService $documents) {}

    public function show(Request $request, AuditProgramDocument $document): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $document);

        return $this->stream($document, inline: true);
    }

    public function download(Request $request, AuditProgramDocument $document): StreamedResponse|RedirectResponse
    {
        $this->authorize('download', $document);

        return $this->stream($document, inline: false);
    }

    private function stream(AuditProgramDocument $document, bool $inline): StreamedResponse|RedirectResponse
    {
        if (! $this->documents->fileExists($document)) {
            Log::warning('Dokumen acuan program tidak tersedia di penyimpanan.', [
                'document_id' => $document->id,
                'storage_path' => $document->storage_path,
            ]);

            return back()->with('error', 'Berkas "'.$document->original_filename.'" tidak tersedia di penyimpanan.');
        }

        try {
            $disposition = $inline ? 'inline' : 'attachment';

            return Storage::disk($document->storage_disk)->response(
                $document->storage_path,
                $document->original_filename,
                [
                    'Content-Type' => $document->mime_type,
                    'Content-Disposition' => $disposition.'; filename="'.$document->original_filename.'"',
                ]
            );
        } catch (Throwable $e) {
            Log::warning('Gagal mengirim dokumen acuan program.', [
                'document_id' => $document->id,
                'exception' => $e->getMessage(),
            ]);

            return back()->with('error', 'Berkas tidak dapat dibuka saat ini.');
        }
    }
}
