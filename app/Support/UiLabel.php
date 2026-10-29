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

    /**
     * Status master standar audit (tabel audit_standards).
     *
     * Berbeda dari executionStatus(): standar memakai daur hidup dokumen
     * (draft → active → inactive/archived), bukan status pelaksanaan audit.
     */
    public static function standardStatus(?string $status): string
    {
        return match ($status) {
            'draft' => 'Draf',
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
            'archived' => 'Diarsipkan',
            default => $status ?? '—',
        };
    }

    /**
     * Kelas badge Tailwind untuk status standar.
     */
    public static function standardStatusTone(?string $status): string
    {
        return match ($status) {
            'active' => 'border-sim-success bg-sim-success-bg text-sim-success',
            'inactive' => 'border-sim-warning bg-sim-warning-bg text-sim-warning',
            'archived' => 'border-sim-neutral bg-sim-neutral-bg text-sim-muted',
            default => 'border-sim-info bg-sim-info-bg text-sim-info',
        };
    }

    /**
     * Status versi instrumen (tabel instrument_versions).
     */
    public static function instrumentStatus(?string $status): string
    {
        return match ($status) {
            'draft' => 'Draf',
            'review' => 'Tinjauan',
            'approved' => 'Disahkan',
            'active' => 'Aktif',
            'archived' => 'Diarsipkan',
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
            'in_capa' => 'Dalam Tindak Lanjut',
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

    /**
     * Status jadwal penugasan auditor (tabel audit_assignments).
     */
    public static function assignmentStatus(?string $status): string
    {
        return match ($status) {
            'draft' => 'Draf',
            'proposed' => 'Menunggu Persetujuan Kepala Pusat',
            'approved' => 'Disetujui/Dipublikasikan',
            'rejected' => 'Ditolak',
            'active' => 'Dipublikasikan',
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

    /**
     * Peran anggota di dalam satu penugasan audit — bukan peran akun SIMMAK.
     */
    public static function teamRole(?string $role): string
    {
        return match ($role) {
            'lead_auditor' => 'Ketua tim auditor',
            'auditor' => 'Anggota auditor',
            'observer' => 'Pengamat',
            default => $role ? str_replace('_', ' ', $role) : '—',
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

    /**
     * Status audit klinis (tabel clinical_audits).
     */
    public static function clinicalAuditStatus(?string $status): string
    {
        return match ($status) {
            'draft' => 'Draf',
            'in_progress' => 'Berjalan',
            'completed' => 'Selesai',
            'closed' => 'Ditutup',
            default => $status ?? '—',
        };
    }

    /**
     * Hasil penilaian elemen audit klinis (skala kesesuaian).
     */
    public static function clinicalResult(?string $result): string
    {
        return match ($result) {
            'conform' => 'Sesuai',
            'partial' => 'Sesuai sebagian',
            'non_conform' => 'Tidak sesuai',
            'na' => 'Belum dinilai',
            default => $result ?? '—',
        };
    }
}
