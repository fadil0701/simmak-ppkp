<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapaVerification extends Model
{
    use BelongsToOrganization;
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id', 'capa_case_id', 'verification_date', 'result',
        'notes', 'is_effective', 'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'verification_date' => 'date',
            'is_effective' => 'boolean',
        ];
    }

    public function capaCase(): BelongsTo
    {
        return $this->belongsTo(CapaCase::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
