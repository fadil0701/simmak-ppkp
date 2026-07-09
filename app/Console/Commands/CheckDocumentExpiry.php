<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentExpiryAlert;
use Illuminate\Console\Command;

class CheckDocumentExpiry extends Command
{
    protected $signature = 'dms:check-expiry';

    protected $description = 'Tandai dokumen kedaluwarsa dan buat alert untuk dokumen yang akan habis masa berlakunya';

    public function handle(): int
    {
        $today = now()->toDateString();

        $expired = Document::query()
            ->where('status', 'published')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->update(['status' => 'expired']);

        $this->info("Dokumen ditandai kedaluwarsa: {$expired}");

        $thresholds = [30, 14, 7];

        foreach ($thresholds as $days) {
            $documents = Document::query()
                ->where('status', 'published')
                ->whereDate('expiry_date', now()->addDays($days)->toDateString())
                ->get();

            foreach ($documents as $document) {
                DocumentExpiryAlert::firstOrCreate(
                    [
                        'document_id' => $document->id,
                        'days_before' => $days,
                        'expiry_date' => $document->expiry_date,
                    ],
                    [
                        'clinic_id' => $document->clinic_id,
                        'alert_level' => $days <= 7 ? 'critical' : ($days <= 14 ? 'warning' : 'info'),
                        'status' => 'pending',
                    ]
                );
            }
        }

        $this->info('Pemeriksaan kedaluwarsa dokumen selesai.');

        return self::SUCCESS;
    }
}
