@extends('layouts.simmak')

@section('title', 'Audit Internal — SIMMAK PPKP')
@section('page-title', 'Audit Internal')

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Program Audit', $stats['programs'], 'blue'], ['Terjadwal', $stats['scheduled'], 'indigo'], ['Berjalan', $stats['ongoing'], 'amber'], ['Selesai', $stats['completed'], 'emerald']] as [$label, $val, $color])
            <div class="simmak-card p-5">
                <div class="text-sm text-slate-500">{{ $label }}</div>
                <div class="mt-1 text-3xl font-bold text-{{ $color }}-600">{{ $val }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="font-semibold text-slate-800">Program Audit</h3>
            </div>
            <div class="simmak-card-body space-y-3">
                @forelse ($programs as $program)
                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="font-mono text-xs text-slate-500">{{ $program->program_code }}</div>
                                <div class="font-semibold text-slate-800">{{ $program->name }}</div>
                                <p class="mt-1 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($program->objective, 100) }}</p>
                            </div>
                            <span class="simmak-badge bg-teal-100 text-teal-800">{{ ucfirst($program->status) }}</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-500">{{ $program->schedules_count }} jadwal audit</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada program audit.</p>
                @endforelse
            </div>
        </div>

        <div class="simmak-card">
            <div class="simmak-card-header flex items-center justify-between">
                <h3 class="font-semibold text-slate-800">Jadwal Audit</h3>
                <div class="flex gap-3">
                    <a href="{{ route('audit.reports.index') }}" class="text-sm font-medium text-teal-700 hover:underline">Laporan Audit</a>
                    <a href="{{ route('findings.index') }}" class="text-sm font-medium text-teal-700 hover:underline">Lihat Temuan →</a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Judul / Klinik</th>
                            <th class="px-4 py-3">Periode</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Temuan</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($schedules as $schedule)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono text-xs">{{ $schedule->schedule_code }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">{{ $schedule->title }}</div>
                                    <div class="text-xs text-slate-500">{{ $schedule->clinic?->name ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    {{ $schedule->scheduled_start->format('d M') }} – {{ $schedule->scheduled_end->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    @php $sc = match($schedule->status) { 'completed' => 'bg-emerald-100 text-emerald-800', 'ongoing' => 'bg-amber-100 text-amber-800', default => 'bg-blue-100 text-blue-800' }; @endphp
                                    <span class="simmak-badge {{ $sc }}">{{ ucfirst($schedule->status) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center font-medium">{{ $schedule->findings_count }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('audit.checklist', $schedule) }}" class="text-xs font-medium text-teal-700 hover:underline">Checklist</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada jadwal.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($schedules->hasPages())
                <div class="border-t border-slate-100 px-4 py-3">{{ $schedules->links() }}</div>
            @endif
        </div>
    </div>
@endsection
