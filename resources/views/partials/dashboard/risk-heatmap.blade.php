@props(['heatmap', 'total' => 0, 'compact' => false])

@if ($compact)
    @include('partials.dashboard.risk-heatmap-compact', ['heatmap' => $heatmap])
@else
<div class="flex h-full flex-col">
    <div class="flex flex-1 gap-3">
        {{-- Y-axis: Dampak --}}
        <div class="flex flex-col items-end justify-between py-1 pr-0.5 text-[9px] font-medium text-slate-400" style="height: 9.5rem;">
            <span>5</span><span>4</span><span>3</span><span>2</span><span>1</span>
        </div>

        <div class="flex-1">
            <div class="mb-0.5 text-[9px] font-medium text-slate-400">Dampak</div>
            <div class="grid grid-cols-5 gap-1">
                @for ($l = 5; $l >= 1; $l--)
                    @for ($i = 1; $i <= 5; $i++)
                        @php
                            $count = $heatmap[$l][$i] ?? 0;
                            $hex = match(true) {
                                ($l * $i) >= 20 => '#b91c1c',
                                ($l * $i) >= 12 => '#ef4444',
                                ($l * $i) >= 8 => '#fb923c',
                                ($l * $i) >= 4 => '#facc15',
                                default => '#4ade80',
                            };
                        @endphp
                        <div class="relative flex aspect-square items-center justify-center rounded-md" style="background-color: {{ $hex }}">
                            @if ($count > 0)
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-900/75 text-[9px] font-bold text-white">{{ $count }}</span>
                            @endif
                        </div>
                    @endfor
                @endfor
            </div>
            <div class="mt-1.5 flex justify-between px-0.5 text-[9px] text-slate-400">
                <span>1</span><span>2</span><span>3</span><span>4</span><span>5</span>
            </div>
            <div class="mt-0.5 text-center text-[9px] text-slate-400">Kemungkinan</div>
        </div>

        {{-- Legend kanan seperti mockup --}}
        <div class="flex flex-col justify-center gap-1.5 pl-1 text-[8px] text-slate-500">
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-red-700"></span> Sangat Tinggi</span>
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-red-500"></span> Tinggi</span>
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-orange-400"></span> Sedang</span>
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-green-400"></span> Rendah</span>
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-green-600"></span> Sangat Rendah</span>
        </div>
    </div>

    <div class="sim-card-footer">
        Total Risiko: <strong class="font-semibold text-sim-ink">{{ $total }}</strong>
    </div>
</div>
@endif
