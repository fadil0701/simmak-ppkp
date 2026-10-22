<?php

namespace Tests\Unit\Audit;

use App\Models\Organization;
use App\Models\User;
use App\Services\AuditEvidenceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EvidenceSecurityTest extends TestCase
{
    /** @test */
    public function valid_pdf_file_is_accepted(): void
    {
        Storage::fake('local');

        $org = Organization::create([
            'code' => 'TEST-EV',
            'name' => 'Test Evidence Org',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Evidence Uploader',
            'email' => 'evidence@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);

        // Buat file PDF palsu dengan magic bytes %PDF
        $pdfContent = "%PDF-1.4\n%����\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF";
        $tmpPath = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($tmpPath, $pdfContent);
        $file = new UploadedFile($tmpPath, 'laporan.pdf', 'application/pdf', null, true);

        $service = new AuditEvidenceService;

        // Karena ClamAV tidak tersedia di test, scan akan gagal graceful dan file akan diterima
        // dengan catatan clamav-unavailable
        $evidence = $service->store($file, $user, [
            'clinic_id' => null,
            'audit_execution_id' => null,
            'evidence_type' => 'laporan_audit',
            'access_classification' => 'internal',
            'description' => 'Laporan audit contoh',
        ]);

        $this->assertNotNull($evidence->id);
        $this->assertEquals('application/pdf', $evidence->mime_type);
        $this->assertTrue($evidence->metadata['magic_bytes_verified'] ?? false);
        $this->assertContains($evidence->metadata['scan_result']['engine'] ?? null, ['clamav', 'clamav-unavailable']);
    }

    /** @test */
    public function file_with_mismatched_extension_is_rejected(): void
    {
        Storage::fake('local');

        $org = Organization::create([
            'code' => 'TEST-EV-2',
            'name' => 'Test Evidence Org 2',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Evidence Uploader 2',
            'email' => 'evidence2@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);

        // File dengan extension .pdf tapi isi sebenarnya adalah JPEG (magic bytes JPEG)
        $jpegContent = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00";
        $tmpPath = tempnam(sys_get_temp_dir(), 'jpg');
        file_put_contents($tmpPath, $jpegContent);
        $file = new UploadedFile($tmpPath, 'fake.pdf', 'application/pdf', null, true);

        $service = new AuditEvidenceService;

        $this->expectException(ValidationException::class);
        $service->store($file, $user, [
            'clinic_id' => null,
            'audit_execution_id' => null,
            'evidence_type' => 'laporan_audit',
            'access_classification' => 'internal',
            'description' => 'File dengan extension palsu',
        ]);
    }

    /** @test */
    public function oversized_file_is_rejected(): void
    {
        Storage::fake('local');

        $org = Organization::create([
            'code' => 'TEST-EV-3',
            'name' => 'Test Evidence Org 3',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Evidence Uploader 3',
            'email' => 'evidence3@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);

        // Buat file lebih dari 10 MB
        $largeContent = str_repeat('A', 1024 * 1024 * 11); // 11 MB
        $tmpPath = tempnam(sys_get_temp_dir(), 'large');
        file_put_contents($tmpPath, $largeContent);
        $file = new UploadedFile($tmpPath, 'large.pdf', 'application/pdf', null, true);

        $service = new AuditEvidenceService;

        $this->expectException(ValidationException::class);
        $service->store($file, $user, [
            'clinic_id' => null,
            'audit_execution_id' => null,
            'evidence_type' => 'laporan_audit',
            'access_classification' => 'internal',
            'description' => 'File terlalu besar',
        ]);
    }
}
