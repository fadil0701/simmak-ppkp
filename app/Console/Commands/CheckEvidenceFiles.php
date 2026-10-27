<?php

namespace App\Console\Commands;

use App\Models\Evidence;
use App\Services\AuditEvidenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Audit integritas berkas bukti: memastikan setiap baris `evidence` punya
 * berkas fisik di disk yang dirujuknya, dan (opsional) checksum SHA-256 masih
 * cocok.
 *
 * Dipakai untuk kasus khas container: basis data ikut ter-restore/berpindah
 * volume tetapi folder `storage/app/private` (tempat bukti disimpan) tidak,
 * sehingga unduhan gagal dengan UnableToRetrieveMetadata/404.
 */
class CheckEvidenceFiles extends Command
{
    protected $signature = 'simmak:check-evidence-files
        {--org= : Batasi pemeriksaan pada satu organization_id}
        {--limit=0 : Batasi jumlah baris yang diperiksa (0 = semua)}
        {--checksum : Verifikasi checksum SHA-256 berkas terhadap kolom checksum}
        {--strict : Keluar dengan kode gagal bila ada berkas yang hilang}';

    protected $description = 'Periksa keberadaan dan integritas berkas bukti audit di disk penyimpanan';

    public function handle(AuditEvidenceService $evidenceService): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $verifyChecksum = (bool) $this->option('checksum');

        $query = Evidence::query()
            ->when($this->option('org'), fn ($q, $org) => $q->where('organization_id', $org))
            ->orderBy('created_at')
            ->when($limit > 0, fn ($q) => $q->limit($limit));

        $total = 0;
        $missing = [];
        $mismatched = [];
        $unreadableDisk = [];

        foreach ($query->cursor() as $evidence) {
            $total++;

            if (! $evidenceService->fileExists($evidence)) {
                $missing[] = $evidence;

                continue;
            }

            if (! $verifyChecksum || ! $evidence->checksum) {
                continue;
            }

            try {
                $disk = Storage::disk($evidence->storage_disk);
                $path = $evidence->storage_disk === 'local'
                    ? $disk->path($evidence->storage_path)
                    : null;

                if ($path === null) {
                    continue; // checksum hanya untuk disk lokal
                }

                $actual = hash_file('sha256', $path);

                if ($actual !== $evidence->checksum) {
                    $mismatched[] = [$evidence, $actual];
                }
            } catch (\Throwable $e) {
                $unreadableDisk[] = [$evidence, $e->getMessage()];
            }
        }

        $this->newLine();
        $this->line("Baris bukti diperiksa : <info>{$total}</info>");
        $this->line('Berkas hilang          : <comment>'.count($missing).'</comment>');
        $this->line('Checksum tidak cocok   : <comment>'.count($mismatched).'</comment>');

        if ($missing !== []) {
            $this->newLine();
            $this->error('Berkas berikut tercatat di basis data tetapi tidak ada di disk:');

            $this->table(
                ['ID', 'Diunggah', 'Nama Berkas', 'Disk', 'Path'],
                array_map(fn (Evidence $e) => [
                    $e->id,
                    $e->uploaded_at?->format('Y-m-d H:i') ?? '-',
                    $e->original_filename,
                    $e->storage_disk,
                    $e->storage_path,
                ], $missing),
            );

            $this->newLine();
            $this->line('Periksa pemetaan volume storage container (contoh: <comment>docker compose exec app ls -l storage/app/private</comment>).');
            $this->line('Bila berkas memang sudah tidak ada, unggah ulang bukti lewat menu Bukti Audit agar baris lama diganti berkas baru.');
        }

        if ($mismatched !== []) {
            $this->newLine();
            $this->error('Berkas dengan checksum berbeda (kemungkinan berubah/tertanam):');

            $this->table(
                ['ID', 'Nama Berkas', 'Checksum DB', 'Checksum Aktual'],
                array_map(fn (array $row) => [
                    $row[0]->id,
                    $row[0]->original_filename,
                    $row[0]->checksum,
                    $row[1],
                ], $mismatched),
            );
        }

        if ($unreadableDisk !== []) {
            $this->newLine();
            $this->error('Berkas ada tetapi tidak dapat dibaca:');

            $this->table(
                ['ID', 'Nama Berkas', 'Pesan'],
                array_map(fn (array $row) => [$row[0]->id, $row[0]->original_filename, $row[1]], $unreadableDisk),
            );
        }

        $failed = $missing !== [] || $mismatched !== [] || $unreadableDisk !== [];

        if (! $failed) {
            $this->info('Semua berkas bukti tersedia'.($verifyChecksum ? ' dan checksum cocok' : '').'.');
        }

        return $failed && $this->option('strict') ? self::FAILURE : self::SUCCESS;
    }
}
