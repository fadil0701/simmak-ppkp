@extends('layouts.simmak')

@section('title', $assignment->assignment_code . ' — SIMMAK PPKP')
@section('page-title', 'Detail Penugasan: ' . $assignment->assignment_code)

@section('content')
    @if ($assignment->conflict_check_status === 'blocked')
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Ada deklarasi konflik kepentingan. Penugasan tidak dapat diaktifkan sampai anggota yang berkonflik diganti atau konflik diselesaikan.
        </div>
    @elseif ($assignment->conflict_check_status === 'pending' && $assignment->teamMembers->isNotEmpty())
        <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            Semua anggota tim harus menyatakan independensi (termasuk opsi tidak ada konflik) sebelum penugasan dapat diaktifkan.
        </div>
    @endif

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ $assignment->assignment_code }} — {{ $assignment->title }}</h2>
            @if ($assignment->scope_notes)
                <p class="text-sm text-slate-500">{{ $assignment->scope_notes }}</p>
            @endif
        </div>

        <div class="flex gap-2 flex-wrap">
            @can('audit.assignment.manage', $assignment)
                <x-action href="{{ route('audit.assignments.edit', $assignment) }}" icon="pencil" label="Ubah" size="sm" />
            @endcan
            @can('audit.assignment.manage', $assignment)
                @if ($assignment->status === 'draft')
                    <x-action href="{{ route('audit.assignments.submit', $assignment) }}" icon="upload" label="Ajukan Aktivasi" size="sm"
                              method="POST" confirm="Ajukan penugasan ini untuk aktivasi?" />
                @elseif ($assignment->status === 'proposed' && $assignment->conflict_check_status === 'resolved')
                    <x-action href="{{ route('audit.assignments.activate', $assignment) }}" icon="check-circle" label="Aktifkan" size="sm"
                              method="POST" confirm="Aktifkan penugasan ini?" />
                @endif
            @endcan
            <x-action href="{{ route('audit.assignments.index') }}" icon="chevron-left" label="Kembali" variant="outline" size="sm" />
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="simmak-card lg:col-span-2">
            <div class="simmak-card-header">
                <h3 class="font-semibold text-slate-800">Tim Auditor</h3>
            </div>
            <div class="simmak-card-body space-y-3">
                @forelse ($assignment->teamMembers as $member)
                    <div class="border border-slate-100 rounded-lg p-4 hover:bg-slate-50">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <div class="font-mono text-xs text-slate-500">{{ $member->user->name }}</div>
                                <div class="font-medium text-slate-800">{{ ucfirst(str_replace('_', ' ', $member->team_role)) }}</div>
                            </div>
                            <span class="simmak-badge {{ $member->active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                {{ $member->active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                        <div class="text-sm text-slate-600 space-y-1">
                            <div class="flex justify-between">
                                <span class="text-xs text-slate-400">Independensi Dinyatakan</span>
                                <span class="{{ $member->independence_declared ? 'text-emerald-600' : 'text-red-600' }}">{{ $member->independence_declared ? 'Ya' : 'Belum' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-slate-400">Konflik Dinyatakan</span>
                                <span class="{{ $member->conflict_declared ? 'text-amber-600' : 'text-emerald-600' }}">{{ $member->conflict_declared ? 'Ya' : 'Tidak' }}</span>
                            </div>
                            @if ($member->conflict_notes)
                                <div class="text-xs text-slate-500 mt-1">{{ $member->conflict_notes }}</div>
                            @endif
                        </div>

                        @can('audit.assignment.manage', $assignment)
                            @unless ($member->independence_declared)
                            <form method="POST" action="{{ route('audit.team-members.conflict', $member) }}" class="mt-3" onsubmit="return confirm('Simpan deklarasi independensi dan konflik?');">
                                @csrf
                                <fieldset class="space-y-1">
                                    <legend class="text-xs font-medium text-slate-600">Deklarasi konflik kepentingan</legend>
                                    <label class="flex items-center gap-2 text-xs">
                                        <input type="radio" name="conflict_declared" value="0" {{ old('conflict_declared', '0') === '0' ? 'checked' : '' }} required>
                                        Tidak ada konflik kepentingan
                                    </label>
                                    <label class="flex items-center gap-2 text-xs">
                                        <input type="radio" name="conflict_declared" value="1" {{ old('conflict_declared') === '1' ? 'checked' : '' }}>
                                        Ada konflik kepentingan
                                    </label>
                                </fieldset>
                                <div class="mt-2">
                                    <label class="sim-form-label">Catatan (wajib jika ada konflik)</label>
                                    <textarea name="conflict_notes" rows="2" class="sim-form-textarea">{{ old('conflict_notes', $member->conflict_notes) }}</textarea>
                                    @error('conflict_notes')<p class="sim-form-error">{{ $message }}</p>@enderror
                                </div>
                                <x-action icon="check-circle" label="Simpan Deklarasi" size="sm" class="mt-2" />
                            </form>
                            @endunless
                        @endcan
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada anggota tim auditor.</p>
                @endforelse

                @can('audit.assignment.manage', $assignment)
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <h4 class="text-xs font-semibold text-slate-500 uppercase mb-3">Tambah Anggota Tim</h4>
                        <form method="POST" action="{{ route('audit.assignments.team', $assignment) }}" class="space-y-3" @submit>
                            @csrf
                            <div class="grid gap-3 sm:grid-cols-4">
                                <div class="sim-form-group">
                                    <label class="sim-form-label">Auditor</label>
                                    <select name="user_id" class="sim-form-select" required>
                                        <option value="">— Pilih auditor —</option>
                                        @foreach ($auditors as $auditor)
                                            <option value="{{ $auditor->id }}">{{ $auditor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sim-form-group">
                                    <label class="sim-form-label">Peran</label>
                                    <input type="text" name="team_role" value="auditor" class="sim-form-input" required>
                                </div>
                                <div class="flex items-end">
                                    <x-action icon="plus" label="Tambah" size="sm" />
                                </div>
                            </div>
                        </form>
                    </div>
                @endcan
            </div>
        </div>

        <div class="simmak-card lg:col-span-1">
            <div class="simmak-card-header">
                <h3 class="font-semibold text-slate-800">Informasi</h3>
            </div>
            <div class="simmak-card-body space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Program</span>
                    <span>{{ $assignment->program?->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Klinik</span>
                    <span>{{ $assignment->clinic?->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Ketua Tim</span>
                    <span>{{ $assignment->leadAuditor?->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Periode</span>
                    <span>{{ $assignment->scheduled_start?->format('d M Y') }} – {{ $assignment->scheduled_end?->format('d M Y') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Status</span>
                    <span class="simmak-badge">{{ ucfirst($assignment->status) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Cek Konflik</span>
                    <span class="simmak-badge {{ $assignment->conflict_check_status === 'resolved' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ ucfirst(str_replace('_', ' ', $assignment->conflict_check_status)) }}
                    </span>
                </div>
            </div>
        </div>

        @can('execute', $assignment)
                <div class="simmak-card lg:col-span-1">
                    <div class="simmak-card-header">
                        <h3 class="font-semibold text-slate-800">Buat Eksekusi Audit</h3>
                    </div>
                    <div class="simmak-card-body space-y-3">
                        <form method="POST" action="{{ route('audit.executions.store', $assignment) }}" class="space-y-3">
                            @csrf
                            <div class="sim-form-group">
                                <label class="sim-form-label">Versi Instrumen<span class="sim-form-label-required"></span></label>
                                <select name="instrument_version_id" class="sim-form-select" required>
                                    <option value="">— Pilih versi instrumen —</option>
                                    @foreach ($instrumentVersions as $version)
                                        <option value="{{ $version->id }}">{{ $version->version_label }} ({{ \App\Support\UiLabel::auditStatus($version->status) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sim-form-group">
                                <label class="sim-form-label">Kode Eksekusi<span class="sim-form-label-required"></span></label>
                                <input type="text" name="execution_code" maxlength="80" value="{{ old('execution_code', 'EXEC-' . date('Ymd') . '-' . substr(str_replace('-', '', $assignment->id), 0, 8)) }}" class="sim-form-input">
                            </div>
                            <div class="sim-form-group">
                                <label class="sim-form-label">Judul Eksekusi<span class="sim-form-label-required"></span></label>
                                <input type="text" name="title" maxlength="255" value="{{ old('title', $assignment->title) }}" class="sim-form-input">
                            </div>
                            <x-action icon="plus" label="Buat Eksekusi" />
                        </form>
                    </div>
                </div>
        @endcan
    </div>
@endsection
