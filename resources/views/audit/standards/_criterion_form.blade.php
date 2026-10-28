@props([
    'action',
    'method' => 'POST',
    'criterion' => null,
    'submitLabel' => 'Simpan EP',
    'formKey' => 'criterion-new',
])

@php
    $isEdit = $method !== 'POST';
    $openOnError = $errors->any() && old('_form') === $formKey;
@endphp

<form method="POST" action="{{ $action }}"
      class="mt-3 space-y-3 rounded-lg border border-sim-border-light bg-sim-surface-alt p-3">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif
    <input type="hidden" name="_form" value="{{ $formKey }}">
    <div class="grid gap-3 sm:grid-cols-4">
        <div class="sm:col-span-1">
            <label class="sim-form-label text-xs">Kode EP <span class="text-sim-danger">*</span></label>
            <input type="text" name="code" maxlength="80" required
                   value="{{ old('code', $criterion?->code) }}" placeholder="EP 1"
                   class="sim-form-input sim-btn-sm {{ $errors->has('code') && $openOnError ? 'border-sim-danger' : '' }}">
        </div>
        <div class="sm:col-span-3">
            <label class="sim-form-label text-xs">Judul / Redaksi EP <span class="text-sim-danger">*</span></label>
            <input type="text" name="title" maxlength="255" required
                   value="{{ old('title', $criterion?->title) }}" placeholder="Teks resmi elemen penilaian"
                   class="sim-form-input sim-btn-sm {{ $errors->has('title') && $openOnError ? 'border-sim-danger' : '' }}">
        </div>
    </div>
    <div>
        <label class="sim-form-label text-xs">Teks Persyaratan</label>
        <textarea name="requirement_text" rows="2"
                  placeholder="Detail persyaratan yang wajib dipenuhi (opsional)"
                  class="sim-form-textarea sim-btn-sm">{{ old('requirement_text', $criterion?->requirement_text) }}</textarea>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label class="sim-form-label text-xs">Kelengkapan Bukti</label>
            <textarea name="evidence_guidance" rows="3"
                      placeholder="Satu bukti per baris, mis.:&#10;Kebijakan yang disahkan&#10;Daftar hadir sosialisasi"
                      class="sim-form-textarea sim-btn-sm">{{ old('evidence_guidance', $criterion?->evidence_guidance) }}</textarea>
        </div>
        <div>
            <label class="sim-form-label text-xs">Panduan Penilaian</label>
            <textarea name="assessment_guidance" rows="3"
                      placeholder="Cara menentukan nilai sesuai / sebagian / tidak sesuai (opsional)"
                      class="sim-form-textarea sim-btn-sm">{{ old('assessment_guidance', $criterion?->assessment_guidance) }}</textarea>
        </div>
    </div>
    <div class="flex items-end justify-between gap-3">
        <div class="w-32">
            <label class="sim-form-label text-xs">Skor Maksimal</label>
            <input type="number" name="max_score" min="0" max="100"
                   value="{{ old('max_score', $criterion?->max_score ?? 10) }}"
                   class="sim-form-input sim-btn-sm {{ $errors->has('max_score') && $openOnError ? 'border-sim-danger' : '' }}">
        </div>
        <x-action action="save" icon="check-circle" :label="$submitLabel" size="sm" />
    </div>
</form>
