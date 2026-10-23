<?php

namespace App\Support;

class UiLabel
{
    public static function documentStatus(?string $status): string
    {
        return match ($status) {
            'draft' => 'Draf',
            'review' => 'Tinjauan',
            'approved' => 'Disetujui',
            'published' => 'Terbit',
            'expired' => 'Kedaluwarsa',
            default => $status ?? '—',
        };
    }

    public static function reviewStatus(?string $status): string
    {
        return match ($status) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => $status ?? '—',
        };
    }

    public static function capaStatus(?string $status): string
    {
        return match ($status) {
            'open' => 'Terbuka',
            'analysis' => 'Analisis',
            'action' => 'Tindakan',
            'verification' => 'Verifikasi',
            'closed' => 'Selesai',
            default => $status ?? '—',
        };
    }

    public static function capaActionStatus(?string $status): string
    {
        return match ($status) {
            'pending' => 'Rencana',
            'in_progress' => 'Berjalan',
            'evidence_submitted' => 'Bukti Dikirim',
            'verified' => 'Terverifikasi',
            'needs_further_action' => 'Perlu Tindak Lanjut',
            default => $status ?? '—',
        };
    }

    public static function capaVerificationResult(?string $result): string
    {
        return match ($result) {
            'effective' => 'Efektif',
            'needs_improvement' => 'Perlu Perbaikan',
            'not_effective' => 'Tidak Efektif',
            'pending' => 'Menunggu',
            default => $result ?? '—',
        };
    }

    public static function capaPriority(?string $priority): string
    {
        return match ($priority) {
            'critical' => 'Kritikal',
            'high' => 'Tinggi',
            'medium' => 'Sedang',
            'low' => 'Rendah',
            default => $priority ?? '—',
        };
    }

    public static function gapSeverity(?string $severity): string
    {
        return match ($severity) {
            'critical' => 'Kritikal',
            'major' => 'Mayor',
            'minor' => 'Ringan',
            default => $severity ?? '—',
        };
    }

    public static function gapStatus(?string $status): string
    {
        return match ($status) {
            'open' => 'Terbuka',
            'in_progress' => 'Dalam Proses',
            'closed' => 'Selesai',
            default => $status ?? '—',
        };
    }

    public static function findingStatus(?string $status): string
    {
        return match ($status) {
            'open' => 'Terbuka',
            'draft' => 'Draf',
            'under_review' => 'Tinjauan',
            'returned_for_revision' => 'Dikembalikan',
            'confirmed' => 'Dikonfirmasi',
            'action_required' => 'Perlu Tindakan',
            'in_capa' => 'Dalam CAPA',
            'pending_verification' => 'Menunggu Verifikasi',
            'closed' => 'Selesai',
            'verified' => 'Terverifikasi',
            default => $status ?? '—',
        };
    }

    public static function riskStatus(?string $status): string
    {
        return match ($status) {
            'identified' => 'Teridentifikasi',
            'mitigating' => 'Mitigasi',
            'monitoring' => 'Pemantauan',
            'closed' => 'Selesai',
            default => $status ?? '—',
        };
    }

    public static function taskStatus(string $status): string
    {
        return match ($status) {
            'overdue' => 'Terlambat',
            'in_progress' => 'Dalam Proses',
            'todo' => 'Belum Mulai',
            default => $status,
        };
    }

    public static function conformity(?string $value): string
    {
        return match ($value) {
            'compliant' => 'Sesuai',
            'partial' => 'Sesuai sebagian',
            'non_compliant' => 'Tidak sesuai',
            'not_applicable' => 'Tidak berlaku',
            'not_assessable' => 'Belum dinilai',
            default => $value ?? '—',
        };
    }

    public static function findingSeverity(?string $severity): string
    {
        return match ($severity) {
            'observation' => 'Observasi',
            'minor' => 'Ringan',
            'major' => 'Mayor',
            'critical' => 'Kritikal',
            default => $severity ?? '—',
        };
    }

    /**
     * Status eksekusi/pelaksanaan audit (tabel audit_executions).
     */
    public static function executionStatus(?string $status): string
    {
        return match ($status) {
            'preparation' => 'Persiapan',
            'in_progress' => 'Berjalan',
            'submitted_for_review' => 'Menunggu tinjauan',
            'finalized' => 'Final',
            default => $status ?? '—',
        };
    }

    public static function auditStatus(?string $status): string
    {
        return match ($status) {
            'scheduled' => 'Terjadwal',
            'ongoing' => 'Berjalan',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => $status ?? '—',
        };
    }

    public static function auditType(?string $type): string
    {
        return match ($type) {
            'periodic' => 'Periodik',
            'thematic' => 'Tematik',
            'special' => 'Khusus',
            'follow_up' => 'Ulang',
            default => $type ?? '—',
        };
    }

    public static function clinicType(?string $type): string
    {
        return match ($type) {
            'pratama' => 'Klinik Pratama',
            'utama' => 'Klinik Utama',
            'pusat' => 'Manajemen',
            default => $type ?? '—',
        };
    }
}
