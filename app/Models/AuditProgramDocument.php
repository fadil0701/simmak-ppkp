<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditProgramDocument extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'audit_program_id', 'title', 'description',
        'storage_disk', 'storage_path', 'original_filename', 'mime_type',
        'size_bytes', 'checksum', 'metadata', 'uploaded_by', 'uploaded_at', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'size_bytes' => 'integer',
            'uploaded_at' => 'datetime',
        ]);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AuditProgram::class, 'audit_program_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
