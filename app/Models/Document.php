<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends BaseModel
{
    use BelongsToOrganization;

    public const STATUSES = ['draft', 'review', 'approved', 'published', 'expired', 'archived'];

    protected $fillable = [
        'organization_id', 'clinic_id', 'unit_id', 'document_type_id',
        'document_number', 'title', 'status', 'effective_date', 'expiry_date',
        'owner_id', 'current_version_id', 'description', 'metadata',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'effective_date' => 'date',
            'expiry_date' => 'date',
        ]);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version_number');
    }

    public function qrCode(): HasOne
    {
        return $this->hasOne(DocumentQrCode::class)->where('is_active', true);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DocumentReview::class)->orderBy('step_order');
    }
}
