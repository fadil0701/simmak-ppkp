<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapaAction extends BaseModel
{
    protected $fillable = [
        'capa_case_id', 'action_type', 'description', 'assigned_to',
        'due_date', 'status', 'completed_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ]);
    }

    public function capaCase(): BelongsTo
    {
        return $this->belongsTo(CapaCase::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
