@props(['items' => [], 'total' => 0])

@php
    $chartH = 88;
    $baseY = 108;
    $barW = 36;
    $gap = 18;
    $startX = 42;
    $gridLines = [0, 25, 50, 75, 100];
@endphp

<div class="flex h-full flex-col">
    <div class="flex-1">
    <svg viewBox="0 0 280 150" class="w-full" aria-hidden="true">
        {{-- Y-axis grid --}}
        @foreach ($gridLines as $g)
            @php $y = $baseY - ($g / 100) * $chartH; @endphp
            <line x1="28" y1="{{ $y }}" x2="252" y2="{{ $y }}" stroke="#f1f5f9" stroke-width="1"/>
            <text x="22" y="{{ $y + 3 }}" text-anchor="end" class="fill-slate-400" font-size="8">{{ $g }}%</text>
        @endforeach

        @foreach ($items as $i => $item)
            @php
                $pct = min(100, max(0, (int) ($item['pct'] ?? 0)));
                $barH = max($pct > 0 ? 4 : 0, ($pct / 100) * $chartH);
                $x = $startX + $i * ($barW + $gap);
                $y = $baseY - $barH;
                $hex = $item['hex'] ?? '#14b8a6';
            @endphp
            <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barW }}" height="{{ $barH }}" rx="4" fill="{{ $hex }}"/>
            <text x="{{ $x + $barW / 2 }}" y="{{ $y - 5 }}" text-anchor="middle" class="fill-slate-700" font-size="9" font-weight="600">{{ $pct }}%</text>
            <text x="{{ $x + $barW / 2 }}" y="{{ $baseY + 14 }}" text-anchor="middle" class="fill-slate-500" font-size="7.5">{{ $item['label'] }}</text>
        @endforeach
    </svg>
    </div>
    <div class="sim-card-footer">
        Total Tindak Lanjut: <strong class="font-semibold text-sim-ink">{{ $total }}</strong>
    </div>
</div>
