@extends('layouts.simmak')

@section('title', 'Master Unit/Klinik — SIMMAK PPKP')
@section('page-title', 'Master Unit/Klinik')

@section('content')
    @if (session('error'))
        <div class="sim-alert sim-alert-error mb-4">{{ session('error') }}</div>
    @endif
    @if (session('status'))
        <div class="sim-alert sim-alert-success mb-4">{{ session('status') }}</div>
    @endif

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Master Unit/Klinik</h2>
            <p class="text-sm text-slate-500">
                Satu unit Kantor Pusat (Manajemen) memantau seluruh kegiatan audit.
                Klinik/unit lain adalah sasaran audit dan dapat ditambah atau dinonaktifkan sesuai kebutuhan.
            </p>
        </div>

        @can('clinics.manage')
            <x-action action="create" href="{{ route('clinics.create') }}" icon="plus" label="Tambah Unit/Klinik" />
        @endcan
    </div>

    <div class="simmak-card">
        <div class="simmak-card-header flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Daftar Unit/Klinik</h3>
        </div>
        <div class="simmak-card-body overflow-x-auto">
            <table class="sim-table">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Nama Unit/Klinik</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Sasaran Audit</th>
                        <th class="px-4 py-3">Kota</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($clinics as $clinic)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $clinic->code }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $clinic->name }}</div>
                                @if ($clinic->is_main_clinic)
                                    <span class="simmak-badge mt-1 bg-indigo-100 text-indigo-800">Utama</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ \App\Support\UiLabel::clinicType($clinic->clinic_type) }}</td>
                            <td class="px-4 py-3">
                                @if ($clinic->is_auditable)
                                    <span class="simmak-badge bg-emerald-100 text-emerald-800">Ya</span>
                                @else
                                    <span class="simmak-badge bg-slate-100 text-slate-700">Pemantauan</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $clinic->city ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="simmak-badge {{ $clinic->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $clinic->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="sim-action-group">
                                    @can('clinics.manage')
                                        <x-action action="edit" href="{{ route('clinics.edit', $clinic) }}" icon="pencil" label="Edit" size="sm" />
                                        @unless ($clinic->isManagementUnit())
                                            <x-action action="delete" href="{{ route('clinics.destroy', $clinic) }}" icon="trash" label="Hapus" size="sm" method="DELETE" confirm="Hapus unit/klinik ini? Data terkait dapat terpengaruh." />
                                        @endunless
                                    @else
                                        <span class="text-sm text-slate-400">—</span>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="sim-table-empty">Belum ada data unit/klinik.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($clinics->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $clinics->links() }}</div>
        @endif
    </div>
@endsection
