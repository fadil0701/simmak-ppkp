<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapaAction extends BaseModel
{
    protected $fillable = [
        'capa_case_id', 'action_type', 'description', 'assigned_to',
        'due_date', 'status', 'completed_at', 'evidence_notes', 'evidence_submitted_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'evidence_submitted_at' => 'datetime',
        ]);
    }

    public function hasSubmittedEvidence(): bool
    {
        if ($this->evidence_submitted_at === null) {
            return false;
        }

        return filled($this->evidence_notes) || $this->evidenceLinks()->exists();
    }

    public function capaCase(): BelongsTo
    {
        return $this->belongsTo(CapaCase::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function evidenceLinks(): HasMany
    {
        return $this->hasMany(ActionEvidence::class, 'corrective_action_id');
    }
}
