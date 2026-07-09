<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskRegister extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'clinic_id', 'fiscal_year_id', 'register_code',
        'name', 'status', 'owner_id', 'metadata', 'created_by',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RiskItem::class);
    }
}
