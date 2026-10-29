<?php

namespace App\Policies;

use App\Models\ClinicalAudit;
use App\Models\User;
use App\Support\AuditWorkScope;

/**
 * Kewenangan audit klinis.
 *
 * - Melihat: izin `audit.clinical.view` yang sudah dijaga middleware route,
 *   ditambah pembatasan organisasi dan ruang lingkup klinik/unit kerja.
 * - Mengelola: izin `audit.clinical.manage` + klinik dalam ruang lingkup.
 * - Menghapus: hanya bila belum ada temuan yang tertaut (jejak audit dijaga).
 */
class ClinicalAuditPolicy
{
    public function view(?User $user, ClinicalAudit $audit): bool
    {
        if (! $user || ! $user->is_active || ! $user->belongsToOrganization($audit->organization_id)) {
            return false;
        }

        return AuditWorkScope::canViewWork($user, $audit->clinic_id, $audit->assignment)
            || $audit->auditor_id === $user->id;
    }

    public function viewAny(?User $user): bool
    {
        return $user && $user->is_active && $user->can('audit.clinical.view');
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->can('audit.clinical.manage');
    }

    public function update(User $user, ClinicalAudit $audit): bool
    {
        return $user->can('audit.clinical.manage')
            && $this->view($user, $audit)
            && $audit->status !== 'closed';
    }

    /**
     * Penghapusan tetap dijaga di controller: audit klinis yang sudah memiliki
     * temuan atau dokumentasi tidak dihapus (ditolak dengan pesan yang jelas).
     */
    public function delete(User $user, ClinicalAudit $audit): bool
    {
        return $user->can('audit.clinical.manage') && $this->view($user, $audit);
    }
}
