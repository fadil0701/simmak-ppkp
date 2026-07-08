<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends BaseModel
{
    protected $fillable = [
        'code',
        'name',
        'org_type',
        'address',
        'phone',
        'email',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
        ]);
    }

    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
