@extends('layouts.simmak')

@section('title', 'Master Klinik — SIMMAK PPKP')
@section('page-title', 'Master Klinik')

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Master Klinik</h2>
            <p class="text-sm text-slate-500">Kelola data klinik dan satuan pelayanan kesehatan PPKP DKI Jakarta.</p>
        </div>

        @can('clinics.manage')
            <a href="{{ route('clinics.create') }}" class="sim-btn sim-btn-primary inline-flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Klinik
            </a>
        @endcan
    </div>

    <div class="simmak-card">
        <div class="simmak-card-header flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Daftar Klinik</h3>
        </div>
        <div class="simmak-card-body overflow-x-auto">
            <table class="sim-table">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Nama Klinik</th>
                        <th class="px-4 py-3">Tipe</th>
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
                            <td class="px-4 py-3 text-slate-600">{{ $clinic->city ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="simmak-badge {{ $clinic->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $clinic->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @can('clinics.manage')
                                    <a href="{{ route('clinics.edit', $clinic) }}" class="text-sm font-medium text-teal-700 hover:underline mr-3">Sunting</a>
                                    <form method="POST" action="{{ route('clinics.destroy', $clinic) }}" class="inline" onsubmit="return confirm('Yakin ingin menghapus klinik ini? Data terkait dapat terpengaruh.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="sim-btn sim-btn-danger sim-btn-sm">Hapus</button>
                                    </form>
                                @else
                                    <span class="text-sm text-slate-400">—</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="sim-table-empty">Belum ada data klinik.</td>
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
