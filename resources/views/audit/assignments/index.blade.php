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
                        <th class="px-4 py-3">Program</th>
                        <th class="px-4 py-3">Petugas Auditor</th>
                        <th class="px-4 py-3">Lokasi Audit</th>
                        <th class="px-4 py-3">Tanggal Audit</th>
                        <th class="px-4 py-3">Standar / Instrumen</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($assignments as $assignment)
                        @php
                            $members = $assignment->teamMembers
                                ->where('team_role', '!=', 'lead_auditor')
                                ->count();
                            $lead = $assignment->leadAuditor;
                            $overdue = $assignment->scheduled_end
                                && $assignment->scheduled_end->isPast()
                                && $assignment->status !== 'completed';
                            $standardTitle = $assignment->instrumentTitle();
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-mono text-xs text-slate-700">{{ $assignment->assignment_code }}</div>
                                <div class="mt-0.5 font-medium text-slate-800">{{ $assignment->title }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $assignment->program?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $lead?->name ?? '—' }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">
                                    @if ($lead?->employee?->employee_number)
                                        NIP {{ $lead->employee->employee_number }} ·
                                    @endif
                                    @if ($members > 0)
                                        +{{ $members }} anggota
                                    @else
                                        tanpa anggota tambahan
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <div>{{ $assignment->clinic?->name ?? '—' }}</div>
                                @if ($assignment->clinic?->city)
                                    <div class="mt-0.5 text-xs text-slate-500">{{ $assignment->clinic->city }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                @if ($assignment->scheduled_start && $assignment->scheduled_end)
                                    <div>
                                        {{ $assignment->scheduled_start->translatedFormat('d M Y') }}
                                        –
                                        {{ $assignment->scheduled_end->translatedFormat('d M Y') }}
                                    </div>
                                @else
                                    <div>—</div>
                                @endif
                                @if ($overdue)
                                    <span class="simmak-badge mt-1 bg-rose-100 text-rose-800 text-xs">Melewati jadwal</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                @if ($standardTitle)
                                    <div>{{ $standardTitle }}</div>
                                @else
                                    <span class="text-xs text-slate-500">Belum ditentukan</span>
                                @endif
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
                                    <x-action href="{{ route('audit.assignments.show', $assignment) }}" icon="users" label="Detail Tim" variant="outline" size="sm" />
                                    @can('audit.assignment.manage', $assignment)
                                        <x-action href="{{ route('audit.assignments.edit', $assignment) }}" icon="pencil" label="Ubah" variant="secondary" size="sm" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">Belum ada penugasan audit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($assignments->hasPages())
            <div class="simmak-card-body border-t border-slate-100">{{ $assignments->links() }}</div>
        @endif
    </div>
@endsection
