{{--
  Kelompok isian bernomor dengan judul dan penjelasan singkat.
  Memakai .sim-form-fieldset / .sim-form-legend yang sudah ada di app.css.

  Pemakaian:
    <x-form-section step="1" title="Identitas Akun" description="Data dasar pengguna.">
        ...
    </x-form-section>
--}}
@props([
    'title',
    'step' => null,
    'description' => null,
])

<fieldset {{ $attributes->class('sim-form-fieldset') }}>
    <legend class="sim-form-legend w-full">
        @if ($step)
            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-sim-navy-800 text-xs font-semibold text-sim-white">
                {{ $step }}
            </span>
        @endif

        <span class="min-w-0">{{ $title }}</span>

        @isset($aside)
            <span class="ml-auto shrink-0">{{ $aside }}</span>
        @endisset
    </legend>

    @if ($description)
        <p class="-mt-1 mb-4 text-xs leading-relaxed text-sim-muted">{{ $description }}</p>
    @endif

    <div class="space-y-4">
        {{ $slot }}
    </div>
</fieldset>
