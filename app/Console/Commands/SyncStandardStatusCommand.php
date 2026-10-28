<?php

namespace App\Console\Commands;

use App\Models\AuditStandard;
use App\Models\InstrumentVersion;
use Illuminate\Console\Command;

/**
 * Selaraskan status master standar dengan versi instrumennya.
 *
 * Aturan: standar yang sudah punya versi instrumen berstatus "active"
 * seharusnya juga berstatus "active" pada master standar. Data lama
 * (sebelum perbaikan 28 Okt 2026) tertinggal pada "draft" sehingga
 * kolom Status di daftar standar tidak pernah berubah.
 *
 * Contoh:
 *   php artisan simmak:sync-standard-status
 *   php artisan simmak:sync-standard-status --dry-run
 */
class SyncStandardStatusCommand extends Command
{
    protected $signature = 'simmak:sync-standard-status {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Selaraskan status standar audit dengan status versi instrumennya';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Standar yang punya minimal satu versi aktif tetapi statusnya bukan active.
        $standardIds = InstrumentVersion::query()
            ->where('status', 'active')
            ->distinct()
            ->pluck('audit_standard_id');

        $changed = 0;

        AuditStandard::query()
            ->whereIn('id', $standardIds)
            ->where('status', '!=', 'active')
            ->get()
            ->each(function (AuditStandard $standard) use ($dryRun, &$changed) {
                $this->line(sprintf(
                    '%s  %s → active',
                    str_pad($standard->code, 12),
                    $standard->status
                ));

                if (! $dryRun) {
                    $standard->update(['status' => 'active']);
                }

                $changed++;
            });

        if ($changed === 0) {
            $this->info('Tidak ada standar yang perlu diselaraskan.');

            return self::SUCCESS;
        }

        $this->info($dryRun
            ? "Pratinjau: {$changed} standar akan diubah menjadi active (tidak ada yang disimpan)."
            : "{$changed} standar diselaraskan menjadi active.");

        return self::SUCCESS;
    }
}
