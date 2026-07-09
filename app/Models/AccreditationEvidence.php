<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccreditationEvidence extends BaseModel
{
    use BelongsToOrganization;

    protected $table = 'accreditation_evidence';

    protected $fillable = [
        'organization_id', 'program_id', 'clinic_id', 'standard_id',
        'title', 'status', 'document_id',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(AccreditationProgram::class, 'program_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
