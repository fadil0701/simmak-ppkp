@extends('layouts.simmak')

@section('title', 'Penugasan Auditor — SIMMAK PPKP')
@section('page-title', 'Penugasan Auditor')

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Penugasan Auditor</h2>
            <p class="text-sm text-slate-500">Kelola penugasan tim auditor dan klinik sasaran untuk setiap program audit.</p>
        </div>

        @can('audit.assignment.manage')
            <x-action href="{{ route('audit.assignments.create') }}" icon="plus" label="Buat Penugasan" />
        @endcan
    </div>

    <div class="simmak-card">
        <div class="simmak-card-header">
            <h3 class="font-semibold text-slate-800">Daftar Penugasan</h3>
        </div>
        <div class="simmak-card-body overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Kode Tugas</th>
                        <th class="px-4 py-3">Judul</th>
                        <th class="px-4 py-3">Program</th>
                        <th class="px-4 py-3">Klinik</th>
                        <th class="px-4 py-3 text-center">Ketua Tim</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($assignments as $assignment)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $assignment->assignment_code }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $assignment->title }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $assignment->program?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $assignment->clinic?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-xs font-medium text-slate-500">{{ $assignment->leadAuditor?->name ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="simmak-badge {{ $assignment->status === 'active' ? 'bg-emerald-100 text-emerald-800' : ($assignment->status === 'draft' ? 'bg-slate-100 text-slate-700' : 'bg-amber-100 text-amber-800') }}">
                                    {{ ucfirst($assignment->status) }}
                                </span>
                                @if ($assignment->conflict_check_status !== 'resolved')
                                    <span class="simmak-badge mt-1 bg-amber-100 text-amber-800 text-xs">Konflik Belum Diselesaikan</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="sim-action-group">
                                    <x-action href="{{ route('audit.assignments.show', $assignment) }}" icon="file-text" label="Lihat" variant="outline" size="sm" />
                                    @can('audit.assignment.manage', $assignment)
                                        <x-action href="{{ route('audit.assignments.edit', $assignment) }}" icon="pencil" label="Ubah" variant="secondary" size="sm" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada penugasan audit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
