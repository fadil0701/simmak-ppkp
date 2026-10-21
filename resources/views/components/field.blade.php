{{--
  Field input/select/textarea standar SIMMAK dengan label, hint, dan error.
  Pemakaian:
    <x-field name="name" label="Nama" required />
    <x-field name="type" label="Jenis" type="select" :options="$types" required />
    <x-field name="note" label="Catatan" type="textarea" :value="old('note')" hint="Opsional" />
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'options' => [],
    'hint' => null,
    'required' => false,
    'placeholder' => null,
    'rows' => 3,
])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $resolvedValue = $value ?? old($name);
    $baseClass = match ($type) {
        'select' => 'sim-form-select',
        'textarea' => 'sim-form-textarea',
        default => 'sim-form-input',
    };
@endphp

<div {{ $attributes->only('class')->class('sim-form-group') }}>
    @if ($label)
        <label class="sim-form-label" for="{{ $id }}">
            {{ $label }}@if ($required)<span class="sim-form-label-required"></span>@endif
        </label>
    @endif

    @if ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" @if ($required) required @endif
                {{ $attributes->except(['class', 'id'])->class([$baseClass, 'border-sim-danger' => $hasError]) }}>
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $resolvedValue === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
                  @if ($required) required @endif
                  @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                  {{ $attributes->except(['class', 'id'])->class([$baseClass, 'border-sim-danger' => $hasError]) }}>{{ $resolvedValue }}</textarea>
    @else
        <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ $resolvedValue }}"
               @if ($required) required @endif
               @if ($placeholder) placeholder="{{ $placeholder }}" @endif
               {{ $attributes->except(['class', 'id'])->class([$baseClass, 'border-sim-danger' => $hasError]) }}>
    @endif

    @if ($hint)
        <p class="sim-form-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="sim-form-error">{{ $message }}</p>
    @enderror
</div>
