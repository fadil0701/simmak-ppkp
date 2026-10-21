@extends('layouts.simmak')

@section('title', '{{ $program->program_code }} — SIMMAK PPKP')
@section('page-title', 'Detail Program: {{ $program->program_code }}')

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">{{ $program->program_code }} — {{ $program->name }}</h2>
            @if ($program->objective)
                <p class="sim-page-subtitle">{{ $program->objective }}</p>
            @endif
        </div>

        <div class="flex gap-2">
            @can('audit.program.update', $program)
                <a href="{{ route('audit.programs.edit', $program) }}" class="sim-btn sim-btn-primary sim-btn-sm">
                    Sunting
                </a>
            @endcan
            @can('audit.program.submit', $program)
                @if ($program->status === 'draft')
                    <form method="POST" action="{{ route('audit.programs.submit', $program) }}" class="inline" onsubmit="return confirm('Yakin ingin mengajukan program ini untuk tinjauan?');">
                        @csrf
                        <button type="submit" class="sim-btn sim-btn-primary sim-btn-sm">
                            Ajukan Tinjauan
                        </button>
                    </form>
                @endif
            @endcan
            @can('audit.program.approve', $program)
                @if ($program->status === 'submitted_for_review')
                    <form method="POST" action="{{ route('audit.programs.approve', $program) }}" class="inline" onsubmit="return confirm('Yakin ingin menyetujui program ini?');">
                        @csrf
                        <button type="submit" class="sim-btn sim-btn-primary sim-btn-sm">
                            Setujui
                        </button>
                    </form>
                    <form method="POST" action="{{ route('audit.programs.return', $program) }}" class="inline" onsubmit="return confirm('Kembalikan untuk revisi?');">
                        @csrf
                        <input type="hidden" name="reason" value="Perlu revisi">
                        <button type="submit" class="sim-btn sim-btn-secondary sim-btn-sm">
                            Kembalikan
                        </button>
                    </form>
                @endif
            @endcan
            <a href="{{ route('audit.programs.index') }}" class="sim-btn sim-btn-outline">
                Kembali
            </a>
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
@endsection
