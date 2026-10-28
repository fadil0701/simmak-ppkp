@props([
    'action',
    'method' => 'POST',
    'section' => null,
    'submitLabel' => 'Simpan Seksi',
    'formKey' => 'section-new',
])

@php
    $isEdit = $method !== 'POST';
@endphp

<form method="POST" action="{{ $action }}" class="mt-2 space-y-2">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif
    <input type="hidden" name="_form" value="{{ $formKey }}">
    <input type="text" name="code" required maxlength="80" placeholder="Kode seksi"
           value="{{ old('code', $section?->code) }}" class="sim-form-input sim-btn-sm">
    <input type="text" name="title" required maxlength="255" placeholder="Judul seksi"
           value="{{ old('title', $section?->title) }}" class="sim-form-input sim-btn-sm">
    <textarea name="description" rows="2" placeholder="Deskripsi (opsional)"
              class="sim-form-textarea sim-btn-sm">{{ old('description', $section?->description) }}</textarea>
    <x-action action="save" icon="check-circle" :label="$submitLabel" size="sm" />
</form>
