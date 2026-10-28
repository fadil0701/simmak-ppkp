<?php

namespace App\Services;

use App\Models\AuditAssignment;
use App\Models\User;
use App\Support\AuditWorkScope;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AuditCalendarService
{
    /** @var list<string> */
    private const MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * @return array{
     *     year: int,
     *     month: int,
     *     title: string,
     *     previous: array{year: int, month: int},
     *     next: array{year: int, month: int},
     *     weekday_labels: list<string>,
     *     weeks: list<list<array{date: string, in_month: bool, is_today: bool, events: list<array<string, mixed>>}>>,
     *     assignments: Collection<int, AuditAssignment>
     * }
     */
    public function monthView(User $user, int $year, int $month): array
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(CarbonInterface::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(CarbonInterface::SUNDAY);

        $assignments = AuditWorkScope::assignments(AuditAssignment::query(), $user)
            ->whereNotNull('scheduled_start')
            ->whereNotNull('scheduled_end')
            ->whereDate('scheduled_start', '<=', $monthEnd->toDateString())
            ->whereDate('scheduled_end', '>=', $monthStart->toDateString())
            ->with(['clinic:id,code,name', 'program:id,name', 'leadAuditor:id,name'])
            ->orderBy('scheduled_start')
            ->get();

        $byDate = [];
        foreach ($assignments as $assignment) {
            $event = [
                'id' => $assignment->id,
                'code' => $assignment->assignment_code,
                'title' => $assignment->title,
                'clinic' => $assignment->clinic?->name ?? '—',
                'status' => $assignment->status,
                'url' => route('audit.assignments.show', $assignment),
            ];

            $cursor = $assignment->scheduled_start->copy()->startOfDay();
            $last = $assignment->scheduled_end->copy()->startOfDay();
            while ($cursor->lte($last)) {
                if ($cursor->betweenIncluded($monthStart, $monthEnd)) {
                    $byDate[$cursor->toDateString()][] = $event;
                }
                $cursor->addDay();
            }
        }

        $weeks = [];
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->toDateString();
                $week[] = [
                    'date' => $key,
                    'day' => $cursor->day,
                    'in_month' => $cursor->month === $monthStart->month,
                    'is_today' => $cursor->isToday(),
                    'events' => $byDate[$key] ?? [],
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        $previous = $monthStart->copy()->subMonth();
        $next = $monthStart->copy()->addMonth();

        return [
            'year' => $year,
            'month' => $month,
            'title' => self::MONTHS[$monthStart->month].' '.$monthStart->year,
            'previous' => ['year' => $previous->year, 'month' => $previous->month],
            'next' => ['year' => $next->year, 'month' => $next->month],
            'weekday_labels' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
            'weeks' => $weeks,
            'assignments' => $assignments,
        ];
    }
}
