@extends('layouts.simmak')

@section('title', 'Kalender Audit — SIMMAK PPKP')
@section('page-title', 'Kalender Audit')
@section('page-subtitle', 'Jadwal penugasan auditor per bulan, sesuai ruang lingkup klinik Anda')

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">Kalender Audit</h2>
            <p class="sim-page-subtitle">Penugasan ditampilkan pada setiap hari di rentang tanggal audit. Data sama dengan daftar penugasan.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('audit.calendar', $calendar['previous']) }}" class="sim-btn sim-btn-back">← Bulan sebelumnya</a>
            <a href="{{ route('audit.calendar', ['year' => now()->year, 'month' => now()->month]) }}" class="sim-btn sim-btn-neutral">Bulan ini</a>
            <a href="{{ route('audit.calendar', $calendar['next']) }}" class="sim-btn sim-btn-back">Bulan berikutnya →</a>
            <a href="{{ route('audit.assignments.index') }}" class="sim-btn sim-btn-detail">Daftar penugasan</a>
        </div>
    </div>

    <div class="sim-card mb-6">
        <div class="sim-card-header flex items-center justify-between">
            <h3 class="sim-card-title">{{ $calendar['title'] }}</h3>
            <span class="text-xs text-sim-muted">{{ $calendar['assignments']->count() }} penugasan pada bulan ini</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] table-fixed border-collapse text-sm">
                <thead>
                    <tr>
                        @foreach ($calendar['weekday_labels'] as $label)
                            <th class="border-b border-sim-border-light bg-sim-surface-alt px-2 py-2 text-left text-xs font-semibold uppercase tracking-wide text-sim-muted">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($calendar['weeks'] as $week)
                        <tr>
                            @foreach ($week as $day)
                                <td class="h-28 align-top border border-sim-border-light p-1.5 {{ $day['in_month'] ? 'bg-white' : 'bg-sim-surface-alt/60' }} {{ $day['is_today'] ? 'ring-2 ring-inset ring-sim-primary' : '' }}">
                                    <div class="mb-1 flex items-center justify-between">
                                        <span class="text-xs font-semibold {{ $day['in_month'] ? 'text-sim-ink' : 'text-sim-muted' }}">{{ $day['day'] }}</span>
                                        @if ($day['events'])
                                            <span class="rounded-full bg-sim-primary/10 px-1.5 text-[10px] font-medium text-sim-primary">{{ count($day['events']) }}</span>
                                        @endif
                                    </div>
                                    <div class="space-y-1">
                                        @foreach (array_slice($day['events'], 0, 3) as $event)
                                            <a href="{{ $event['url'] }}" class="block truncate rounded px-1 py-0.5 text-[11px] leading-tight hover:bg-sim-hover
                                                {{ $event['status'] === 'active' ? 'bg-emerald-50 text-emerald-800' : ($event['status'] === 'completed' ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-800') }}">
                                                <span class="font-mono">{{ $event['code'] }}</span>
                                                <span class="text-sim-muted"> · {{ $event['clinic'] }}</span>
                                            </a>
                                        @endforeach
                                        @if (count($day['events']) > 3)
                                            <div class="text-[10px] text-sim-muted">+{{ count($day['events']) - 3 }} lainnya</div>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="sim-card">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Penugasan pada {{ $calendar['title'] }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="sim-table w-full">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Klinik</th>
                        <th>Jadwal</th>
                        <th>Ketua</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($calendar['assignments'] as $assignment)
                        <tr>
                            <td>
                                <div class="font-mono text-xs">{{ $assignment->assignment_code }}</div>
                                <div class="text-sm text-sim-ink">{{ $assignment->title }}</div>
                            </td>
                            <td>{{ $assignment->clinic?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap">
                                {{ $assignment->scheduled_start?->format('d M Y') }}
                                –
                                {{ $assignment->scheduled_end?->format('d M Y') }}
                            </td>
                            <td>{{ $assignment->leadAuditor?->name ?? '—' }}</td>
                            <td>{{ $assignment->status }}</td>
                            <td>
                                <x-action action="detail" href="{{ route('audit.assignments.show', $assignment) }}" icon="eye" label="Detail" size="sm" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="sim-table-empty">Tidak ada penugasan terjadwal pada bulan ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
