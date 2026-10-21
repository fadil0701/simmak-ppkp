@extends('layouts.simmak')

@section('title', 'Dashboard Akreditasi — SIMMAK PPKP')
@section('page-title', 'Dashboard Akreditasi — '.$accreditation_type)
@section('page-subtitle', 'Pantau progres dan kesiapan akreditasi secara real-time')

@section('content')
    @if (! $program)
        <div class="simmak-card p-8 text-center">
            <p class="text-slate-600">Belum ada program akreditasi aktif untuk klinik ini.</p>
            <p class="mt-2 text-sm text-slate-500">Jalankan seeder: <code class="rounded bg-slate-100 px-2 py-0.5">php artisan db:seed --class=AccreditationSeeder</code></p>
        </div>
    @else
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-slate-600">
                <span class="font-semibold text-slate-800">{{ $clinic?->name ?? $program->clinic?->name }}</span>
                <span class="mx-1.5 text-slate-300">·</span>
                {{ $accreditation_type }}
            </div>
            <div class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm">
                <svg class="h-4 w-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Periode Akreditasi: <strong class="font-semibold text-slate-800">{{ $program->cycle }}</strong>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-12">
            {{-- Struktur Akreditasi (tree) --}}
            <aside class="simmak-card xl:col-span-3">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Struktur Akreditasi</h3>
                    <p class="mt-0.5 text-sm font-semibold text-navy-900">Akreditasi {{ $accreditation_type }}</p>
                </div>
                <nav class="max-h-[42rem] space-y-1 overflow-y-auto p-3">
                    @forelse ($structure as $chapter)
                        <details class="group rounded-lg" @if ($chapter['expanded'] ?? false) open @endif>
                            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-2.5 py-2 text-sm hover:bg-slate-50">
                                <span class="h-2 w-2 shrink-0 rounded-full
                                    {{ match($chapter['status'] ?? 'low') {
                                        'good' => 'bg-emerald-500',
                                        'warn' => 'bg-amber-400',
                                        default => 'bg-orange-400',
                                    } }}"></span>
                                <span class="min-w-0 flex-1 font-medium text-slate-800">
                                    Bab {{ $chapter['number'] }}
                                    <span class="block truncate text-xs font-normal text-slate-500">{{ $chapter['title'] }}</span>
                                </span>
                                <span class="text-xs font-semibold text-slate-500">{{ $chapter['pct'] }}%</span>
                                <svg class="h-3.5 w-3.5 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <ul class="mb-1 ml-4 space-y-0.5 border-l border-slate-200 py-1 pl-3">
                                @foreach ($chapter['standards'] as $std)
                                    <li>
                                        <a href="{{ route('accreditation.standards') }}" class="block rounded-md px-2 py-1.5 text-xs text-slate-600 hover:bg-teal-50 hover:text-teal-800">
                                            <span class="font-semibold text-slate-700">{{ $std['code'] }}</span>
                                            — {{ \Illuminate\Support\Str::limit($std['title'], 36) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @empty
                        <p class="px-2 py-4 text-sm text-slate-500">Belum ada struktur standar.</p>
                    @endforelse
                </nav>
                <div class="border-t border-slate-100 px-4 py-3">
                    <a href="{{ route('accreditation.standards') }}" class="inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline">
                        Kelola Standar
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </aside>

            {{-- Konten utama --}}
            <div class="min-w-0 space-y-4 xl:col-span-9">
                {{-- Baris 1: Kesiapan / Progres Bab / Gap --}}
                <div class="grid items-stretch gap-4 lg:grid-cols-3">
                    <div class="simmak-card flex flex-col overflow-hidden">
                        <div class="px-5 pt-4">
                            <h3 class="text-xs font-bold uppercase tracking-wide text-navy-900">Skor Kesiapan Akreditasi</h3>
                        </div>
                        <div class="flex flex-1 flex-col items-center justify-center px-5 py-4">
                            <div class="relative flex h-36 w-36 items-center justify-center">
                                <svg class="h-36 w-36 -rotate-90" viewBox="0 0 100 100" aria-hidden="true">
                                    <circle cx="50" cy="50" r="42" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                                    <circle cx="50" cy="50" r="42" fill="none" stroke="#0d9488" stroke-width="8"
                                            stroke-dasharray="{{ min(100, $readiness) * 2.64 }} 264" stroke-linecap="round"/>
                                </svg>
                                <div class="absolute text-center">
                                    <div class="text-3xl font-bold text-teal-700">{{ $readiness }}%</div>
                                    <div class="text-xs font-medium text-slate-500">Siap</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-auto flex items-center gap-2 border-t border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-800">
                            <svg class="h-4 w-4 shrink-0 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            <span>Terus pertahankan dan tingkatkan kualitas pelayanan!</span>
                        </div>
                    </div>

                    <div class="simmak-card flex flex-col">
                        <div class="px-5 pt-4">
                            <h3 class="text-xs font-bold uppercase tracking-wide text-navy-900">Progres Per Bab</h3>
                        </div>
                        <div class="flex-1 space-y-3 px-5 py-4">
                            @foreach ($chapter_progress as $chapter)
                                <div>
                                    <div class="mb-1 flex justify-between gap-2 text-xs">
                                        <span class="truncate text-slate-600" title="{{ $chapter['title'] }}">Bab {{ $chapter['number'] }} — {{ \Illuminate\Support\Str::limit($chapter['title'], 22) }}</span>
                                        <span class="shrink-0 font-semibold text-slate-800">{{ $chapter['pct'] }}%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-slate-100">
                                        <div class="h-2 rounded-full {{ $chapter['color'] }}" style="width: {{ $chapter['pct'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="simmak-card flex flex-col">
                        <div class="px-5 pt-4">
                            <h3 class="text-xs font-bold uppercase tracking-wide text-navy-900">Ringkasan Analisis Kesenjangan</h3>
                        </div>
                        <div class="grid flex-1 grid-cols-3 gap-2 px-5 py-4">
                            <div class="rounded-xl bg-red-50 px-2 py-4 text-center">
                                <div class="text-2xl font-bold text-red-600">{{ $gap_summary['critical'] }}</div>
                                <div class="mt-1 text-[10px] font-medium leading-tight text-red-700">Gap Kritikal</div>
                            </div>
                            <div class="rounded-xl bg-orange-50 px-2 py-4 text-center">
                                <div class="text-2xl font-bold text-orange-500">{{ $gap_summary['major'] }}</div>
                                <div class="mt-1 text-[10px] font-medium leading-tight text-orange-700">Gap Mayor</div>
                            </div>
                            <div class="rounded-xl bg-emerald-50 px-2 py-4 text-center">
                                <div class="text-2xl font-bold text-emerald-600">{{ $gap_summary['minor'] }}</div>
                                <div class="mt-1 text-[10px] font-medium leading-tight text-emerald-700">Gap Minor</div>
                            </div>
                        </div>
                        <a href="{{ route('accreditation.gaps') }}" class="sim-btn sim-btn-primary mt-auto mx-5 mb-4 flex items-center justify-between">
                            <span>Total Gap</span>
                            <span>{{ $gap_summary['total'] }}</span>
                        </a>
                    </div>
                </div>

                {{-- Baris 2: Self Assessment / Mock Survey / Evidence --}}
                <div class="grid items-stretch gap-4 lg:grid-cols-3">
                    @foreach ([
                        [
                            'title' => 'Self Assessment',
                            'score' => $self_assessment['score'],
                            'caption' => 'Kelengkapan Self Assessment',
                            'lines' => [
                                'Tanggal Mulai: '.($self_assessment['started_at'] ?? '—'),
                                'Terakhir Diperbarui: '.($self_assessment['updated_at'] ?? '—'),
                            ],
                            'href' => route('accreditation.self-assessment'),
                            'btn' => 'Lihat Detail Self Assessment',
                        ],
                        [
                            'title' => 'Hasil Survei Simulasi',
                            'score' => $mock_survey['score'],
                            'caption' => 'Skor Survei Simulasi',
                            'lines' => [
                                'Tanggal Survey: '.($mock_survey['survey_date'] ?? '—'),
                                'Oleh: '.($mock_survey['surveyor'] ?? '—'),
                            ],
                            'href' => route('accreditation.mock-survey'),
                            'btn' => 'Lihat Laporan Survei Simulasi',
                        ],
                        [
                            'title' => 'Repositori Bukti',
                            'score' => $evidence['pct'],
                            'caption' => 'Kelengkapan Upload Bukti',
                            'lines' => [
                                'Total Bukti Diperlukan: '.$evidence['total'],
                                'Bukti Terunggah: '.$evidence['uploaded'],
                            ],
                            'href' => route('accreditation.evidence'),
                            'btn' => 'Kelola Repositori Bukti',
                        ],
                    ] as $card)
                        <div class="simmak-card flex h-full flex-col p-5">
                            <h3 class="text-xs font-bold uppercase tracking-wide text-navy-900">{{ $card['title'] }}</h3>
                            <div class="mt-4 flex flex-1 items-start gap-4">
                                <div class="relative flex h-24 w-24 shrink-0 items-center justify-center">
                                    <svg class="h-24 w-24 -rotate-90" viewBox="0 0 100 100" aria-hidden="true">
                                        <circle cx="50" cy="50" r="42" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                                        <circle cx="50" cy="50" r="42" fill="none" stroke="#14b8a6" stroke-width="8"
                                                stroke-dasharray="{{ min(100, $card['score']) * 2.64 }} 264" stroke-linecap="round"/>
                                    </svg>
                                    <div class="absolute text-lg font-bold text-teal-700">{{ $card['score'] }}%</div>
                                </div>
                                <div class="min-w-0 pt-1">
                                    <p class="text-sm font-medium text-slate-700">{{ $card['caption'] }}</p>
                                    @foreach ($card['lines'] as $line)
                                        <p class="mt-1 text-xs text-slate-500">{{ $line }}</p>
                                    @endforeach
                                </div>
                            </div>
                            <a href="{{ $card['href'] }}" class="sim-btn sim-btn-secondary mt-auto inline-flex w-full items-center justify-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                {{ $card['btn'] }}
                            </a>
                        </div>
                    @endforeach
                </div>

                {{-- Baris 3: Timeline + Aktivitas --}}
                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="simmak-card lg:col-span-2">
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Linimasa Menuju Survei Akreditasi</h3>
                                <p class="text-xs text-slate-500">Tahapan menuju survei resmi</p>
                            </div>
                            <div class="rounded-lg bg-teal-50 px-3 py-1.5 text-center">
                                <div class="text-[10px] uppercase tracking-wide text-teal-700">Menuju Survei</div>
                                <div class="text-sm font-bold text-teal-800">{{ $days_to_survey ?? 0 }} hari lagi</div>
                            </div>
                        </div>
                        <div class="px-5 py-6">
                            <div class="relative flex items-start justify-between gap-2">
                                <div class="absolute left-[10%] right-[10%] top-5 hidden h-0.5 bg-slate-200 sm:block"></div>
                                @foreach ($timeline as $step)
                                    @php
                                        $dotClass = match ($step['status']) {
                                            'done' => 'bg-emerald-500 text-white',
                                            'active' => 'bg-teal-600 text-white ring-4 ring-teal-100',
                                            default => 'bg-slate-200 text-slate-500',
                                        };
                                        $labelClass = match ($step['status']) {
                                            'done' => 'text-emerald-700',
                                            'active' => 'text-teal-700',
                                            default => 'text-slate-500',
                                        };
                                    @endphp
                                    <div class="relative z-10 flex flex-1 flex-col items-center text-center">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold {{ $dotClass }}">
                                            @if ($step['status'] === 'done')
                                                ✓
                                            @elseif (($step['icon'] ?? '') === 'star')
                                                ★
                                            @else
                                                {{ $loop->iteration }}
                                            @endif
                                        </div>
                                        <div class="mt-2 text-xs font-semibold {{ $labelClass }}">{{ $step['label'] }}</div>
                                        <div class="mt-0.5 text-[10px] text-slate-400">
                                            {{ match ($step['status']) {
                                                'done' => 'Selesai',
                                                'active' => 'Berjalan',
                                                default => 'Akan Datang',
                                            } }}
                                        </div>
                                        @if (! empty($step['date']))
                                            <div class="text-[10px] text-slate-400">{{ $step['date'] }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-6 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                                Survei akreditasi dijadwalkan
                                <strong>{{ $program->target_survey_date?->format('d F Y') }}</strong>
                                oleh <strong>{{ $program->type?->issuing_body ?? 'LAFKI' }}</strong>.
                            </div>
                        </div>
                    </div>

                    <div class="simmak-card flex flex-col">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h3 class="text-sm font-bold text-slate-800">Aktivitas Terkini</h3>
                        </div>
                        <div class="flex-1 space-y-4 px-5 py-4">
                            @foreach ($activities as $activity)
                                <div class="flex gap-3">
                                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full
                                        {{ match($activity['icon'] ?? 'doc') {
                                            'upload' => 'bg-blue-100 text-blue-600',
                                            'alert' => 'bg-amber-100 text-amber-600',
                                            'check' => 'bg-emerald-100 text-emerald-600',
                                            default => 'bg-slate-100 text-slate-600',
                                        } }}">
                                        @if (($activity['icon'] ?? '') === 'upload')
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                        @elseif (($activity['icon'] ?? '') === 'alert')
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                        @elseif (($activity['icon'] ?? '') === 'check')
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        @else
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-slate-800">{{ $activity['text'] }}</p>
                                        <p class="text-xs text-slate-400">{{ $activity['time'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-auto border-t border-slate-100 px-5 py-3">
                            <a href="{{ route('accreditation.gaps') }}" class="inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline">
                                Lihat Semua Aktivitas
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
