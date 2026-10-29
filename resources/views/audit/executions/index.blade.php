@extends('layouts.simmak')

@section('title', 'Pelaksanaan Audit — SIMMAK PPKP')
@section('page-title', 'Pelaksanaan Audit')
@section('page-subtitle', 'Antrian kerja auditor: isi nilai kesesuaian dan rekomendasi pada instrumen.')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span>Audit Internal</span>
    </li>
    <li class="flex items-center gap-1 text-sim-ink">
        @include('partials.icons.chevron-right')
        <span class="font-medium">Pelaksanaan Audit</span>
    </li>
@endsection

@section('content')
    @php
        $statusTone = [
            'preparation' => 'border-sim-neutral bg-sim-neutral-bg text-sim-neutral',
            'in_progress' => 'border-sim-info bg-sim-info-bg text-sim-info',
            'submitted_for_review' => 'border-sim-warning bg-sim-warning-bg text-sim-warning',
            'finalized' => 'border-sim-success bg-sim-success-bg text-sim-success',
        ];
        $statusLabels = [
            'preparation' => 'Persiapan',
            'in_progress' => 'Berjalan',
            'submitted_for_review' => 'Menunggu tinjauan',
            'finalized' => 'Final',
        ];
        $filterActive = request()->filled('status') || request()->filled('q');
        $summaryCards = [
            ['label' => 'Total Eksekusi', 'value' => $executionTotal, 'meta' => 'Sesuai lingkup akses Anda', 'tone' => 'text-sim-ink'],
            ['label' => 'Persiapan', 'value' => (int) ($statusCounts['preparation'] ?? 0), 'meta' => 'Belum dimulai auditor', 'tone' => 'text-sim-neutral'],
            ['label' => 'Berjalan', 'value' => (int) ($statusCounts['in_progress'] ?? 0), 'meta' => 'Sedang diisi kesesuaiannya', 'tone' => 'text-sim-info'],
            ['label' => 'Menunggu Tinjauan', 'value' => (int) ($statusCounts['submitted_for_review'] ?? 0), 'meta' => 'Sudah diserahkan auditor', 'tone' => 'text-sim-warning'],
            ['label' => 'Final', 'value' => (int) ($statusCounts['finalized'] ?? 0), 'meta' => 'Hasil audit ditetapkan', 'tone' => 'text-sim-success'],
        ];
    @endphp

    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">Pelaksanaan Audit</h2>
            <p class="sim-page-subtitle">
                Hanya penugasan yang Anda ikuti. Isi <strong>nilai kesesuaian</strong> dan <strong>rekomendasi</strong> pada instrumen;
                EP tidak sesuai otomatis menjadi temuan dan tindak lanjut klinik.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('audit.assignment.view')
                <x-action action="navigate" href="{{ route('audit.assignments.index') }}" icon="user-group" label="Penugasan Auditor" />
            @endcan
        </div>
    </div>

    {{-- Ringkasan status eksekusi --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        @foreach ($summaryCards as $card)
            <div class="sim-stat-card">
                <div class="sim-stat-label">{{ $card['label'] }}</div>
                <div class="sim-stat-value {{ $card['tone'] }}">{{ $card['value'] }}</div>
                <div class="sim-stat-meta">{{ $card['meta'] }}</div>
            </div>
        @endforeach
    </div>

    @can('audit.execution.execute')
        @if ($assignments->isNotEmpty())
            <div class="sim-card mb-6 border-l-4 border-sim-teal-600">
                <div class="sim-card-header">
                    <h3 class="sim-card-title">Buat Eksekusi Baru</h3>
                    <p class="mt-1 text-sm text-sim-muted">
                        Pilih penugasan aktif yang Anda ikuti beserta versi instrumen yang akan diperiksa.
                    </p>
                </div>
                <form method="POST" action="{{ route('audit.executions.store-selection') }}">
                    @csrf
                    <div class="sim-card-body">
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <label class="sim-form-label sim-form-label-required" for="audit_assignment_id">Penugasan Audit</label>
                                <select name="audit_assignment_id" id="audit_assignment_id" class="sim-form-select" required>
                                    <option value="">— Pilih penugasan —</option>
                                    @foreach ($assignments as $assignment)
                                        <option value="{{ $assignment->id }}" @selected(old('audit_assignment_id') === $assignment->id)>
                                            {{ $assignment->assignment_code }} — {{ $assignment->clinic?->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('audit_assignment_id')<p class="sim-form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="sim-form-label sim-form-label-required" for="instrument_version_id">Versi Instrumen</label>
                                <select name="instrument_version_id" id="instrument_version_id" class="sim-form-select" required>
                                    <option value="">— Pilih instrumen —</option>
                                    @foreach ($instrumentVersions as $version)
                                        <option value="{{ $version->id }}" @selected(old('instrument_version_id') === $version->id)>
                                            {{ $version->version_label }} ({{ $version->standard?->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('instrument_version_id')<p class="sim-form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="sim-form-label" for="execution_code">Kode Eksekusi</label>
                                <input type="text" id="execution_code" readonly tabindex="-1"
                                       value="{{ $suggestedExecutionCode ?? '' }}" class="sim-form-input bg-sim-surface-alt">
                                <p class="sim-form-hint">Dibuat otomatis saat data disimpan.</p>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-3">
                                <label class="sim-form-label sim-form-label-required" for="title">Judul Eksekusi</label>
                                <input type="text" name="title" id="title" value="{{ old('title') }}"
                                       class="sim-form-input" placeholder="Contoh: Audit Internal Klinik Pratama Jakarta Pusat 2026" required>
                                @error('title')<p class="sim-form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <p class="sim-form-hint">
                            Auditor hanya dapat memeriksa klinik/unit pada penugasan ini. Temuan dan Tindak Lanjut dibuat otomatis dari EP yang tidak sesuai.
                        </p>
                    </div>
                    <div class="sim-card-footer flex justify-end">
                        <x-action action="create" icon="plus" label="Buat Eksekusi" />
                    </div>
                </form>
            </div>
        @else
            <div class="sim-alert sim-alert-info mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">Belum ada penugasan aktif</p>
                    <p class="text-sim-muted">Buat dan aktifkan penugasan audit terlebih dahulu sebelum membuat eksekusi.</p>
                </div>
                @can('audit.assignment.view')
                    <x-action action="navigate" href="{{ route('audit.assignments.index') }}" icon="user-group" label="Kelola Penugasan" size="sm" />
                @endcan
            </div>
        @endif
    @else
        <div class="sim-alert sim-alert-info mb-6">
            <p class="font-semibold">Akses terbatas</p>
            <p class="text-sim-muted">
                Anda tidak memiliki izin membuat eksekusi audit; daftar di bawah hanya dapat dibaca.
                Hubungi administrator untuk izin <code class="sim-code">audit.execution.execute</code>.
            </p>
        </div>
    @endcan

    <div class="sim-card">
        <div class="sim-card-header flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="sim-card-title">Daftar Eksekusi Audit</h3>
                <p class="mt-1 text-sm text-sim-muted">
                    {{ $executions->total() }} eksekusi{{ $filterActive ? ' pada filter ini' : ' dalam lingkup akses Anda' }}.
                </p>
            </div>

            <form method="GET" action="{{ route('audit.executions.index') }}" class="sim-toolbar">
                <label class="sr-only" for="filter-q">Cari kode atau judul eksekusi</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-sim-muted">
                        @include('partials.icons.search')
                    </span>
                    <input type="search" name="q" id="filter-q" value="{{ request('q') }}"
                           class="sim-form-input pl-9" placeholder="Cari kode / judul…">
                </div>
                <label class="sr-only" for="filter-status">Status eksekusi</label>
                <select name="status" id="filter-status" class="sim-form-select" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="sim-btn sim-btn-neutral sim-btn-sm">Terapkan</button>
                @if ($filterActive)
                    <a href="{{ route('audit.executions.index') }}" class="sim-btn sim-btn-back sim-btn-sm">Reset</a>
                @endif
            </form>
        </div>

        <div class="sim-card-body overflow-x-auto p-0">
            <table class="sim-table w-full">
                <thead>
                    <tr>
                        <th class="w-32">Kode</th>
                        <th class="min-w-[240px]">Eksekusi</th>
                        <th class="w-40">Penugasan</th>
                        <th class="w-32">Periode</th>
                        <th class="w-44">Progres Pengisian</th>
                        <th class="w-36">Status</th>
                        <th class="w-32 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($executions as $execution)
                        @php
                            $criteriaTotal = 0;
                            if ($execution->instrumentVersion) {
                                $criteriaTotal = $execution->instrumentVersion->sections
                                    ->flatMap->criteria
                                    ->where('active', true)
                                    ->count();
                            }
                            $assessed = (int) $execution->assessed_responses_count;
                            $progress = $criteriaTotal > 0 ? (int) round(($assessed / $criteriaTotal) * 100) : 0;
                            $canExecuteRow = auth()->user()->can('execute', $execution);
                        @endphp
                        <tr>
                            <td class="text-sim-mono text-xs text-sim-muted">{{ $execution->execution_code }}</td>
                            <td>
                                <div class="text-sm font-medium text-sim-ink">{{ $execution->title }}</div>
                                <div class="mt-0.5 text-xs text-sim-muted">
                                    {{ $execution->clinic?->code ?? '—' }} — {{ $execution->clinic?->name ?? 'Klinik belum ditetapkan' }}
                                    · {{ $execution->instrumentVersion?->version_label ?? 'Tanpa instrumen' }}
                                </div>
                            </td>
                            <td class="text-xs text-sim-muted">
                                <div class="text-sim-mono">{{ $execution->assignment?->assignment_code ?? '—' }}</div>
                                @if ($execution->findings_count > 0)
                                    @can('audit.finding.view')
                                        <a href="{{ route('findings.index') }}" class="mt-1 inline-flex font-medium text-sim-warning hover:underline">
                                            {{ $execution->findings_count }} temuan
                                        </a>
                                    @else
                                        <span class="mt-1 inline-block font-medium text-sim-warning">{{ $execution->findings_count }} temuan</span>
                                    @endcan
                                @else
                                    <span class="mt-1 inline-block">Belum ada temuan</span>
                                @endif
                            </td>
                            <td class="text-xs text-sim-muted">
                                @if ($execution->started_at)
                                    <div>{{ $execution->started_at->format('d M Y') }}</div>
                                    <div>mulai audit</div>
                                @else
                                    <span>Belum dimulai</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="sim-progress w-20">
                                        <div class="sim-progress-bar {{ $progress >= 100 ? 'sim-progress-bar-success' : '' }}" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <span class="text-sim-mono text-xs text-sim-muted">{{ $assessed }}/{{ $criteriaTotal }}</span>
                                </div>
                                <div class="mt-1 text-xs text-sim-muted">EP dinilai</div>
                            </td>
                            <td>
                                <span class="sim-badge sim-badge-status {{ $statusTone[$execution->status] ?? 'border-sim-border bg-sim-surface text-sim-muted' }}">
                                    {{ $statusLabels[$execution->status] ?? $execution->status }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="sim-action-group">
                                    @if ($canExecuteRow && $execution->status === 'in_progress')
                                        <x-action action="edit" href="{{ route('audit.executions.checklist', $execution) }}" icon="clipboard-check" label="Isi Tabel" size="sm" />
                                    @elseif ($canExecuteRow && in_array($execution->status, ['submitted_for_review', 'finalized'], true))
                                        <x-action action="edit" href="{{ route('audit.executions.checklist', $execution) }}" icon="pencil" label="Koreksi" size="sm" />
                                    @else
                                        <x-action action="detail" href="{{ route('audit.executions.checklist', $execution) }}" icon="eye" label="Detail" size="sm" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="sim-empty-state">
                                    <div class="sim-empty-icon">
                                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002-2h1a2 2 0 012 2v2a2 2 0 01-2 2H9a2 2 0 01-2-2V7z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 14l2 2 4-4"></path></svg>
                                    </div>
                                    <p class="sim-empty-title">
                                        {{ $filterActive ? 'Tidak ada eksekusi pada filter ini' : 'Belum ada eksekusi audit' }}
                                    </p>
                                    <p class="sim-empty-description">
                                        {{ $filterActive
                                            ? 'Ubah kata kunci atau status, atau reset filter untuk melihat seluruh eksekusi.'
                                            : 'Eksekusi dibuat dari penugasan auditor yang aktif dan versi instrumen yang disetujui.' }}
                                    </p>
                                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                                        @if ($filterActive)
                                            <x-action action="cancel" href="{{ route('audit.executions.index') }}" icon="x-mark" label="Reset Filter" size="sm" />
                                        @endif
                                        @can('audit.assignment.view')
                                            <x-action action="detail" href="{{ route('audit.assignments.index') }}" icon="user-group" label="Lihat Penugasan" size="sm" />
                                        @endcan
                                    </div>
                                </div>
                            </td>
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

