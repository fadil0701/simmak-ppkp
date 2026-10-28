@extends('layouts.simmak')

@section('title', 'Temuan Audit — SIMMAK PPKP')
@section('page-title', 'Temuan Audit')
@section('page-subtitle', 'Kelola temuan ketidaksesuaian, observasi, dan peluang perbaikan dari hasil audit internal')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span>Temuan Audit</span>
    </li>
@endsection

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">Temuan Audit</h2>
            <p class="sim-page-subtitle">Temuan dikategorikan berdasarkan tingkat risiko dan status tindak lanjut. Temuan risiko tinggi memerlukan perhatian segera.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-action action="export" href="{{ route('findings.export', request()->query()) }}" icon="download" label="Ekspor CSV" />
            @can('audit.finding.create')
                <x-action action="create" href="{{ route('findings.create') }}" icon="plus" label="Tambah Temuan" />
            @endcan
        </div>
    </div>

    <div class="sim-card">
        <div class="sim-card-header flex items-center justify-between">
            <div>
                <h3 class="sim-card-title">Daftar Temuan</h3>
            </div>

            <div class="flex gap-2 flex-wrap">
                <form method="GET" class="flex gap-2">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="severity" value="{{ request('severity') }}">
                    <input type="hidden" name="clinic_id" value="{{ request('clinic_id') }}">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode, judul, uraian…"
                           class="sim-form-input sim-btn-sm w-56" aria-label="Cari temuan">
                    <x-action action="save" icon="check-circle" label="Cari" size="sm" />
                </form>
                <form method="GET" class="flex gap-2">
                    <input type="hidden" name="q" value="{{ request('q') }}">
                    <input type="hidden" name="severity" value="{{ request('severity') }}">
                    <input type="hidden" name="clinic_id" value="{{ request('clinic_id') }}">
                    <select name="status" class="sim-form-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        @foreach (['draft', 'under_review', 'confirmed', 'in_capa', 'pending_verification', 'closed'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                                {{ \App\Support\UiLabel::findingStatus($s) }}
                            </option>
                        @endforeach
                    </select>
                </form>
                <form method="GET" class="flex gap-2">
                    <input type="hidden" name="q" value="{{ request('q') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="clinic_id" value="{{ request('clinic_id') }}">
                    <select name="severity" class="sim-form-select" onchange="this.form.submit()">
                        <option value="">Semua Tingkat Risiko</option>
                        @foreach (['critical', 'major', 'minor', 'observation'] as $s)
                            <option value="{{ $s }}" {{ request('severity') === $s ? 'selected' : '' }}>
                                {{ \App\Support\UiLabel::findingSeverity($s) }}
                            </option>
                        @endforeach
                    </select>
                </form>
                <form method="GET" class="flex gap-2">
                    <input type="hidden" name="q" value="{{ request('q') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="severity" value="{{ request('severity') }}">
                    <select name="clinic_id" class="sim-form-select" onchange="this.form.submit()">
                        <option value="">Semua Klinik</option>
                        @foreach ($clinics as $c)
                            <option value="{{ $c->id }}" {{ request('clinic_id') === $c->id ? 'selected' : '' }}>
                                {{ $c->code }} — {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
                @if ($stats['escalated'] > 0)
                    <form method="GET" class="flex items-center">
                        <input type="hidden" name="q" value="{{ request('q') }}">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <input type="hidden" name="severity" value="{{ request('severity') }}">
                        <input type="hidden" name="clinic_id" value="{{ request('clinic_id') }}">
                        <label class="inline-flex items-center gap-2 text-sm text-sim-muted cursor-pointer select-none">
                            <input type="checkbox" name="escalated" value="1" class="rounded border-sim-border" {{ request('escalated') ? 'checked' : '' }} onchange="this.form.submit()">
                            <span class="inline-flex items-center gap-1">
                                <svg class="h-3.5 w-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Eskalasi ({{ $stats['escalated'] }})
                            </span>
                        </label>
                    </form>
                @endif
            </div>
        </div>

        <div class="sim-card-body overflow-x-auto">
            <table class="sim-table w-full">
                <thead>
                    <tr>
                        <th class="w-24">Kode Temuan</th>
                        <th>Judul</th>
                        <th class="w-32">Klinik</th>
                        <th class="w-16 text-center">Tipe</th>
                        <th class="w-20 text-center">Risiko</th>
                        <th class="w-20 text-center">Status</th>
                        <th class="w-24">PIC</th>
                        <th class="w-20 text-center">Tenggat</th>
                        <th class="w-32 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sim-border-light">
                    @forelse ($findings as $finding)
                        <tr>
                            <td class="text-sim-mono text-xs text-sim-muted">{{ $finding->finding_code }}</td>
                            <td class="text-sm font-medium text-sim-ink">{{ $finding->title }}</td>
                            <td class="text-sm text-sim-muted">{{ $finding->clinic?->code }} — {{ $finding->clinic?->name }}</td>
                            <td class="text-center text-xs text-sim-muted text-sim-mono">{{ ucfirst(str_replace('_', ' ', $finding->finding_type ?? '-')) }}</td>
                            <td class="text-center">
                                <span class="sim-badge sim-badge-status
                                    @switch($finding->severity)
                                        @case('critical') border-sim-danger bg-sim-danger-bg text-sim-danger
                                        @case('major') border-sim-warning bg-sim-warning-bg text-sim-warning
                                        @case('minor') border-sim-info bg-sim-info-bg text-sim-info
                                        @default border-sim-success bg-sim-success-bg text-sim-success
                                    @endswitch
                                ">
                                    {{ \App\Support\UiLabel::findingSeverity($finding->severity) }}
                                </span>
                                @if ($finding->is_escalated)
                                    <span class="sim-badge border-red-300 bg-red-100 text-red-700 mt-1 inline-flex items-center gap-1">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        Eskalasi
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="sim-badge sim-badge-status
                                    @switch($finding->status)
                                        @case('closed') border-sim-success bg-sim-success-bg text-sim-success
                                        @case('draft') border-sim-neutral bg-sim-neutral-bg text-sim-neutral
                                        @default border-sim-info bg-sim-info-bg text-sim-info
                                    @endswitch
                                ">
                                    {{ \App\Support\UiLabel::findingStatus($finding->status) }}
                                </span>
                            </td>
                            <td class="text-sm text-sim-muted">{{ $finding->owner?->name ?? $finding->assignee?->name ?? '—' }}</td>
                            <td class="text-center text-sim-mono text-xs">{{ $finding->due_at?->format('d M Y') ?? '—' }}</td>
                            <td class="text-right">
                                <div class="sim-action-group">
                                    <x-action action="detail" href="{{ route('findings.show', $finding) }}" icon="file-text" label="Detail" size="sm" />
                                    @can('audit.finding.update', $finding)
                                        <x-action action="edit" href="{{ route('findings.edit', $finding) }}" icon="pencil" label="Edit" size="sm" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="sim-table-empty">Belum ada temuan audit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($findings->hasPages())
            <div class="sim-card-footer">
                {{ $findings->links() }}
            </div>
        @endif
    </div>
@endsection