@extends('layouts.simmak')

@section('title', $assignment->assignment_code . ' — SIMMAK PPKP')
@section('page-title', 'Detail Penugasan: ' . $assignment->assignment_code)

@section('content')
    @if ($assignment->status === 'rejected')
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
            <p class="font-medium">Jadwal ditolak Kepala Pusat — belum dipublikasikan.</p>
            @if ($assignment->rejection_reason)
                <p class="mt-1">Alasan: {{ $assignment->rejection_reason }}</p>
            @endif
            @if ($assignment->rejecter)
                <p class="mt-1 text-xs">{{ $assignment->rejecter->name }} · {{ $assignment->rejected_at?->translatedFormat('d M Y H:i') }}</p>
            @endif
        </div>
    @elseif ($assignment->status === 'proposed')
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Menunggu persetujuan akhir Kepala Pusat. Jadwal belum tampil di kalender auditor/auditi.
        </div>
    @endif

    @if ($assignment->conflict_check_status === 'blocked')
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Ada deklarasi konflik kepentingan. Penugasan tidak dapat disetujui sampai anggota yang berkonflik diganti atau konflik diselesaikan.
        </div>
    @elseif ($assignment->conflict_check_status === 'pending' && $assignment->teamMembers->isNotEmpty())
        <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            Semua anggota tim harus menyatakan independensi (termasuk opsi tidak ada konflik) sebelum jadwal dapat disetujui Kepala Pusat.
        </div>
    @endif

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ $assignment->assignment_code }} — {{ $assignment->title }}</h2>
            @if ($assignment->scope_notes)
                <p class="text-sm text-slate-500">{{ $assignment->scope_notes }}</p>
            @endif
            <p class="text-sm text-slate-500">
                Jumlah auditor: {{ $assignment->teamMembers->where('active', true)->count() }} (ketua + anggota)
                · {{ $assignment->clinic?->name ?? 'Tanpa lokasi' }}
                @if ($assignment->scheduled_start && $assignment->scheduled_end)
                    · {{ $assignment->scheduled_start->translatedFormat('d M Y') }} – {{ $assignment->scheduled_end->translatedFormat('d M Y') }}
                @endif
                @if ($assignment->instrumentTitle())
                    · {{ $assignment->instrumentTitle() }}
                @endif
            </p>
        </div>

        <div class="flex gap-2 flex-wrap">
            @can('update', $assignment)
                <x-action action="edit" href="{{ route('audit.assignments.edit', $assignment) }}" icon="pencil" label="Edit" size="sm" />
            @endcan
            @can('submit', $assignment)
                <x-action action="upload" href="{{ route('audit.assignments.submit', $assignment) }}" icon="upload"
                          :label="$assignment->status === 'rejected' ? 'Ajukan Ulang ke Kepala Pusat' : 'Ajukan ke Kepala Pusat'" size="sm"
                          method="POST" confirm="Ajukan jadwal ini untuk persetujuan akhir Kepala Pusat?" />
            @endcan
            @can('approve', $assignment)
                <x-action action="approve" href="{{ route('audit.assignments.approve', $assignment) }}" icon="check-circle" label="Setujui &amp; Publikasikan" size="sm"
                          method="POST" confirm="Setujui jadwal ini? Setelah disetujui, jadwal tampil di kalender auditor dan auditi yang terlibat." />
            @endcan
            <x-action action="back" href="{{ route('audit.assignments.index') }}" icon="chevron-left" label="Kembali" size="sm" />
        </div>
    </div>

    @can('reject', $assignment)
        <form method="POST" action="{{ route('audit.assignments.reject', $assignment) }}" class="mb-6 rounded-lg border border-rose-200 bg-rose-50 p-4">
            @csrf
            <label class="sim-form-label" for="rejection_reason">Tolak jadwal (wajib isi alasan)</label>
            <textarea id="rejection_reason" name="reason" rows="2" required class="sim-form-textarea mt-1" placeholder="Contoh: tanggal bentrok dengan program lain">{{ old('reason') }}</textarea>
            @error('reason')<p class="sim-form-error">{{ $message }}</p>@enderror
            <x-action action="reject" icon="x-mark" label="Tolak &amp; kembalikan" size="sm" class="mt-2" />
        </form>
    @endcan

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="simmak-card lg:col-span-2">
            <div class="simmak-card-header">
                <h3 class="font-semibold text-slate-800">Tim Auditor</h3>
            </div>
            <div class="simmak-card-body space-y-3">
                @php
                    $inactiveMembers = $assignment->teamMembers->where('active', false)->count();
                    $pendingMembers = $assignment->teamMembers
                        ->filter(fn ($member) => ! $member->independence_declared || $member->conflict_declared)
                        ->count();
                @endphp
                @if ($inactiveMembers > 0 || $pendingMembers > 0)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 space-y-1">
                        @if ($inactiveMembers > 0)
                            <p>{{ $inactiveMembers }} anggota tim berstatus nonaktif — data tetap ditampilkan untuk jejak audit.</p>
                        @endif
                        @if ($pendingMembers > 0)
                            <p>{{ $pendingMembers }} anggota belum menyelesaikan deklarasi independensi/konflik.</p>
                        @endif
                    </div>
                @endif
                @forelse ($assignment->teamMembers as $member)
                    <div class="border border-slate-100 rounded-lg p-4 hover:bg-slate-50">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <div class="font-medium text-slate-800">{{ $member->user?->name ?? 'Pengguna tidak ditemukan' }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ \App\Support\UiLabel::teamRole($member->team_role) }}
                                    <span class="text-slate-400"> · peran di tim audit ini, bukan peran akun SIMMAK</span>
                                    @if ($member->user?->employee?->employee_number)
                                        · NIP {{ $member->user->employee->employee_number }}
                                    @endif
                                    @if ($member->user?->employee?->position)
                                        · {{ $member->user->employee->position }}
                                    @endif
                                    @if ($member->user?->employee?->unit?->name)
                                        · {{ $member->user->employee->unit->name }}
                                    @endif
                                </div>
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
                                <x-action action="save" icon="check-circle" label="Simpan Deklarasi" size="sm" class="mt-2" />
                            </form>
                            @endunless
                        @endcan
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada anggota tim auditor.</p>
                @endforelse

                @can('audit.assignment.manage', $assignment)
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <h4 class="text-xs font-semibold text-slate-500 uppercase mb-1">Tambah anggota tim audit ini</h4>
                        <p class="mb-3 text-xs text-slate-500">
                            Peran di sini hanya berlaku pada penugasan ini (ketua / anggota / pengamat).
                            Bukan peran jabatan akun dan bukan tugas modul di form pengguna.
                        </p>
                        <form method="POST" action="{{ route('audit.assignments.team', $assignment) }}" class="space-y-3" @submit>
                            @csrf
                            <div class="grid gap-3 sm:grid-cols-4">
                                <div class="sim-form-group sm:col-span-2">
                                    <label class="sim-form-label">Anggota (akun)</label>
                                    <select name="user_id" class="sim-form-select" required>
                                        <option value="">— Pilih anggota —</option>
                                        @foreach ($auditors as $auditor)
                                            <option value="{{ $auditor->id }}">{{ $auditor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sim-form-group">
                                    <label class="sim-form-label">Peran dalam tim audit ini</label>
                                    <select name="team_role" class="sim-form-select" required>
                                        @foreach (['auditor', 'lead_auditor', 'observer'] as $peranTim)
                                            <option value="{{ $peranTim }}" @selected(old('team_role', 'auditor') === $peranTim)>
                                                {{ \App\Support\UiLabel::teamRole($peranTim) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <x-action action="create" icon="plus" label="Tambah" size="sm" />
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
                    <span class="simmak-badge">{{ \App\Support\UiLabel::assignmentStatus($assignment->status) }}</span>
                </div>
                @if ($assignment->approver && $assignment->approved_at)
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Disetujui</span>
                        <span>{{ $assignment->approver->name }} · {{ $assignment->approved_at->translatedFormat('d M Y H:i') }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Cek Konflik</span>
                    <span class="simmak-badge {{ $assignment->conflict_check_status === 'resolved' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ ucfirst(str_replace('_', ' ', $assignment->conflict_check_status)) }}
                    </span>
                </div>
            </div>
        </div>

        @include('audit.programs._document-list', [
            'documents' => $assignment->program?->documents ?? collect(),
            'heading' => 'Dokumen Acuan Program Audit',
            'class' => 'lg:col-span-3',
        ])

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
                                <label class="sim-form-label">Kode Eksekusi</label>
                                <input type="text" value="{{ $suggestedExecutionCode ?? '' }}" readonly tabindex="-1" class="sim-form-input bg-sim-surface-alt">
                                <p class="text-xs text-sim-muted mt-1">Dibuat otomatis saat data disimpan.</p>
                            </div>
                            <div class="sim-form-group">
                                <label class="sim-form-label">Judul Eksekusi<span class="sim-form-label-required"></span></label>
                                <input type="text" name="title" maxlength="255" value="{{ old('title', $assignment->title) }}" class="sim-form-input">
                            </div>
                            <x-action action="create" icon="plus" label="Buat Eksekusi" />
                        </form>
                    </div>
                </div>
        @endcan
    </div>
@endsection
