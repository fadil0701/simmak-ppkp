{{--
  Kartu pilihan untuk daftar opsi panjang (peran, scope sistem, klinik, status).
  Seluruh area kartu dapat diklik dan ada penanda visual saat terpilih.

  Pemakaian:
    <x-check-card name="role_ids[]" value="{{ $role->name }}" :checked="$cek"
                  title="Kepala Pusat" description="Memantau seluruh klinik."
                  meta="struktural" />

  Props:
    tone="warning"  → aksen kuning (dipakai untuk peran lama yang tak lagi diberikan)
    disabled        → kartu tidak dapat diubah, tampil meredup
    type="radio"    → untuk pilihan tunggal
--}}
@props([
    'name',
    'value',
    'checked' => false,
    'title' => null,
    'description' => null,
    'meta' => null,
    'tone' => 'default',
    'disabled' => false,
    'type' => 'checkbox',
])

@php
    $id = $attributes->get('id') ?? 'pilih-'.\Illuminate\Support\Str::slug(str_replace(['[', ']'], '', $name).'-'.$value);
    $kartu = 'sim-check-card';

    if ($tone === 'warning') {
        $kartu .= ' sim-check-card-warning';
    }

    if ($disabled) {
        $kartu .= ' sim-check-card-off';
    }
@endphp

<label for="{{ $id }}" {{ $attributes->except('id')->class($kartu) }} @if ($disabled) aria-disabled="true" @endif>
    <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
           @checked($checked) @disabled($disabled)
           class="mt-0.5 h-4 w-4 shrink-0 rounded border-sim-border text-sim-primary focus:ring-sim-primary">

    <span class="min-w-0 flex-1">
        <span class="block text-sm font-medium text-sim-ink">{{ $title }}</span>
        @if ($description)
            <span class="mt-0.5 block text-xs leading-relaxed text-sim-muted">{{ $description }}</span>
        @endif
    </span>

    @if ($meta)
        <span class="sim-badge shrink-0 border-sim-border-light bg-sim-surface-alt text-[11px] font-normal text-sim-muted">
            {{ $meta }}
        </span>
    @endif
</label>
