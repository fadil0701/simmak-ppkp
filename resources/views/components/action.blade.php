{{--
  Tombol aksi seragam SIMMAK — selalu memakai ikon + teks ringkas.

  WARNA DITENTUKAN OLEH JENIS AKSI (lihat App\Support\UiAction).
  Gunakan prop action; hindari variant manual agar warna tidak melenceng.

  Pemakaian:
    <x-action action="create" :href="route('x.create')" label="Tambah" />
    <x-action action="edit" :href="route('x.edit', $item)" />
    <x-action action="delete" :href="route('x.destroy', $item)" method="DELETE" confirm="Hapus data ini?" />
    <x-action action="back" :href="route('x.index')" />
    <x-action action="download" :href="route('x.export')" label="Ekspor CSV" />

  Palet baku:
    back     netral (abu outline)   create  teal    save    navy
    edit     amber                  delete  merah   detail  biru outline
    approve  hijau                  reject  oranye  cancel  abu
    upload   teal outline           download/export  slate outline
    print    navy outline

  Variant lama (primary/secondary/outline/danger/ghost) masih diterima untuk
  kompatibilitas, tetapi hanya dipakai bila aksi tidak dapat ditentukan.
--}}
@props([
    'variant' => null,
    'action' => null,
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'label' => null,
    'type' => 'submit',
    'method' => null,
    'confirm' => null,
    'title' => null,
    'fields' => [],
])

@php
    // Tanpa prop action: tebak dari ikon/teks supaya tombol lama ikut baku.
    $resolvedAction = $action;

    if ($resolvedAction === null && $icon !== null) {
        $resolvedAction = \App\Support\UiAction::guess($icon, $label);
    }

    if ($resolvedAction !== null && \App\Support\UiAction::has($resolvedAction)) {
        $classes = \App\Support\UiAction::classes($resolvedAction, $size);
        $icon ??= \App\Support\UiAction::icon($resolvedAction);
        $label ??= \App\Support\UiAction::label($resolvedAction);
    } else {
        $variant ??= 'outline';
        $classes = 'sim-btn sim-btn-'.$variant;

        if ($size === 'sm') {
            $classes .= ' sim-btn-sm';
        } elseif ($size === 'icon') {
            $classes .= ' sim-btn-icon';
        }
    }

    $needsForm = $method !== null;
@endphp

@if ($needsForm)
    <form method="POST" action="{{ $href }}" class="inline"
          @if ($confirm) onsubmit="return confirm(@js($confirm))" @endif>
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif
        @foreach ($fields as $fieldName => $fieldValue)
            <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">
        @endforeach
        <button type="submit" @if ($title) title="{{ $title }}" @endif
                {{ $attributes->class($classes) }}>
            @if ($icon)<span class="inline-flex">@include('partials.icons.'.$icon)</span>@endif
            @if ($label)<span>{{ $label }}</span>@endif
        </button>
    </form>
@elseif ($href)
    <a href="{{ $href }}" @if ($title) title="{{ $title }}" @endif
       {{ $attributes->class($classes) }}>
        @if ($icon)<span class="inline-flex">@include('partials.icons.'.$icon)</span>@endif
        @if ($label)<span>{{ $label }}</span>@endif
    </a>
@else
    <button type="{{ $type }}" @if ($title) title="{{ $title }}" @endif
            @if ($confirm) onclick="return confirm(@js($confirm))" @endif
            {{ $attributes->class($classes) }}>
        @if ($icon)<span class="inline-flex">@include('partials.icons.'.$icon)</span>@endif
        @if ($label)<span>{{ $label }}</span>@endif
    </button>
@endif
