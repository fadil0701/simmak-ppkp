{{--
  Tombol standar SIMMAK.
  Pemakaian:
    <x-btn>Simpan</x-btn>
    <x-btn variant="outline" href="{{ route('x') }}">Batal</x-btn>
    <x-btn variant="danger" size="sm" type="submit">Hapus</x-btn>
  Variant: primary (default), secondary, outline, danger.
  Size: md (default), sm, icon.
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'submit',
])

@php
    $classes = 'sim-btn sim-btn-'.$variant;
    if ($size === 'sm') {
        $classes .= ' sim-btn-sm';
    } elseif ($size === 'icon') {
        $classes .= ' sim-btn-icon';
    }
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
