<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attachment extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'clinic_id', 'disk', 'path', 'original_name',
        'mime_type', 'size_bytes', 'checksum', 'metadata', 'uploaded_by',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
