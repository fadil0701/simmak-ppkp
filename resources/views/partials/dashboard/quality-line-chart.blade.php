@props(['trends' => []])

@php
    $series = [
        ['key' => 'kepuasan', 'label' => 'Kepuasan Pasien', 'color' => '#14b8a6'],
        ['key' => 'waktu_tunggu', 'label' => 'Waktu Tunggu', 'color' => '#60a5fa'],
        ['key' => 'infeksi', 'label' => 'Kejadian Infeksi', 'color' => '#fbbf24', 'scale' => 8],
    ];
    $width = 560;
    $height = 180;
    $padX = 24;
    $padY = 16;
    $chartW = $width - $padX * 2;
    $chartH = $height - $padY * 2;
    $count = max(count($trends), 1);
    $pointsFor = function (string $key, ?int $scale = null) use ($trends, $count, $padX, $padY, $chartW, $chartH) {
        return collect($trends)->values()->map(function ($row, $i) use ($key, $scale, $count, $padX, $padY, $chartW, $chartH) {
            $value = (float) ($row[$key] ?? 0);
            if ($scale) {
                $value = min(100, $value * $scale);
            }
            $x = $padX + ($count > 1 ? ($i / ($count - 1)) * $chartW : $chartW / 2);
            $y = $padY + $chartH - ($value / 100) * $chartH;
            return round($x, 1).','.round($y, 1);
        })->implode(' ');
    };
@endphp

<div class="w-full overflow-x-auto">
  <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-48 w-full min-w-[320px]" preserveAspectRatio="xMidYMid meet">
    @for ($g = 0; $g <= 4; $g++)
      @php $gy = $padY + ($chartH / 4) * $g; @endphp
      <line x1="{{ $padX }}" y1="{{ $gy }}" x2="{{ $width - $padX }}" y2="{{ $gy }}" stroke="#f1f5f9" stroke-width="1"/>
    @endfor
    @foreach ($series as $s)
      <polyline fill="none" stroke="{{ $s['color'] }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                points="{{ $pointsFor($s['key'], $s['scale'] ?? null) }}"/>
      @foreach ($trends as $i => $row)
        @php
          $value = (float) ($row[$s['key']] ?? 0);
          if (! empty($s['scale'])) { $value = min(100, $value * $s['scale']); }
          $x = $padX + ($count > 1 ? ($i / ($count - 1)) * $chartW : $chartW / 2);
          $y = $padY + $chartH - ($value / 100) * $chartH;
        @endphp
        <circle cx="{{ $x }}" cy="{{ $y }}" r="3.5" fill="{{ $s['color'] }}" stroke="#fff" stroke-width="1.5"/>
      @endforeach
    @endforeach
  </svg>
  <div class="mt-2 flex flex-wrap justify-between gap-2 border-t border-slate-100 pt-2">
    @foreach ($trends as $trend)
      <span class="text-[10px] text-slate-500">{{ $trend['month'] }}</span>
    @endforeach
  </div>
  <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-600">
    @foreach ($series as $s)
      <span class="flex items-center gap-1.5">
        <span class="h-2 w-4 rounded-full" style="background: {{ $s['color'] }}"></span>
        {{ $s['label'] }}
      </span>
    @endforeach
  </div>
</div>
