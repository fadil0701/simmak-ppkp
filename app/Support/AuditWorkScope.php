<?php

namespace App\Support;

use App\Models\AuditAssignment;
use App\Models\User;
use App\Support\SimmakRoles;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pembatasan kerja audit: auditor hanya melihat/mengerjakan
 * penugasan tempat ia menjadi anggota tim; auditi hanya klinik sendiri.
 */
class AuditWorkScope
{
    public static function isFieldAuditor(User $user): bool
    {
        return $user->can('audit.execution.execute')
            && ! $user->hasCrossClinicAccess();
    }

    public static function isAssignedTo(User $user, ?AuditAssignment $assignment): bool
    {
        if (! $assignment) {
            return false;
        }

        return $assignment->teamMembers()
            ->where('user_id', $user->id)
            ->where('active', true)
            ->exists();
    }

    /**
     * Pengelola jadwal / Kepala Pusat: boleh melihat draf dan antrian persetujuan.
     */
    public static function canOverseeSchedules(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->hasUnrestrictedAccess()) {
            return true;
        }

        if ($user->hasRole(SimmakRoles::KEPALA_PUSAT) && $user->can('audit.program.approve')) {
            return true;
        }

        return $user->can('audit.assignment.manage');
    }

    public static function canViewWork(User $user, ?string $clinicId, ?AuditAssignment $assignment = null): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($assignment && ! $user->belongsToOrganization($assignment->organization_id)) {
            return false;
        }

        if ($assignment && ! $assignment->isPublished()) {
            if (! self::canOverseeSchedules($user)) {
                return false;
            }

            if ($user->hasCrossClinicAccess() || $user->hasUnrestrictedAccess()) {
                return true;
            }

            return $clinicId ? $user->hasClinicAccess($clinicId) : false;
        }

        if ($assignment && self::isAssignedTo($user, $assignment)) {
            return true;
        }

        if ($clinicId) {
            return $user->hasClinicAccess($clinicId);
        }

        return false;
    }

    public static function assignments(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $table = $query->getModel()->getTable();

        if (self::canOverseeSchedules($user)) {
            if ($user->hasCrossClinicAccess()) {
                return $query->where($table.'.organization_id', $user->organization_id);
            }

            return $query->forUserClinics($user);
        }

        $query->whereIn($table.'.status', AuditAssignment::PUBLISHED_STATUSES);

        return $query->where(function (Builder $inner) use ($user, $table) {
            $inner->whereHas('teamMembers', function (Builder $members) use ($user) {
                $members->where('user_id', $user->id)->where('active', true);
            });

            $clinicIds = $user->clinics()->pluck('clinics.id')->filter()->all();
            if ($clinicIds !== []) {
                $inner->orWhereIn($table.'.clinic_id', $clinicIds);
            }
        });
    }

    public static function executions(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->hasCrossClinicAccess()) {
            return $query->where($query->getModel()->getTable().'.organization_id', $user->organization_id);
        }

        if (self::isFieldAuditor($user)) {
            return $query->whereHas('assignment.teamMembers', function (Builder $members) use ($user) {
                $members->where('user_id', $user->id)->where('active', true);
            });
        }

        return $query->forUserClinics($user);
    }

    public static function findings(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->hasCrossClinicAccess()) {
            return $query->where($query->getModel()->getTable().'.organization_id', $user->organization_id);
        }

        if (self::isFieldAuditor($user)) {
            return $query->where(function (Builder $inner) use ($user) {
                $inner->whereHas('execution.assignment.teamMembers', function (Builder $members) use ($user) {
                    $members->where('user_id', $user->id)->where('active', true);
                })->orWhere(function (Builder $fallback) use ($user) {
                    $clinicIds = $user->clinics()->pluck('clinics.id');
                    $fallback->whereNull('audit_execution_id')
                        ->whereIn('clinic_id', $clinicIds->all() ?: ['00000000-0000-0000-0000-000000000000']);
                });
            });
        }

        return $query->forUserClinics($user);
    }

    public static function capaCases(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->hasCrossClinicAccess()) {
            return $query->where($query->getModel()->getTable().'.organization_id', $user->organization_id);
        }

        if (self::isFieldAuditor($user)) {
            return $query->where(function (Builder $inner) use ($user) {
                $inner->whereHas('sourceFinding.execution.assignment.teamMembers', function (Builder $members) use ($user) {
                    $members->where('user_id', $user->id)->where('active', true);
                })->orWhere(function (Builder $fallback) use ($user) {
                    $clinicIds = $user->clinics()->pluck('clinics.id');
                    $fallback->whereDoesntHave('sourceFinding.execution.assignment')
                        ->whereIn('clinic_id', $clinicIds->all() ?: ['00000000-0000-0000-0000-000000000000']);
                });
            });
        }

        return $query->forUserClinics($user);
    }
}
