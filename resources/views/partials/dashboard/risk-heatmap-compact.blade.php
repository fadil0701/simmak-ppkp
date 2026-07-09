@props(['heatmap'])

<div class="grid grid-cols-5 gap-1">
    @for ($l = 5; $l >= 1; $l--)
        @for ($i = 1; $i <= 5; $i++)
            @php
                $count = $heatmap[$l][$i] ?? 0;
                $color = app(\App\Services\RiskService::class)->heatmapColor($l, $i);
            @endphp
            <div class="flex aspect-square items-center justify-center rounded text-[9px] font-bold {{ $color }} {{ $count > 0 ? 'text-white' : 'text-white/60' }}">
                {{ $count > 0 ? $count : '' }}
            </div>
        @endfor
    @endfor
</div>
