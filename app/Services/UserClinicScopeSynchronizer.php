<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;

/**
 * Menyelaraskan `user_clinic_scopes` dengan klinik yang dipegang pegawai.
 *
 * Sumber kebenaran akses klinik adalah master pegawai
 * (`employee_clinic_assignments`). `user_clinic_scopes` hanya cermin agar
 * `User::clinicScopeIds()`, `SetClinicContext`, `ClinicScope`, dan
 * `AuditWorkScope` tetap bekerja tanpa perubahan.
 */
class UserClinicScopeSynchronizer
{
    /**
     * Selaraskan seluruh akun yang terhubung dengan pegawai.
     */
    public function syncForEmployee(Employee $employee): void
    {
        $isActive = (bool) $employee->is_active;
        $clinicIds = $isActive ? $employee->clinicScopeIds() : [];
        $defaultClinicId = $isActive ? $employee->primaryClinicId() : null;

        $employee->users()->get()->each(
            fn (User $user) => $this->syncScopes($user, $clinicIds, $defaultClinicId),
        );
    }

    /**
     * Tulis ulang scope klinik akun.
     *
     * @param  array<int, string>  $clinicIds
     */
    public function syncScopes(User $user, array $clinicIds, ?string $defaultClinicId = null): void
    {
        $clinicIds = array_values(array_unique(array_filter($clinicIds)));

        if ($clinicIds === [] && $defaultClinicId !== null) {
            $clinicIds = [$defaultClinicId];
        }

        $default = $defaultClinicId !== null && in_array($defaultClinicId, $clinicIds, true)
            ? $defaultClinicId
            : ($clinicIds[0] ?? null);

        $sync = collect($clinicIds)
            ->mapWithKeys(fn (string $id) => [
                $id => ['is_default' => $id === $default],
            ])
            ->all();

        $user->clinics()->sync($sync);

        if ($default !== $user->default_clinic_id) {
            $user->forceFill(['default_clinic_id' => $default])->save();
        }
    }
}
