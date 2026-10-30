@extends('layouts.simmak')

@section('title', $program->program_code . ' — SIMMAK PPKP')
@section('page-title', 'Detail Program: ' . $program->program_code)

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">{{ $program->program_code }} — {{ $program->name }}</h2>
            @if ($program->objective)
                <p class="sim-page-subtitle">{{ $program->objective }}</p>
            @endif
        </div>

        <div class="flex gap-2 flex-wrap">
            @can('audit.program.update', $program)
                <x-action action="edit" href="{{ route('audit.programs.edit', $program) }}" icon="pencil" label="Edit" size="sm" />
            @endcan
            @can('audit.program.submit', $program)
                @if ($program->status === 'draft')
                    <x-action action="upload" href="{{ route('audit.programs.submit', $program) }}" icon="upload" label="Ajukan Tinjauan" size="sm"
                              method="POST" confirm="Ajukan program ini untuk tinjauan?" />
                @endif
            @endcan
            @can('audit.program.approve', $program)
                @if ($program->status === 'submitted_for_review')
                    <x-action action="approve" href="{{ route('audit.programs.approve', $program) }}" icon="check-circle" label="Setujui" size="sm"
                              method="POST" confirm="Setujui program ini?" />
                    <x-action action="back" href="{{ route('audit.programs.return', $program) }}" icon="x-mark" label="Kembalikan" size="sm"
                              method="POST" confirm="Kembalikan program ini untuk revisi?" :fields="['reason' => 'Perlu revisi']" />
                @endif
            @endcan
            <x-action action="back" href="{{ route('audit.programs.index') }}" icon="chevron-left" label="Kembali" size="sm" />
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="sim-card lg:col-span-2">
            <div class="sim-card-header flex items-center justify-between">
                <h3 class="sim-card-title">Jadwal Audit</h3>
            </div>
            <div class="sim-card-body space-y-3">
                @forelse ($program->schedules as $schedule)
                    <div class="border border-sim-border-light rounded-lg p-4 hover:bg-sim-hover">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <div class="font-mono text-xs text-sim-muted">{{ $schedule->schedule_code }}</div>
                                <div class="font-medium text-sim-ink">{{ $schedule->title }}</div>
                                <div class="text-xs text-sim-muted">{{ $schedule->clinic?->name ?? '—' }}</div>
                            </div>
                            <span class="sim-badge sim-badge-status {{ $schedule->status === 'completed' ? 'border-sim-success bg-sim-success-bg text-sim-success' : ($schedule->status === 'ongoing' ? 'border-sim-warning bg-sim-warning-bg text-sim-warning' : 'border-sim-info bg-sim-info-bg text-sim-info') }}">
                                {{ ucfirst($schedule->status) }}
                            </span>
                        </div>
                        <div class="text-sm text-sim-muted space-y-1">
                            <div class="flex justify-between">
                                <span class="text-xs text-sim-muted">Tanggal</span>
                                <span>{{ $schedule->scheduled_start->format('d M Y') }} – {{ $schedule->scheduled_end->format('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-sim-muted">Belum ada jadwal audit untuk program ini.</p>
                @endforelse
            </div>
        </div>

        <div class="sim-card lg:col-span-1">
            <div class="sim-card-header">
                <h3 class="sim-card-title">Informasi</h3>
            </div>
            <div class="sim-card-body space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-sim-muted">Status</span>
                    <span class="sim-badge sim-badge-status">{{ \App\Support\UiLabel::auditStatus($program->status) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-sim-muted">Dibuat oleh</span>
                    <span>{{ $program->creator?->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-sim-muted">Dibuat pada</span>
                    <span>{{ $program->created_at?->format('d M Y H:i') ?? '—' }}</span>
                </div>
                @if ($program->approved_by)
                    <div class="flex justify-between text-sm">
                        <span class="text-sim-muted">Disetujui oleh</span>
                        <span class="text-sim-muted">—</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6">
        @include('audit.programs._document-list', [
            'documents' => $program->documents,
        ])
    </div>
@endsection
