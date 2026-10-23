<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Batasi query pada satu organisasi.
     *
     * Null berarti tanpa batasan organisasi — dipakai administrator sistem
     * (lihat User::organizationFilterId()) agar daftar data lintas organisasi.
     */
    public function scopeForOrganization(Builder $query, ?string $organizationId): Builder
    {
        if ($organizationId === null) {
            return $query;
        }

        return $query->where($this->getTable().'.organization_id', $organizationId);
    }
}
