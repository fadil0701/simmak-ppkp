@extends('layouts.simmak')

@section('title', 'Audit Klinis — SIMMAK PPKP')
@section('page-title', 'Audit Klinis')
@section('page-subtitle', 'Penilaian kesesuaian pelayanan klinis terhadap standar/kriteria; temuannya masuk ke modul Temuan Audit bersama.')

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">Audit Klinis</h2>
            <p class="sim-page-subtitle">Daftar audit klinis per klinik/unit. Tindak lanjut memakai modul Tindak Lanjut yang sama dengan audit internal.</p>
        </div>

        @can('audit.clinical.manage')
            <x-action action="create" href="{{ route('audit.clinical.create') }}" icon="plus" label="Tambah Audit Klinis" />
        @endcan
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6 mb-6">
        @foreach ([
            'total' => 'Total Audit',
            'draft' => 'Draf',
            'in_progress' => 'Berjalan',
            'completed' => 'Selesai',
            'closed' => 'Ditutup',
            'non_conform' => 'Elemen Tidak Sesuai',
        ] as $key => $label)
            <div class="sim-card">
                <div class="sim-card-body p-4">
                    <div class="text-xs text-sim-muted">{{ $label }}</div>
                    <div class="mt-1 text-xl font-semibold text-sim-ink">{{ $stats[$key] ?? 0 }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="sim-card">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Daftar Audit Klinis</h3>
        </div>

        <div class="sim-card-body border-b border-sim-border-light">
            <form method="GET" action="{{ route('audit.clinical.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nomor, judul, ruang lingkup…"
                       class="sim-form-input lg:col-span-2" aria-label="Cari audit klinis">

                <select name="status" class="sim-form-select">
                    <option value="">Semua Status</option>
                    @foreach (['draft', 'in_progress', 'completed', 'closed'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                            {{ \App\Support\UiLabel::clinicalAuditStatus($status) }}
                        </option>
                    @endforeach
                </select>

                <select name="clinic_id" class="sim-form-select">
                    <option value="">Semua Klinik</option>
                    @foreach ($clinics as $clinic)
                        <option value="{{ $clinic->id }}" {{ request('clinic_id') === $clinic->id ? 'selected' : '' }}>
                            {{ $clinic->name }}
                        </option>
                    @endforeach
                </select>

                <input type="date" name="from" value="{{ request('from') }}" class="sim-form-input" aria-label="Tanggal dari">
                <input type="date" name="to" value="{{ request('to') }}" class="sim-form-input" aria-label="Tanggal sampai">

                <div class="flex gap-2 lg:col-span-6">
                    <x-action action="save" icon="check-circle" label="Filter" size="sm" />
                    <x-action action="back" href="{{ route('audit.clinical.index') }}" icon="chevron-left" label="Reset" size="sm" />
                </div>
            </form>
        </div>

        <div class="sim-card-body overflow-x-auto">
            <table class="sim-table w-full">
                <thead>
                    <tr>
                        <th class="w-32">Nomor</th>
                        <th>Judul</th>
                        <th class="w-40">Klinik/Unit</th>
                        <th class="w-28 text-center">Tanggal</th>
                        <th class="w-36">Auditor / PIC</th>
                        <th class="w-24 text-center">Elemen</th>
                        <th class="w-24 text-center">Temuan</th>
                        <th class="w-24 text-center">Status</th>
                        <th class="w-24 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sim-border-light">
                    @forelse ($audits as $audit)
                        <tr>
                            <td class="text-sim-mono text-xs text-sim-muted">{{ $audit->audit_number }}</td>
                            <td class="text-sm font-medium text-sim-ink">{{ $audit->title }}</td>
                            <td class="text-sm text-sim-muted">{{ $audit->clinic?->name ?? '—' }}</td>
                            <td class="text-center text-sm text-sim-muted">{{ $audit->audit_date?->format('d M Y') ?? '—' }}</td>
                            <td class="text-xs text-sim-muted">
                                {{ $audit->auditor?->name ?? '—' }}
                                <div class="text-[11px]">PIC: {{ $audit->pic?->name ?? '—' }}</div>
                            </td>
                            <td class="text-center text-sim-mono text-xs">{{ $audit->items_count }}</td>
                            <td class="text-center text-sim-mono text-xs">{{ $audit->findings_count }}</td>
                            <td class="text-center">
                                <span class="sim-badge sim-badge-status
                                    @switch($audit->status)
                                        @case('completed') border-sim-success bg-sim-success-bg text-sim-success
                                        @case('in_progress') border-sim-info bg-sim-info-bg text-sim-info
                                        @case('closed') border-sim-neutral bg-sim-neutral-bg text-sim-neutral
                                        @default border-sim-warning bg-sim-warning-bg text-sim-warning
                                    @endswitch
                                ">
                                    {{ \App\Support\UiLabel::clinicalAuditStatus($audit->status) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="sim-action-group">
                                    <x-action action="detail" href="{{ route('audit.clinical.show', $audit) }}" icon="file-text" label="Detail" size="sm" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="sim-table-empty">Belum ada audit klinis.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($audits->hasPages())
            <div class="sim-card-footer">{{ $audits->links() }}</div>
        @endif
    </div>
@endsection

