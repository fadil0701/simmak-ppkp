@extends('layouts.simmak')

@section('title', 'Standar & Instrumen Audit — SIMMAK PPKP')
@section('page-title', 'Standar & Instrumen Audit')

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">Standar & Instrumen Audit</h2>
            <p class="sim-page-subtitle">Kelola standar acuan dan versi instrumen yang digunakan dalam audit internal.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('audit.regulatory.index') }}" class="sim-btn sim-btn-back inline-flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Register Sumber Regulasi
            </a>

            @can('audit.standard.manage')
                <a href="{{ route('audit.standards.create') }}" class="sim-btn sim-btn-create inline-flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Tambah Standar
                </a>
            @endcan
        </div>
    </div>

    <div class="sim-card">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Daftar Standar Audit</h3>
        </div>
        <div class="sim-card-body overflow-x-auto">
            <table class="sim-table w-full text-left text-sm">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Judul Standar</th>
                        <th>Sumber Regulasi</th>
                        <th class="text-center">Versi</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($standards as $standard)
                        <tr>
                            <td class="font-mono text-xs text-sim-muted">{{ $standard->code }}</td>
                            <td>
                                <div class="font-medium text-sim-ink">{{ $standard->title }}</div>
                            </td>
                            <td class="text-sim-muted">
                                {{ $standard->sourceDocument?->document_number ?? '—' }}
                            </td>
                            <td class="text-center">
                                <span class="text-xs font-medium text-sim-muted">{{ $standard->instrumentVersions->count() }}</span>
                            </td>
                            <td class="text-center">
                                <span class="sim-badge sim-badge-status {{ \App\Support\UiLabel::standardStatusTone($standard->status) }}">
                                    {{ \App\Support\UiLabel::standardStatus($standard->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="sim-action-group">
                                    <x-action action="detail" href="{{ route('audit.standards.show', $standard) }}" icon="file-text" label="Detail" size="sm" />
                                    @can('audit.standard.update', $standard)
                                        <x-action action="edit" href="{{ route('audit.standards.edit', $standard) }}" icon="pencil" label="Edit" size="sm" />
                                    @endcan
                                    @can('audit.standard.manage')
                                        @if ($standard->status !== 'active')
                                            <x-action action="approve" :href="route('audit.standards.status', $standard)" method="PATCH"
                                                      :fields="['status' => 'active']" icon="check-circle"
                                                      label="Aktifkan" size="sm" />
                                        @else
                                            <x-action action="cancel" :href="route('audit.standards.status', $standard)" method="PATCH"
                                                      :fields="['status' => 'inactive']" icon="x-mark"
                                                      label="Nonaktifkan" size="sm" />
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="sim-table-empty">Belum ada standar audit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
