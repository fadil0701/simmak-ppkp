<?php

namespace App\Services;

class RiskService
{
    public function calculateScore(int $likelihood, int $impact): int
    {
        return $likelihood * $impact;
    }

    public function riskLevel(int $score): string
    {
        return match (true) {
            $score >= 20 => 'extreme',
            $score >= 12 => 'high',
            $score >= 6 => 'medium',
            default => 'low',
        };
    }

    public function riskLevelLabel(string $level): string
    {
        return match ($level) {
            'extreme' => 'Sangat Tinggi',
            'high' => 'Tinggi',
            'medium' => 'Sedang',
            default => 'Rendah',
        };
    }

    public function riskLevelColor(string $level): string
    {
        return match ($level) {
            'extreme' => 'bg-red-700 text-white',
            'high' => 'bg-red-100 text-red-800',
            'medium' => 'bg-amber-100 text-amber-800',
            default => 'bg-emerald-100 text-emerald-800',
        };
    }

    public function heatmapColor(int $likelihood, int $impact): string
    {
        $score = $this->calculateScore($likelihood, $impact);

        return match (true) {
            $score >= 20 => 'bg-red-700',
            $score >= 12 => 'bg-red-500',
            $score >= 8 => 'bg-orange-400',
            $score >= 4 => 'bg-yellow-400',
            default => 'bg-green-400',
        };
    }

    /**
     * @return array<int, array<int, int>>
     */
    public function buildHeatmap(iterable $items): array
    {
        $grid = [];
        for ($l = 1; $l <= 5; $l++) {
            for ($i = 1; $i <= 5; $i++) {
                $grid[$l][$i] = 0;
            }
        }

        foreach ($items as $item) {
            $l = min(5, max(1, (int) $item->inherent_likelihood));
            $i = min(5, max(1, (int) $item->inherent_impact));
            $grid[$l][$i]++;
        }

        return $grid;
    }
}
