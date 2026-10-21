<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;

    use HasFactory;
    use HasRoles;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'default_clinic_id',
        'name',
        'username',
        'email',
        'password',
        'auth_provider',
        'ldap_dn',
        'is_active',
        'last_login_at',
        'metadata',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function defaultClinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'default_clinic_id');
    }

    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class, 'user_clinic_scopes')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function isLocalAuth(): bool
    {
        return $this->auth_provider === 'local';
    }

    public function isLdapAuth(): bool
    {
        return $this->auth_provider === 'ldap';
    }

    /**
     * Super-admin / administrator sistem: akses lintas seluruh organisasi.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Apakah user berada pada scope manajemen (kantor pusat)?
     *
     * Manajemen Pusat adalah klinik dengan clinic_type 'pusat'. User dengan
     * scope ini berperan sebagai pengawas sehingga dapat mengakses seluruh
     * Klinik Utama dan Klinik Pratama di organisasinya.
     */
    public function hasHeadOfficeScope(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->clinics()
            ->where('clinics.clinic_type', 'pusat')
            ->exists();
    }

    /**
     * Apakah user melihat data lintas semua klinik tanpa konteks klinik aktif?
     * Administrator sistem tidak diikat ke satu klinik.
     */
    public function viewsAllClinics(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Apakah user boleh melihat/mengelola lintas klinik (manajemen pusat
     * atau administrator sistem)?
     */
    public function hasCrossClinicAccess(): bool
    {
        return $this->isSuperAdmin()
            || $this->hasHeadOfficeScope()
            || $this->can('clinics.manage')
            || $this->can('audit.configuration.manage');
    }

    /**
     * Apakah user berhak atas data yang dimiliki organisasi tertentu?
     *
     * Administrator sistem lintas seluruh organisasi; user lain hanya
     * organisasinya sendiri.
     */
    public function belongsToOrganization(?string $organizationId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $organizationId !== null && $this->organization_id === $organizationId;
    }

    /**
     * Get IDs of clinics this user may access.
     *
     * - Super-admin / administrator sistem: seluruh klinik, semua organisasi.
     * - Scope manajemen (kantor pusat): seluruh klinik dalam organisasinya.
     * - Selain itu: hanya klinik yang di-assign eksplisit (user_clinic_scopes).
     */
    public function clinicScopeIds(): Collection
    {
        if ($this->isSuperAdmin()) {
            return Clinic::query()->pluck('clinics.id');
        }

        if ($this->hasHeadOfficeScope() || $this->can('clinics.manage') || $this->can('audit.configuration.manage')) {
            return Clinic::query()
                ->where('clinics.organization_id', $this->organization_id)
                ->pluck('clinics.id');
        }

        return $this->clinics()->pluck('clinics.id');
    }

    /**
     * Check if user has access to a specific clinic.
     */
    public function hasClinicAccess(string $clinicId): bool
    {
        if ($this->hasCrossClinicAccess()) {
            if ($this->isSuperAdmin()) {
                return Clinic::whereKey($clinicId)->exists();
            }

            return Clinic::whereKey($clinicId)
                ->where('organization_id', $this->organization_id)
                ->exists();
        }

        return $this->clinics()->where('clinics.id', $clinicId)->exists();
    }

    /**
     * Get the active clinic ID from request attributes (set by SetClinicContext).
     */
    public function activeClinicId(Request $request): ?string
    {
        return $request->attributes->get('active_clinic')?->id
            ?? $this->default_clinic_id;
    }
}
