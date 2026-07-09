<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskItem extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'risk_register_id', 'risk_category_id', 'clinic_id',
        'risk_code', 'title', 'risk_description', 'risk_source',
        'inherent_likelihood', 'inherent_impact', 'inherent_score',
        'residual_likelihood', 'residual_impact', 'residual_score',
        'risk_level', 'status', 'owner_id', 'review_date', 'metadata', 'created_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'review_date' => 'date',
            'inherent_likelihood' => 'integer',
            'inherent_impact' => 'integer',
            'inherent_score' => 'integer',
            'residual_likelihood' => 'integer',
            'residual_impact' => 'integer',
            'residual_score' => 'integer',
        ]);
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(RiskRegister::class, 'risk_register_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RiskCategory::class, 'risk_category_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function mitigationPlans(): HasMany
    {
        return $this->hasMany(RiskMitigationPlan::class);
    }
}
