{{--
  Formulir penugasan auditor — dipakai bersama oleh create & edit agar isinya tidak
  pernah berbeda. Variabel: $action, $method, $submitLabel, $assignment (opsional),
  $suggestedCode, $programs, $clinics, $auditors, $instrumentVersions.
--}}
@php
    $assignment = $assignment ?? null;
    $isEdit = $assignment !== null;

    $action = $action ?? '';
    $method = $method ?? 'POST';
    $submitLabel = $submitLabel ?? ($isEdit ? 'Simpan Perubahan' : 'Buat Penugasan');
    $cancelUrl = $cancelUrl ?? route('audit.assignments.index');
    $suggestedCode = $suggestedCode ?? '';
    $instrumentVersions = $instrumentVersions ?? collect();

    $leadId = (string) old('lead_auditor_id', $assignment->lead_auditor_id ?? '');
    $programId = (string) old('audit_program_id', $assignment->audit_program_id ?? '');
    $clinicId = (string) old('clinic_id', $assignment->clinic_id ?? '');
    $instrumentId = (string) old('instrument_version_id', $assignment->instrument_version_id ?? '');
    $codeValue = old('assignment_code', $assignment->assignment_code ?? $suggestedCode ?? '');
    $titleValue = old('title', $assignment->title ?? '');

    $defaultMembers = $isEdit ? $assignment->teamMembers->where('active', true)->pluck('user_id')->all() : [];
    $memberIds = collect(old('member_ids', $defaultMembers))->filter()->map(fn ($id) => (string) $id)->all();

    $programs = $programs ?? collect();
    $clinics = $clinics ?? collect();
    $auditors = $auditors ?? collect();

    $blockers = [];
    if (! $isEdit && $programs->isEmpty()) {
        $blockers['program'] = 'Belum ada program audit berstatus disetujui, padahal penugasan harus berada di bawah satu program.';
    }
    if ($clinics->isEmpty()) {
        $blockers['clinic'] = 'Tidak ada klinik yang dapat diaudit pada lingkup akses Anda.';
    }
    if ($auditors->isEmpty()) {
        $blockers['auditor'] = 'Belum ada akun aktif yang dapat ditugaskan sebagai auditor.';
    }
@endphp


