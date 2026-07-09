<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentExpiryAlert extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'document_id', 'clinic_id', 'alert_level', 'expiry_date',
        'days_before', 'status', 'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'notified_at' => 'datetime',
            'days_before' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
