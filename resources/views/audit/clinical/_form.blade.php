@php
    $audit = $audit ?? null;
    $assignments = $assignments ?? collect();
    $selectedAssignmentId = old('audit_assignment_id', $audit?->audit_assignment_id);
    $lockedAssignment = $audit?->assignment;
    $hasNonconformity = (string) old('has_nonconformity', filled($audit?->nonconformity_notes) ? '1' : '0') === '1';
@endphp

<div class="space-y-4">
    @if (! $audit)
        <div class="sim-form-group">
            <label class="sim-form-label">Jadwal Penugasan Audit <span class="sim-form-label-required"></span></label>
            <select name="audit_assignment_id" required class="sim-form-select {{ $errors->has('audit_assignment_id') ? 'border-sim-danger' : '' }}">
                <option value="">— Pilih jadwal yang sudah disetujui —</option>
                @foreach ($assignments as $assignment)
                    <option value="{{ $assignment->id }}" @selected($selectedAssignmentId === $assignment->id)
                            data-clinic="{{ $assignment->clinic?->code }} — {{ $assignment->clinic?->name }}">
                        {{ $assignment->assignment_code }} · {{ $assignment->clinic?->name ?? '—' }}
                        ({{ $assignment->scheduled_start?->format('d M Y') }} – {{ $assignment->scheduled_end?->format('d M Y') }})
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-sim-muted">Hanya jadwal yang sudah disetujui Kepala Pusat dan menugaskan Anda.</p>
            @error('audit_assignment_id')<p class="sim-form-error">{{ $message }}</p>@enderror
        </div>
        @if ($assignments->isEmpty())
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                Belum ada jadwal penugasan yang disetujui dan melibatkan Anda. Audit klinis tidak dapat dibuat tanpa jadwal tersebut.
            </div>
        @endif
    @else
        <div class="rounded-lg border border-sim-border bg-sim-surface-alt px-3 py-2 text-sm">
            <span class="text-xs text-sim-muted">Jadwal penugasan</span>
            <p class="font-medium">
                {{ $lockedAssignment?->assignment_code ?? '—' }}
                · {{ $audit->clinic?->name ?? $lockedAssignment?->clinic?->name ?? '—' }}
            </p>
        </div>
    @endif

    <div class="sim-form-group">
        <label class="sim-form-label">Nomor Audit Klinis</label>
        <input type="text" value="{{ $audit?->audit_number ?? $suggestedNumber }}" readonly
               class="sim-form-input bg-sim-surface-alt" tabindex="-1">
        <p class="mt-1 text-xs text-sim-muted">Dibuat otomatis saat data disimpan. Tidak dapat diubah.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sim-form-group">
            <label class="sim-form-label">Klinik / Unit</label>
            <input type="text" id="clinic-display" readonly tabindex="-1"
                   class="sim-form-input bg-sim-surface-alt"
                   value="{{ $audit?->clinic ? ($audit->clinic->code.' — '.$audit->clinic->name) : '' }}"
                   placeholder="Mengikuti jadwal penugasan">
            <p class="mt-1 text-xs text-sim-muted">Diambil dari objek audit pada jadwal. Tidak dapat diubah.</p>
        </div>

        <div class="sim-form-group">
            <label class="sim-form-label">Auditor</label>
            <input type="text" readonly tabindex="-1"
                   class="sim-form-input bg-sim-surface-alt"
                   value="{{ $audit?->auditor?->name ?? auth()->user()?->name }}"
                   placeholder="Mengikuti akun yang ditugaskan">
            <p class="mt-1 text-xs text-sim-muted">Diisi dari akun Anda pada tim penugasan. Tidak dapat diubah lewat formulir.</p>
        </div>
    </div>

    <div class="sim-form-group">
        <label class="sim-form-label">Rekam Medis</label>
        <input type="text" name="medical_record_ref" maxlength="80"
               value="{{ old('medical_record_ref', $audit?->medical_record_ref) }}"
               class="sim-form-input {{ $errors->has('medical_record_ref') ? 'border-sim-danger' : '' }}"
               placeholder="Nomor RM (tanpa identitas pasien lengkap)"
               autocomplete="off">
        <p class="mt-1 text-xs text-sim-muted">Hanya nomor/kode rekam medis. Jangan isi nama atau data pasien lain.</p>
        @error('medical_record_ref')<p class="sim-form-error">{{ $message }}</p>@enderror
    </div>

    <div class="rounded-lg border border-sim-border p-3 space-y-3">
        <label class="flex items-center gap-2 text-sm font-medium text-sim-ink">
            <input type="hidden" name="has_nonconformity" value="0">
            <input type="checkbox" name="has_nonconformity" value="1" @checked($hasNonconformity)
                   class="h-4 w-4 rounded border-sim-border text-sim-primary">
            Ada ketidaksesuaian (temuan)
        </label>
        <p class="text-xs text-sim-muted">Jika dicentang, uraian dan analisis wajib diisi. Sistem menautkannya ke Temuan Audit dan Tindak Lanjut (satu temuan per audit klinis, tidak digandakan saat diubah).</p>

        <div class="sim-form-group">
            <label class="sim-form-label">Uraian Ketidaksesuaian</label>
            <textarea name="nonconformity_notes" rows="4" class="sim-form-textarea {{ $errors->has('nonconformity_notes') ? 'border-sim-danger' : '' }}"
                      placeholder="Uraikan ketidaksesuaian yang ditemukan">{{ old('nonconformity_notes', $audit?->nonconformity_notes) }}</textarea>
            @error('nonconformity_notes')<p class="sim-form-error">{{ $message }}</p>@enderror
        </div>

        <div class="sim-form-group">
            <label class="sim-form-label">Analisis Ketidaksesuaian / Masalah</label>
            <textarea name="problem_analysis" rows="4" class="sim-form-textarea {{ $errors->has('problem_analysis') ? 'border-sim-danger' : '' }}"
                      placeholder="Analisis penyebab atau masalah berdasarkan pemeriksaan">{{ old('problem_analysis', $audit?->problem_analysis) }}</textarea>
            @error('problem_analysis')<p class="sim-form-error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<script>
    (function () {
        var select = document.querySelector('select[name="audit_assignment_id"]');
        var clinic = document.getElementById('clinic-display');
        if (!select || !clinic) {
            return;
        }
        var sync = function () {
            var option = select.options[select.selectedIndex];
            clinic.value = option && option.value ? (option.getAttribute('data-clinic') || '') : '';
        };
        select.addEventListener('change', sync);
        sync();
    })();
</script>
