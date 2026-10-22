{{--
  Tombol aksi seragam SIMMAK — selalu memakai ikon + teks ringkas.

  Pemakaian:
    <x-action href="{{ route('x') }}" icon="plus" label="Tambah" />
    <x-action href="{{ route('x.edit', $item) }}" icon="pencil" label="Ubah" variant="outline" size="sm" />
    <x-action icon="trash" label="Hapus" variant="danger" size="sm" method="DELETE" confirm="Hapus data ini?" />
    <x-action href="..." icon="download" label="Unduh" variant="outline" size="sm" />

  Variant: primary (default), secondary, outline, danger, ghost.
  Size: md (default), sm, icon.
--}}
@props([
    'variant' => 'primary',
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
    $classes = 'sim-btn sim-btn-'.$variant;
    if ($size === 'sm') {
        $classes .= ' sim-btn-sm';
    } elseif ($size === 'icon') {
        $classes .= ' sim-btn-icon';
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
