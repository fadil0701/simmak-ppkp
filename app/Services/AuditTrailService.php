<?php

namespace App\Services;

use App\Models\AuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditTrailService
{
    public function log(
        string $event,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
    ): AuditTrail {
        $user = auth()->user();

        return AuditTrail::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $user?->organization_id ?? $auditable?->organization_id,
            'clinic_id' => $user?->default_clinic_id,
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => $auditable ? $auditable::class : 'system',
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
            'url' => request()->fullUrl(),
            'metadata' => $metadata,
        ]);
    }
}
