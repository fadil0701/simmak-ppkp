<?php

namespace App\Models\Concerns;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait ClinicScope
{
    /**
     * Scope query to clinics accessible by the given user.
     *
     * - Super-admin / administrator sistem: seluruh klinik (semua organisasi).
     * - Scope manajemen (kantor pusat): seluruh klinik dalam organisasinya.
     * - Selain itu: hanya klinik yang di-assign eksplisit (user_clinic_scopes).
     */
    public function scopeForUserClinics(Builder $query, ?User $user = null): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // Administrator sistem: lintas semua organisasi.
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // Manajemen pusat berperan sebagai pengawas: seluruh klinik dalam organisasi.
        if ($user->hasCrossClinicAccess()) {
            return $query->where($this->getTable().'.organization_id', $user->organization_id);
        }

        // Otherwise, restrict to clinics explicitly assigned to the user
        $clinicIds = $user->clinics()->pluck('clinics.id');

        if ($clinicIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        // Clinic model filters on its own `id`, other models filter on `clinic_id`
        $clinicColumn = ($this instanceof Clinic) ? 'id' : 'clinic_id';

        return $query->whereIn($this->getTable().'.'.$clinicColumn, $clinicIds);
    }

    /**
     * Scope query to a single active clinic context.
     */
    public function scopeForActiveClinic(Builder $query, ?Request $request = null): Builder
    {
        if ($request) {
            $activeClinic = $request->attributes->get('active_clinic');
            if ($activeClinic) {
                $clinicColumn = ($this instanceof Clinic) ? 'id' : 'clinic_id';

                return $query->where($this->getTable().'.'.$clinicColumn, $activeClinic->id);
            }
        }

        return $query;
    }
}
