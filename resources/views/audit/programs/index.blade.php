@extends('layouts.simmak')

@section('title', 'Program Audit — SIMMAK PPKP')
@section('page-title', 'Program Audit')
@section('page-subtitle', 'Perencanaan audit internal tahunan dan jadwal audit per klinik')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span>Program Audit</span>
    </li>
@endsection

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">Program Audit</h2>
            <p class="sim-page-subtitle">Kelola program audit tahunan dan jadwal audit per klinik. Program harus disetujui sebelum dapat dijadwalkan.</p>
        </div>

        @can('audit.program.create')
            <x-action href="{{ route('audit.programs.create') }}" icon="plus" label="Tambah Program" />
        @endcan
    </div>

    <div class="sim-card">
        <div class="sim-card-header flex items-center justify-between">
            <div>
                <h3 class="sim-card-title">Daftar Program Audit</h3>
            </div>

            <div class="flex gap-2 flex-wrap">
                <form method="GET" class="flex gap-2">
                    <input type="hidden" name="audit_type" value="{{ request('audit_type') }}">
                    <select name="status" class="sim-form-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        @foreach (['draft', 'submitted_for_review', 'approved', 'returned_for_revision', 'scheduled', 'cancelled'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                                {{ \App\Support\UiLabel::auditStatus($s) }}
                            </option>
                        @endforeach
                    </select>
                </form>
                <form method="GET" class="flex gap-2">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <select name="audit_type" class="sim-form-select" onchange="this.form.submit()">
                        <option value="">Semua Jenis</option>
                        <option value="periodic" {{ request('audit_type') === 'periodic' ? 'selected' : '' }}>Periodik</option>
                        <option value="thematic" {{ request('audit_type') === 'thematic' ? 'selected' : '' }}>Tematik</option>
                        <option value="special" {{ request('audit_type') === 'special' ? 'selected' : '' }}>Khusus</option>
                        <option value="follow_up" {{ request('audit_type') === 'follow_up' ? 'selected' : '' }}>Ulang</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="sim-card-body overflow-x-auto">
            <table class="sim-table w-full">
                <thead>
                    <tr>
                        <th class="w-24">Kode Program</th>
                        <th>Judul</th>
                        <th class="w-24 text-center">Jenis</th>
                        <th class="w-40 text-center">Tujuan</th>
                        <th class="w-20 text-center">Tahun</th>
                        <th class="w-20 text-center">Status</th>
                        <th class="w-16 text-center">Jadwal</th>
                        <th class="w-32 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sim-border-light">
                    @forelse ($programs as $program)
                        <tr>
                            <td class="text-sim-mono text-xs text-sim-muted">{{ $program->program_code }}</td>
                            <td class="text-sm font-medium text-sim-ink">{{ $program->name }}</td>
                            <td class="text-center text-xs text-sim-muted">
                                {{ \App\Support\UiLabel::auditType($program->audit_type) ?? '—' }}
                            </td>
                            <td class="text-sm text-sim-muted">{{ \Illuminate\Support\Str::limit($program->objective, 60) }}</td>
                            <td class="text-sm text-center text-sim-muted">
                                {{ optional($program->fiscalYear)->name ?? '—' }}
                            </td>
                            <td class="text-center">
                                <span class="sim-badge sim-badge-status
                                    @switch($program->status)
                                        @case('approved') border-sim-success bg-sim-success-bg text-sim-success
                                        @case('draft') border-sim-neutral bg-sim-neutral-bg text-sim-neutral
                                        @case('submitted_for_review') border-sim-info bg-sim-info-bg text-sim-info
                                        @case('returned_for_revision') border-sim-warning bg-sim-warning-bg text-sim-warning
                                        @default border-sim-border bg-sim-surface text-sim-muted
                                    @endswitch
                                ">
                                    {{ \App\Support\UiLabel::auditStatus($program->status) }}
                                </span>
                            </td>
                            <td class="text-center text-sim-mono text-xs">{{ $program->schedules_count }}</td>
                            <td class="text-right">
                                <div class="sim-action-group">
                                    <x-action href="{{ route('audit.programs.show', $program) }}" icon="file-text" label="Lihat" variant="outline" size="sm" />
                                    @can('audit.program.update', $program)
                                        <x-action href="{{ route('audit.programs.edit', $program) }}" icon="pencil" label="Ubah" variant="secondary" size="sm" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="sim-table-empty">Belum ada program audit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($programs->hasPages())
            <div class="sim-card-footer">
                {{ $programs->links() }}
            </div>
        @endif
    </div>
@endsection