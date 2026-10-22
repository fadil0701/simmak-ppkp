@extends('layouts.simmak')

@section('title', 'Pelaksanaan Audit — SIMMAK PPKP')
@section('page-title', 'Pelaksanaan Audit')
@section('page-subtitle', 'Daftar eksekusi audit yang telah dibuat. Klik nama eksekusi untuk mengisi checklist.')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span>Pelaksanaan Audit</span>
    </li>
@endsection

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">Pelaksanaan Audit</h2>
            <p class="sim-page-subtitle">Daftar eksekusi audit per klinik. Pilih eksekusi untuk mengisi checklist dan mengunggah bukti.</p>
        </div>

        @can('audit.execution.execute')
            @php
                $assignments = \App\Models\AuditAssignment::where('organization_id', auth()->user()->organization_id)
                    ->where('status', 'active')
                    ->with('clinic')
                    ->get();
            @endphp
            @if ($assignments->isNotEmpty())
                @php
                    $instrumentVersions = \App\Models\InstrumentVersion::whereHas('standard', fn ($q) => $q->where('organization_id', auth()->user()->organization_id))
                        ->whereIn('status', ['active', 'approved'])
                        ->orderBy('version_label')
                        ->get();
                @endphp
                <div class="sim-card p-4 mt-4 border-l-4 border-sim-primary">
                    <h3 class="sim-card-title text-sm">Buat Eksekusi Baru</h3>
                    <p class="text-xs text-sim-muted mb-3">Pilih penugasan aktif dan versi instrumen untuk memulai pelaksanaan audit.</p>
                    <form method="POST" action="{{ route('audit.executions.store-selection') }}" class="space-y-3">
                        @csrf
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <label class="sim-form-label">Penugasan Audit</label>
                                <select name="audit_assignment_id" class="sim-form-input" required>
                                    <option value="">— Pilih penugasan —</option>
                                    @foreach ($assignments as $assignment)
                                        <option value="{{ $assignment->id }}">{{ $assignment->assignment_code }} — {{ $assignment->clinic?->name }}</option>
                                    @endforeach
                                </select>
                                @error('audit_assignment_id')<p class="sim-form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="sim-form-label">Versi Instrumen</label>
                                <select name="instrument_version_id" class="sim-form-input" required>
                                    <option value="">— Pilih instrumen —</option>
                                    @foreach ($instrumentVersions as $version)
                                        <option value="{{ $version->id }}">{{ $version->version_label }} ({{ $version->standard?->code }})</option>
                                    @endforeach
                                </select>
                                @error('instrument_version_id')<p class="sim-form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="sim-form-label">Kode Eksekusi</label>
                                <input type="text" name="execution_code" value="{{ old('execution_code', 'EXEC-'.date('Ymd')) }}" class="sim-form-input" required>
                                @error('execution_code')<p class="sim-form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2 lg:col-span-3">
                                <label class="sim-form-label">Judul Eksekusi</label>
                                <input type="text" name="title" value="{{ old('title') }}" class="sim-form-input" required>
                                @error('title')<p class="sim-form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <x-action icon="plus" label="Buat Eksekusi" />
                        </div>
                    </form>
                </div>
            @else
                <div class="sim-card p-4 mt-4 border-l-4 border-sim-warning bg-sim-warning/5">
                    <div class="sim-card-title text-sm">Tidak ada penugasan aktif</div>
                    <p class="text-xs text-sim-muted mb-2">Buat dan aktifkan penugasan audit terlebih dahulu sebelum membuat eksekusi.</p>
                    <x-action href="{{ route('audit.assignments.index') }}" icon="user-group" label="Kelola Penugasan" variant="outline" size="sm" />
                </div>
            @endif
        @else
            <div class="sim-card p-4 mt-4 border-l-4 border-sim-info bg-sim-info/5">
                <div class="sim-card-title text-sm">Akses Terbatas</div>
                <p class="text-xs text-sim-muted">Anda tidak memiliki izin untuk membuat eksekusi audit. Hubungi administrator untuk izin <code>audit.execution.execute</code>.</p>
            </div>
        @endcan
    </div>

    <div class="sim-card mt-6">
        <div class="sim-card-header flex items-center justify-between">
            <div>
                <h3 class="sim-card-title">Daftar Eksekusi Audit</h3>
            </div>
        </div>
        <div class="sim-card-body overflow-x-auto">
            <table class="sim-table w-full">
                <thead>
                    <tr>
                        <th class="w-24">Kode Eksekusi</th>
                        <th>Judul</th>
                        <th class="w-32">Klinik</th>
                        <th class="w-20 text-center">Status</th>
                        <th class="w-24 text-center">Instrument</th>
                        <th class="w-32 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sim-border-light">
                    @forelse ($executions as $execution)
                        <tr>
                            <td class="text-sim-mono text-xs text-sim-muted">{{ $execution->execution_code }}</td>
                            <td class="text-sm font-medium text-sim-ink">{{ $execution->title }}</td>
                            <td class="text-sm text-sim-muted">{{ $execution->clinic?->code }} — {{ $execution->clinic?->name }}</td>
                            <td class="text-center">
                                <span class="sim-badge sim-badge-status
                                    @switch($execution->status)
                                        @case('preparation') border-sim-neutral bg-sim-neutral-bg text-sim-neutral
                                        @case('in_progress') border-sim-info bg-sim-info-bg text-sim-info
                                        @case('submitted_for_review') border-sim-warning bg-sim-warning-bg text-sim-warning
                                        @case('finalized') border-sim-success bg-sim-success-bg text-sim-success
                                        @default border-sim-border bg-sim-surface text-sim-muted
                                    @endswitch
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $execution->status)) }}
                                </span>
                            </td>
                            <td class="text-center text-sim-mono text-xs">{{ $execution->instrumentVersion?->version_label ?? '—' }}</td>
                            <td class="text-right">
                                <div class="sim-action-group">
                                    <x-action href="{{ route('audit.executions.checklist', $execution) }}" icon="clipboard-check" label="Isi Tabel" variant="outline" size="sm" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="sim-table-empty">Belum ada eksekusi audit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($executions->hasPages())
            <div class="sim-card-footer">
                {{ $executions->links() }}
            </div>
        @endif
    </div>
@endsection