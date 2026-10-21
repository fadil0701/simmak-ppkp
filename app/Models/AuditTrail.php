<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuditTrail extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'metadata',
    ];

    protected $guarded = ['id', 'created_at'];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Audit trail is append-only. Prevent updates to existing records.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        if ($this->exists) {
            throw new \Illuminate\Database\Eloquent\MassAssignmentException(
                'Audit trail records are append-only and cannot be updated.'
            );
        }

        return parent::update($attributes, $options);
    }

    /**
     * Audit trail is append-only. Prevent deletion of records.
     */
    public function delete(): bool
    {
        throw new \Illuminate\Database\Eloquent\MassAssignmentException(
            'Audit trail records are append-only and cannot be deleted.'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new audit trail record with auto-generated UUID.
     */
    public static function create(array $attributes = []): static
    {
        if (empty($attributes['id'])) {
            $attributes['id'] = (string) Str::uuid();
        }

        return parent::create($attributes);
    }
}
