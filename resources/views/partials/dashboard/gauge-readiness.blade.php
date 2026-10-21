@props(['value' => 0, 'target' => '—'])

@php
    $pct = min(100, max(0, (int) $value));
    $circumference = 251.2;
    $offset = $circumference - ($circumference * $pct / 100);
@endphp

<div class="flex h-full flex-col">
    <div class="flex flex-1 flex-col items-center justify-center">
        <div class="relative h-36 w-full max-w-[220px]">
            <svg viewBox="0 0 220 130" class="h-full w-full" aria-hidden="true">
                <defs>
                    <linearGradient id="gaugeGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#0d9488"/>
                        <stop offset="100%" stop-color="#14b8a6"/>
                    </linearGradient>
                </defs>
                {{-- Grid arc background --}}
                <path d="M25 105 A85 85 0 0 1 195 105" fill="none" stroke="#e8edf2" stroke-width="18" stroke-linecap="round"/>
                {{-- Progress arc --}}
                <path d="M25 105 A85 85 0 0 1 195 105" fill="none" stroke="url(#gaugeGrad)" stroke-width="18" stroke-linecap="round"
                      stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}"/>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center pt-6">
                <span class="text-4xl font-bold leading-none text-slate-800">{{ $pct }}%</span>
                <span class="mt-1 text-sm font-medium text-slate-500">Siap</span>
            </div>
        </div>
    </div>
    <div class="sim-card-footer flex items-center gap-1.5">
        <svg class="h-3.5 w-3.5 shrink-0 text-sim-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span>Target Akreditasi: <strong class="font-semibold text-sim-ink">{{ $target }}</strong></span>
    </div>
</div>
