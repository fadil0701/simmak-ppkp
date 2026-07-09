@props(['activities' => []])

<div class="space-y-4">
  @foreach ($activities as $activity)
    <div class="flex gap-3">
      <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full
        {{ match($activity['icon'] ?? 'chart') {
            'check' => 'bg-emerald-100 text-emerald-600',
            'doc' => 'bg-blue-100 text-blue-600',
            'alert' => 'bg-amber-100 text-amber-600',
            'user' => 'bg-violet-100 text-violet-600',
            default => 'bg-slate-100 text-slate-500',
        } }}">
        @if (($activity['icon'] ?? '') === 'check')
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        @elseif (($activity['icon'] ?? '') === 'doc')
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        @elseif (($activity['icon'] ?? '') === 'alert')
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        @else
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @endif
      </div>
      <div class="min-w-0">
        <p class="text-sm font-medium text-slate-800">{{ $activity['text'] }}</p>
        @if (!empty($activity['subtitle']))
          <p class="text-xs text-slate-500">{{ $activity['subtitle'] }}</p>
        @endif
        <p class="text-xs text-slate-400">{{ $activity['time'] }}</p>
      </div>
    </div>
  @endforeach
</div>
