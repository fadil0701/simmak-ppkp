<?php

namespace App\Services;

use App\Models\AuditFinding;
use App\Models\CapaCase;
use App\Models\User;
use Illuminate\Support\Str;

class AuditCapaService
{
    public function generateFindingCode(User $user): string
    {
        $year = now()->format('Y');
        $seq = AuditFinding::where('organization_id', $user->organization_id)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('TMN-%s-%04d', $year, $seq);
    }

    public function generateCapaNumber(User $user): string
    {
        $year = now()->format('Y');
        $seq = CapaCase::where('organization_id', $user->organization_id)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('CAPA-%s-%04d', $year, $seq);
    }

    public function createCapaFromFinding(AuditFinding $finding, User $user): CapaCase
    {
        $capa = CapaCase::create([
            'organization_id' => $finding->organization_id,
            'clinic_id' => $finding->clinic_id,
            'case_number' => $this->generateCapaNumber($user),
            'source_type' => 'audit_finding',
            'source_id' => $finding->id,
            'priority' => match ($finding->severity) {
                'critical' => 'critical',
                'major' => 'high',
                default => 'medium',
            },
            'problem_statement' => $finding->title.': '.($finding->description ?? ''),
            'status' => 'open',
            'owner_id' => $finding->assigned_to ?? $user->id,
            'target_close_date' => now()->addDays(30)->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $finding->update(['status' => 'in_capa', 'updated_by' => $user->id]);

        return $capa;
    }
}
