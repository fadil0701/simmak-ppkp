{{--
  Tombol standar SIMMAK (dipakai bila tidak memerlukan ikon).

  Warna mengikuti palet aksi baku — lihat App\Support\UiAction.
  Prop `action` eksplisit; bila tidak diisi, jenis aksi ditebak dari teks slot.

  Pemakaian:
    <x-btn action="save">Simpan Standar</x-btn>
    <x-btn action="cancel" href="{{ route('x') }}">Batal</x-btn>
    <x-btn action="delete" size="sm" type="submit">Hapus</x-btn>

  Palet: back, create, save, edit, delete, detail, approve, reject,
         cancel, upload, download, import, export, print, start, escalate.
--}}
@props([
    'variant' => null,
    'action' => null,
    'size' => 'md',
    'href' => null,
    'type' => 'submit',
])

@php
    $slotText = trim(preg_replace('/\s+/', ' ', strip_tags((string) $slot)));

    $resolvedAction = $action ?? \App\Support\UiAction::guess(null, $slotText);

    if ($resolvedAction !== null && \App\Support\UiAction::has($resolvedAction)) {
        $classes = \App\Support\UiAction::classes($resolvedAction, $size);
    } else {
        $variant ??= 'outline';
        $classes = 'sim-btn sim-btn-'.$variant;

        if ($size === 'sm') {
            $classes .= ' sim-btn-sm';
        } elseif ($size === 'icon') {
            $classes .= ' sim-btn-icon';
        }
    }
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