@if ($errors->any())
    <div class="sim-alert sim-alert-error mb-5" role="alert">
        <span class="sim-alert-icon">!</span>
        <div>
            <p class="font-medium">Formulir belum lengkap — {{ $errors->count() }} isian perlu diperbaiki.</p>
            <ul class="mt-1 list-disc pl-4 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if ($blockers !== [])
    <div class="sim-alert sim-alert-error mb-5" role="alert">
        <span class="sim-alert-icon">!</span>
        <div>
            <p class="font-medium">Penugasan belum dapat {{ $isEdit ? 'diperbarui' : 'dibuat' }}.</p>
            <ul class="mt-1 list-disc pl-4 text-xs">
                @foreach ($blockers as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs">
                @isset($blockers['program'])
                    <a href="{{ route('audit.programs.index') }}" class="underline">Kelola program audit</a> —
                    program harus diajukan lalu disetujui terlebih dahulu.
                @endisset
                @isset($blockers['auditor'])
                    Lengkapi akun pengguna (aktif, dengan izin pelaksanaan audit) pada
                    <a href="{{ route('users.index') }}" class="underline">Manajemen Pengguna</a>.
                @endisset
            </p>
        </div>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="space-y-5" id="assignment-form">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <x-form-section step="1" title="Identitas Penugasan"
                    description="Kode dan judul menjadi penanda tugas ini di seluruh daftar, laporan, dan berita acara.">
        @if ($isEdit)
            <div class="grid gap-4 rounded-lg border border-sim-border-light bg-sim-surface-alt p-3 sm:grid-cols-2">
                <div>
                    <p class="sim-meta-label">Kode Tugas</p>
                    <p class="sim-meta-value font-mono">{{ $assignment->assignment_code }}</p>
                </div>
                <div>
                    <p class="sim-meta-label">Program Audit</p>
                    <p class="sim-meta-value">
                        {{ $assignment->program?->program_code }} — {{ $assignment->program?->name ?? 'Tanpa program' }}
                    </p>
                </div>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sim-form-group !mb-0">
                    <label class="sim-form-label" for="assignment_code">Kode Tugas<span class="sim-form-label-required"></span></label>
                    <input type="text" id="assignment_code" name="assignment_code" maxlength="80"
                           value="{{ $codeValue }}"
                           class="sim-form-input {{ $errors->has('assignment_code') ? 'border-sim-danger' : '' }}"
                           placeholder="TUGAS-{{ now()->format('Y') }}-001" required>
                    @error('assignment_code')<p class="sim-form-error">{{ $message }}</p>@enderror
                    <p class="sim-form-hint">Dibuat otomatis berurutan. Ubah hanya bila instansi memakai penomoran lain.</p>
                </div>

                <div class="sim-form-group !mb-0">
                    <label class="sim-form-label" for="audit_program_id">Program Audit<span class="sim-form-label-required"></span></label>
                    <select id="audit_program_id" name="audit_program_id"
                            class="sim-form-select {{ $errors->has('audit_program_id') ? 'border-sim-danger' : '' }}" required>
                        <option value="">— Pilih program —</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" data-name="{{ $program->name }}"
                                    @selected($programId === (string) $program->id)>
                                {{ $program->program_code }} — {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('audit_program_id')<p class="sim-form-error">{{ $message }}</p>@enderror
                    <p class="sim-form-hint">Hanya program berstatus disetujui yang dapat ditugaskan.</p>
                </div>
            </div>
        @endif

        <div class="sim-form-group !mb-0">
            <label class="sim-form-label" for="title">Judul Tugas<span class="sim-form-label-required"></span></label>
            <input type="text" id="title" name="title" maxlength="255"
                   value="{{ $titleValue }}"
                   class="sim-form-input {{ $errors->has('title') ? 'border-sim-danger' : '' }}"
                   placeholder="Audit Tahunan Klinik Pratama Tebet" required>
            @error('title')<p class="sim-form-error">{{ $message }}</p>@enderror
            <p class="sim-form-hint">Terisi otomatis dari program dan klinik yang dipilih; silakan disesuaikan.</p>
        </div>
    </x-form-section>


    <x-form-section step="2" title="Lokasi &amp; Jadwal Audit"
                    description="Klinik sasaran dan rentang tanggal pelaksanaan. Auditor hanya dapat memeriksa klinik pada penugasan ini.">
        <div class="sim-form-group !mb-0">
            <label class="sim-form-label" for="clinic_id">Klinik / Unit yang Diaudit<span class="sim-form-label-required"></span></label>
            <select id="clinic_id" name="clinic_id"
                    class="sim-form-select {{ $errors->has('clinic_id') ? 'border-sim-danger' : '' }}" required>
                <option value="">— Pilih klinik —</option>
                @foreach ($clinics as $clinic)
                    <option value="{{ $clinic->id }}" data-name="{{ $clinic->name }}"
                            @selected($clinicId === (string) $clinic->id)>
                        {{ $clinic->code }} — {{ $clinic->name }}@if ($clinic->city) · {{ $clinic->city }}@endif
                    </option>
                @endforeach
            </select>
            @error('clinic_id')<p class="sim-form-error">{{ $message }}</p>@enderror
            <p class="sim-form-hint">Daftar dibatasi pada klinik yang dapat diaudit dalam lingkup akses Anda.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sim-form-group !mb-0">
                <label class="sim-form-label" for="scheduled_start">Tanggal Mulai<span class="sim-form-label-required"></span></label>
                <input type="date" id="scheduled_start" name="scheduled_start"
                       value="{{ old('scheduled_start', $assignment?->scheduled_start?->format('Y-m-d')) }}"
                       class="sim-form-input {{ $errors->has('scheduled_start') ? 'border-sim-danger' : '' }}" required>
                @error('scheduled_start')<p class="sim-form-error">{{ $message }}</p>@enderror
            </div>

            <div class="sim-form-group !mb-0">
                <label class="sim-form-label" for="scheduled_end">Tanggal Selesai<span class="sim-form-label-required"></span></label>
                <input type="date" id="scheduled_end" name="scheduled_end"
                       value="{{ old('scheduled_end', $assignment?->scheduled_end?->format('Y-m-d')) }}"
                       class="sim-form-input {{ $errors->has('scheduled_end') ? 'border-sim-danger' : '' }}" required>
                @error('scheduled_end')<p class="sim-form-error">{{ $message }}</p>@enderror
                <p class="sim-form-hint" id="schedule-hint">Tidak boleh lebih awal dari tanggal mulai.</p>
            </div>
        </div>
    </x-form-section>


    <x-form-section step="3" title="Tim Auditor"
                    description="Tetapkan ketua tim, lalu centang anggota lain yang ikut bertugas. Ketua tim otomatis menjadi anggota tim.">
        @if ($blockers['auditor'] ?? false)
            <p class="text-sm text-sim-danger">{{ $blockers['auditor'] }}</p>
        @else
            <div class="sim-form-group !mb-0">
                <label class="sim-form-label" for="lead_auditor_id">Ketua Tim Auditor<span class="sim-form-label-required"></span></label>
                <select id="lead_auditor_id" name="lead_auditor_id"
                        class="sim-form-select {{ $errors->has('lead_auditor_id') ? 'border-sim-danger' : '' }}" required>
                    <option value="">— Pilih ketua tim —</option>
                    @foreach ($auditors as $auditor)
                        <option value="{{ $auditor->id }}" @selected($leadId === (string) $auditor->id)>
                            {{ $auditor->employee?->full_name ?? $auditor->name }}@if ($auditor->employee?->employee_number) · {{ $auditor->employee->employee_number }}@endif@if ($auditor->employee?->unit?->name) — {{ $auditor->employee->unit->name }}@elseif ($auditor->employee?->clinic?->name) — {{ $auditor->employee->clinic->name }}@endif
                        </option>
                    @endforeach
                </select>
                @error('lead_auditor_id')<p class="sim-form-error">{{ $message }}</p>@enderror
                <p class="sim-form-hint">Hanya akun aktif yang memiliki izin pelaksanaan audit yang muncul di sini.</p>
            </div>

            @if ($isEdit)
                @php
                    $currentMembers = $assignment->teamMembers
                        ->where('active', true)
                        ->reject(fn ($member) => $member->user_id === $assignment->lead_auditor_id);
                @endphp

                <div class="sim-form-group !mb-0">
                    <span class="sim-form-label">Anggota Tim Saat Ini</span>

                    @if ($currentMembers->isEmpty())
                        <p class="sim-form-hint">Belum ada anggota selain ketua tim.</p>
                    @else
                        <ul class="mt-2 space-y-2 rounded-lg border border-sim-border bg-sim-surface-alt p-3">
                            @foreach ($currentMembers as $member)
                                <li class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-sim-ink">
                                            {{ $member->user?->employee?->full_name ?? $member->user?->name ?? 'Pengguna tidak ditemukan' }}
                                        </span>
                                        <span class="mt-0.5 block text-xs text-sim-muted">
                                            {{ ucfirst(str_replace('_', ' ', $member->team_role)) }}
                                            @if ($member->user?->employee?->employee_number)
                                                · NIP {{ $member->user->employee->employee_number }}
                                            @endif
                                        </span>
                                    </span>
                                    <span @class([
                                        'sim-badge shrink-0 text-[11px]',
                                        'border-sim-teal-600/30 bg-sim-teal-50 text-sim-teal-700' => $member->independence_declared && ! $member->conflict_declared,
                                        'border-sim-warning/30 bg-sim-warning/10 text-sim-warning' => ! $member->independence_declared || $member->conflict_declared,
                                    ])>
                                        {{ $member->independence_declared && ! $member->conflict_declared ? 'Independensi tercatat' : 'Menunggu deklarasi' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <p class="sim-form-hint">
                        Keanggotaan tim tidak diubah dari halaman ini agar deklarasi independensi yang sudah tercatat tidak hilang.
                        <a href="{{ route('audit.assignments.show', $assignment) }}" class="underline" class="sim-btn sim-btn-detail">Kelola tim di halaman detail</a>.
                    </p>
                </div>
            @else
                <div class="sim-form-group !mb-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="sim-form-label !mb-0">Anggota Tim Tambahan</span>
                        <span class="sim-badge border-sim-border-light bg-sim-surface-alt text-[11px] text-sim-muted"
                              id="member-counter">0 dipilih</span>
                    </div>

                    @if ($auditors->count() > 1)
                        <div class="mt-2 max-h-64 space-y-2 overflow-y-auto rounded-lg border border-sim-border bg-sim-surface-alt p-3">
                            @foreach ($auditors as $auditor)
                                @php
                                    $info = [
                                        'name' => $auditor->employee?->full_name ?? $auditor->name,
                                        'context' => $auditor->employee?->unit?->name ?? $auditor->employee?->clinic?->name ?? $auditor->email,
                                        'meta' => $auditor->employee?->employee_number ? 'NIP '.$auditor->employee->employee_number : null,
                                    ];
                                    $isLead = $leadId === (string) $auditor->id;
                                @endphp

                                <label class="sim-check-card !p-2.5"
                                       data-auditor-row="{{ $auditor->id }}"
                                       data-auditor-name="{{ $info['name'] }}"
                                       @class(['sim-check-card-off' => $isLead])>
                                    <input type="checkbox" name="member_ids[]" value="{{ $auditor->id }}"
                                           class="mt-0.5 h-4 w-4 shrink-0 rounded border-sim-border text-sim-primary focus:ring-sim-primary"
                                           data-member-checkbox="{{ $auditor->id }}"
                                           @checked(in_array((string) $auditor->id, $memberIds, true))>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium text-sim-ink">{{ $info['name'] }}</span>
                                        <span class="mt-0.5 block text-xs text-sim-muted">{{ $info['context'] }}</span>
                                    </span>
                                    <span class="sim-badge shrink-0 border-sim-teal-600/30 bg-sim-teal-50 text-[11px] text-sim-teal-700"
                                          data-lead-badge @if (! $isLead) hidden @endif>Ketua tim</span>
                                    @if ($info['meta'])
                                        <span class="sim-badge shrink-0 border-sim-border-light bg-sim-white text-[11px] font-normal text-sim-muted">{{ $info['meta'] }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        <p class="sim-form-hint">
                            Ketua tim yang dipilih tidak perlu dicentang di sini — ia otomatis menjadi anggota tim.
                            Anggota lain dapat ditambahkan belakangan dari halaman detail penugasan beserta deklarasi independensinya.
                        </p>
                    @else
                        <p class="sim-form-hint">Hanya ada satu kandidat auditor, sehingga ketua tim menjadi satu-satunya anggota tim.</p>
                    @endif
                    @error('member_ids')<p class="sim-form-error">{{ $message }}</p>@enderror
                    @error('member_ids.*')<p class="sim-form-error">{{ $message }}</p>@enderror
                </div>
            @endif
        @endif
    </x-form-section>


    <x-form-section step="4" title="Instrumen &amp; Ruang Lingkup"
                    description="Opsional. Instrumen yang direncanakan membantu auditor menyiapkan checklist sebelum eksekusi dimulai.">
        <div class="sim-form-group !mb-0">
            <label class="sim-form-label" for="instrument_version_id">Standar / Instrumen</label>
            <select id="instrument_version_id" name="instrument_version_id"
                    class="sim-form-select {{ $errors->has('instrument_version_id') ? 'border-sim-danger' : '' }}">
                <option value="">— Belum ditentukan —</option>
                @foreach ($instrumentVersions as $version)
                    <option value="{{ $version->id }}" @selected($instrumentId === (string) $version->id)>
                        {{ $version->standard?->title ?? 'Standar' }} ({{ $version->version_label }})
                    </option>
                @endforeach
            </select>
            @error('instrument_version_id')<p class="sim-form-error">{{ $message }}</p>@enderror
            <p class="sim-form-hint">
                Bila dikosongkan, kolom instrumen mengambil versi yang dipakai saat eksekusi audit dibuat.
            </p>
        </div>

        <div class="sim-form-group !mb-0">
            <label class="sim-form-label" for="scope_notes">Catatan Ruang Lingkup</label>
            <textarea id="scope_notes" name="scope_notes" rows="3"
                      class="sim-form-textarea {{ $errors->has('scope_notes') ? 'border-sim-danger' : '' }}"
                      placeholder="Contoh: fokus pada pelayanan rawat jalan, kepatuhan rekam medis, dan kebersihan lingkungan">{{ old('scope_notes', $assignment->scope_notes ?? '') }}</textarea>
            @error('scope_notes')<p class="sim-form-error">{{ $message }}</p>@enderror
        </div>
    </x-form-section>

    <div class="sim-form-actions">
        <p class="text-xs text-sim-muted">
            @if ($blockers !== [])
                Lengkapi prasyarat di atas sebelum formulir ini dapat disimpan.
            @else
                Penugasan tersimpan sebagai <strong>draf</strong>. Aktivasi baru bisa dilakukan setelah seluruh anggota
                menyatakan independensi.
            @endif
        </p>
        <div class="flex flex-wrap items-center gap-2">
            <x-action action="cancel" href="{{ $cancelUrl }}" icon="chevron-left" label="Batal" />
            <x-action action="save" icon="check-circle" :label="$submitLabel" :disabled="$blockers !== []" />
        </div>
    </div>
</form>


<script>
    (function () {
        const form = document.getElementById('assignment-form');
        if (!form) {
            return;
        }

        const programSelect = document.getElementById('audit_program_id');
        const clinicSelect = document.getElementById('clinic_id');
        const titleInput = document.getElementById('title');

        // 1. Judul terisi otomatis selama pengguna belum menyuntingnya sendiri.
        let titleTouched = titleInput ? titleInput.value.trim() !== '' : false;

        const fillTitle = function () {
            if (!titleInput || titleTouched || !programSelect || !clinicSelect) {
                return;
            }

            const program = programSelect.selectedOptions[0];
            const clinic = clinicSelect.selectedOptions[0];

            if (!program || !program.value || !clinic || !clinic.value) {
                return;
            }

            titleInput.value = (program.dataset.name || '') + ' — ' + (clinic.dataset.name || '');
        };

        if (titleInput) {
            titleInput.addEventListener('input', function () {
                titleTouched = true;
            });
        }

        [programSelect, clinicSelect].forEach(function (select) {
            if (select) {
                select.addEventListener('change', fillTitle);
            }
        });

        // 2. Ketua tim otomatis menjadi anggota: barisnya dikunci dan diberi penanda.
        const leadSelect = document.getElementById('lead_auditor_id');
        const rows = Array.prototype.slice.call(document.querySelectorAll('[data-auditor-row]'));
        const counter = document.getElementById('member-counter');

        const refreshCounter = function () {
            if (!counter) {
                return;
            }

            const picked = rows.filter(function (row) {
                const box = row.querySelector('[data-member-checkbox]');

                return box && box.checked && !box.disabled;
            }).length;

            counter.textContent = picked + ' dipilih';
        };

        const syncLead = function () {
            const leadId = leadSelect ? leadSelect.value : '';

            rows.forEach(function (row) {
                const box = row.querySelector('[data-member-checkbox]');
                const badge = row.querySelector('[data-lead-badge]');
                const isLead = leadId !== '' && row.dataset.auditorRow === leadId;

                row.classList.toggle('sim-check-card-off', isLead);

                if (badge) {
                    badge.hidden = !isLead;
                }

                if (box) {
                    box.disabled = isLead;

                    if (isLead) {
                        box.checked = false;
                    }
                }
            });

            refreshCounter();
        };

        if (leadSelect) {
            leadSelect.addEventListener('change', syncLead);
            syncLead();
        }

        rows.forEach(function (row) {
            const box = row.querySelector('[data-member-checkbox]');

            if (box) {
                box.addEventListener('change', refreshCounter);
            }
        });

        // 3. Tanggal selesai tidak boleh mendahului tanggal mulai.
        const startInput = document.getElementById('scheduled_start');
        const endInput = document.getElementById('scheduled_end');
        const scheduleHint = document.getElementById('schedule-hint');

        const syncSchedule = function () {
            if (!startInput || !endInput) {
                return;
            }

            if (startInput.value) {
                endInput.min = startInput.value;
            } else {
                endInput.removeAttribute('min');
            }

            if (scheduleHint) {
                const start = startInput.value ? new Date(startInput.value) : null;
                const end = endInput.value ? new Date(endInput.value) : null;

                if (start && end && end >= start) {
                    const days = Math.round((end - start) / 86400000) + 1;
                    scheduleHint.textContent = 'Durasi ' + days + ' hari kalender.';
                } else {
                    scheduleHint.textContent = 'Tidak boleh lebih awal dari tanggal mulai.';
                }
            }
        };

        [startInput, endInput].forEach(function (input) {
            if (input) {
                input.addEventListener('change', syncSchedule);
            }
        });

        syncSchedule();

        // 4. Cegah pengiriman formulir saat prasyarat utama belum lengkap.
        form.addEventListener('submit', function (event) {
            if (!leadSelect || leadSelect.value !== '') {
                return;
            }

            if (rows.length > 0) {
                event.preventDefault();
                leadSelect.focus();
            }
        });
    })();
</script>
