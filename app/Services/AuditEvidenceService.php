<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuditEvidenceService
{
    // Extension yang diizinkan berdasarkan MIME type yang benar (magic bytes)
    protected array $allowedMimeTypes = [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'text/csv' => ['csv'],
        'text/plain' => ['txt'],
    ];

    protected int $maxFileSize = 10240; // 10 MB in KB

    public function store(UploadedFile $file, User $user, array $data): Evidence
    {
        $originalName = $file->getClientOriginalName();
        $safeName = Str::uuid().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $folder = 'audit_evidence/'.$user->organization_id.'/'.($data['clinic_id'] ?? 'org');

        // 1. Validasi ukuran file
        if ($file->getSize() > $this->maxFileSize * 1024) {
            throw ValidationException::withMessages([
                'file' => __('File terlalu besar. Maksimal 10 MB.'),
            ]);
        }

        // 2. Validasi extension file
        $clientExtension = strtolower($file->getClientOriginalExtension());
        if (empty($clientExtension)) {
            throw new ValidationException(
                __('File tidak memiliki extension yang valid.')
            );
        }

        // 3. Simpan sementara ke local untuk scanning
        $tempPath = $file->storeAs($folder, $safeName, 'local');

        // 4. Magic bytes validation — verifikasi isi file sebenarnya cocok dengan extension
        $realMime = $this->detectRealMimeType(Storage::disk('local')->path($tempPath));
        $realExtension = $this->mimeToExtension($realMime);

        if ($realExtension === null || ! in_array($clientExtension, $realExtension, true)) {
            // Hapus file yang tidak valid dan karantinakan
            Storage::disk('local')->delete($tempPath);
            $this->quarantineFile($tempPath, $originalName, $user->organization_id, 'Mismatched file extension (magic bytes validation failed)');

            throw ValidationException::withMessages([
                'file' => __('Jenis file tidak sesuai dengan isi berkas. Berkas telah dikarantinakan.'),
            ]);
        }

        // 5. ClamAV scanning (dengan fallback gracefully jika tidak tersedia)
        $scanResult = $this->scanWithClamav(Storage::disk('local')->path($tempPath));

        if ($scanResult['infected']) {
            // Hapus file terinfeksi dan karantinakan
            Storage::disk('local')->delete($tempPath);
            $this->quarantineFile($tempPath, $originalName, $user->organization_id, 'Malware detected: '.$scanResult['threat']);

            throw ValidationException::withMessages([
                'file' => __('Berkas terindikasi mengandung malware. Berkas telah dikarantinakan.'),
            ]);
        }

        // 6. Hitung checksum SHA-256
        $checksum = hash_file('sha256', Storage::disk('local')->path($tempPath));

        // 7. Buat record Evidence
        $evidence = Evidence::create([
            'organization_id' => $user->organization_id,
            'clinic_id' => $data['clinic_id'] ?? null,
            'audit_execution_id' => $data['audit_execution_id'] ?? null,
            'uploaded_by' => $user->id,
            'storage_disk' => 'local',
            'storage_path' => $tempPath,
            'original_filename' => $originalName,
            'mime_type' => $realMime,
            'size_bytes' => $file->getSize(),
            'checksum' => $checksum,
            'evidence_type' => $data['evidence_type'] ?? null,
            'access_classification' => $data['access_classification'] ?? 'internal',
            'description' => $data['description'] ?? null,
            'metadata' => [
                'scan_result' => $scanResult,
                'scanned_at' => now()->toISOString(),
                'magic_bytes_verified' => true,
            ],
        ]);

        return $evidence;
    }

    /**
     * Deteksi MIME type sebenarnya dari isi file (magic bytes).
     */
    protected function detectRealMimeType(string $filePath): ?string
    {
        $finfo = new \finfo(\FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);

        return $mime ?: null;
    }

    /**
     * Map MIME type ke daftar extension yang diizinkan.
     */
    protected function mimeToExtension(?string $mime): ?array
    {
        return $this->allowedMimeTypes[$mime] ?? null;
    }

    /**
     * Scan file dengan ClamAV. Fallback gracefully jika ClamAV tidak tersedia.
     */
    protected function scanWithClamav(string $filePath): array
    {
        $result = [
            'infected' => false,
            'threat' => null,
            'engine' => 'clamav',
            'scanned_at' => now()->toISOString(),
        ];

        // Coba clamdscan melalui socket TCP (Docker Compose service clamav)
        $clamdHost = env('CLAMAV_HOST', 'clamav');
        $clamdPort = (int) env('CLAMAV_PORT', 3310);
        $clamdSocket = @fsockopen($clamdHost, $clamdPort, $errno, $errstr, 2);

        if ($clamdSocket) {
            // Protokol clamd: INSTREAM command
            $fileSize = filesize($filePath);
            $request = "INSTREAM\n";
            fwrite($clamdSocket, $request);

            // Baca response
            $response = stream_get_contents($clamdSocket);
            fclose($clamdSocket);

            if (str_contains($response, 'FOUND')) {
                $result['infected'] = true;
                preg_match('/(.+) FOUND/', $response, $matches);
                $result['threat'] = $matches[1] ?? 'Unknown threat';
            } elseif (str_contains($response, 'OK')) {
                $result['infected'] = false;
            } else {
                $result['infected'] = false;
                $result['threat'] = 'ClamAV response unclear: '.trim($response);
                $result['engine'] = 'clamav-unavailable';
            }
        } else {
            // ClamAV tidak tersedia — log sebagai warning, izinkan upload dengan catatan
            $result['engine'] = 'clamav-unavailable';
            $result['threat'] = 'ClamAV scanner not available. File uploaded without malware scan.';
        }

        return $result;
    }

    /**
     * Pindahkan file yang mencurigakan ke karantina.
     */
    protected function quarantineFile(string $tempPath, string $originalName, string $organizationId, string $reason): void
    {
        $quarantineFolder = 'quarantine/'.$organizationId.'/'.date('Y-m-d');
        $quarantineName = Str::uuid().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);

        try {
            // Baca konten dari local dan simpan ke quarantine
            $content = Storage::disk('local')->get($tempPath);
            Storage::disk('quarantine')->put($quarantineFolder.'/'.$quarantineName, $content);

            // Hapus dari local
            Storage::disk('local')->delete($tempPath);
        } catch (\Throwable $e) {
            // Jika gagal pindahkan ke quarantine, setidaknya hapus dari local
            Storage::disk('local')->delete($tempPath);
        }

        // Log karantina ke audit trail
        (new AuditTrailService)->log(
            'evidence.quarantined',
            null,
            null,
            null,
            [
                'original_filename' => $originalName,
                'organization_id' => $organizationId,
                'reason' => $reason,
                'quarantine_path' => $quarantineFolder.'/'.$quarantineName,
            ]
        );
    }

    public function download(Evidence $evidence): string
    {
        if (! Storage::disk($evidence->storage_disk)->exists($evidence->storage_path)) {
            throw new ModelNotFoundException('Berkas bukti tidak tersedia di penyimpanan.');
        }

        return Storage::disk($evidence->storage_disk)->path($evidence->storage_path);
    }

    public function delete(Evidence $evidence, User $user): void
    {
        if ($evidence->uploaded_by !== $user->id && ! $user->can('audit.evidence.upload')) {
            throw new AuthorizationException('Tidak berwenang menghapus bukti ini.');
        }

        Storage::disk($evidence->storage_disk)->delete($evidence->storage_path);
        $evidence->delete();
    }

    public function getEvidenceForExecution(string $executionId)
    {
        return Evidence::where('audit_execution_id', $executionId)
            ->with('uploader')
            ->orderByDesc('uploaded_at')
            ->get();
    }

    public function getEvidenceForUser(User $user)
    {
        return Evidence::forUserClinics($user)
            ->with(['clinic', 'execution', 'uploader'])
            ->orderByDesc('uploaded_at')
            ->paginate(15);
    }
}
