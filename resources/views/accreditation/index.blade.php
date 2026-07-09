@extends('layouts.simmak')

@section('title', 'Dashboard Akreditasi — SIMMAK PPKP')
@section('page-title', 'Dashboard Akreditasi — '.$accreditation_type)
@section('page-subtitle', $clinic?->name ?? 'PPKP Provinsi DKI Jakarta')

@section('content')
    @if (! $program)
        <div class="simmak-card p-8 text-center">
            <p class="text-slate-600">Belum ada program akreditasi aktif untuk klinik ini.</p>
            <p class="mt-2 text-sm text-slate-500">Jalankan seeder: <code class="rounded bg-slate-100 px-2 py-0.5">php artisan db:seed --class=AccreditationSeeder</code></p>
        </div>
    @else
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-slate-600">
                Periode Akreditasi <span class="font-semibold text-slate-800">{{ $program->cycle }}</span>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600">
                Target Survei: <span class="font-semibold text-teal-700">{{ $program->target_survey_date->format('d M Y') }}</span>
            </div>
        </div>

        <div class="mb-6 grid gap-4 lg:grid-cols-3">
            {{-- Skor Kesiapan --}}
            <div class="simmak-card p-6">
                <h3 class="text-sm font-semibold text-slate-700">Skor Kesiapan Akreditasi</h3>
                <div class="mt-4 flex items-center gap-6">
                    <div class="relative flex h-28 w-28 items-center justify-center">
                        <svg class="h-28 w-28 -rotate-90" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="42" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                            <circle cx="50" cy="50" r="42" fill="none" stroke="#0d9488" stroke-width="8"
                                    stroke-dasharray="{{ $readiness * 2.64 }} 264" stroke-linecap="round"/>
                        </svg>
                        <div class="absolute text-center">
                            <div class="text-2xl font-bold text-teal-700">{{ $readiness }}%</div>
                            <div class="text-[10px] text-slate-500">Siap</div>
                        </div>
                    </div>
                    <p class="text-sm leading-relaxed text-slate-600">
                        @if ($readiness >= 80)
                            Klinik dalam kondisi <strong class="text-emerald-700">siap</strong> menuju survei akreditasi.
                        @else
                            Perlu peningkatan pada bab dengan progres di bawah 80%.
                        @endif
                    </p>
                </div>
            </div>

            {{-- Progres Per Bab --}}
            <div class="simmak-card p-6">
                <h3 class="text-sm font-semibold text-slate-700">Progres Per Bab</h3>
                <div class="mt-4 space-y-3">
                    @foreach ($chapter_progress as $chapter)
                        <div>
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="text-slate-600">Bab {{ $chapter['number'] }} — {{ \Illuminate\Support\Str::limit($chapter['title'], 28) }}</span>
                                <span class="font-semibold text-slate-800">{{ $chapter['pct'] }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full {{ $chapter['color'] }}" style="width: {{ $chapter['pct'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Gap Analysis --}}
            <div class="simmak-card p-6">
                <h3 class="text-sm font-semibold text-slate-700">Ringkasan Analisis Kesenjangan</h3>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-red-50 px-4 py-3">
                        <span class="text-sm text-red-800">Kesenjangan Kritikal</span>
                        <span class="text-2xl font-bold text-red-700">{{ $gap_summary['critical'] }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-orange-50 px-4 py-3">
                        <span class="text-sm text-orange-800">Kesenjangan Mayor</span>
                        <span class="text-2xl font-bold text-orange-600">{{ $gap_summary['major'] }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-4 py-3">
                        <span class="text-sm text-emerald-800">Kesenjangan Ringan</span>
                        <span class="text-2xl font-bold text-emerald-700">{{ $gap_summary['minor'] }}</span>
                    </div>
                </div>
                <div class="mt-4 rounded-lg bg-blue-50 px-4 py-2 text-center text-sm font-semibold text-blue-800">
                    Total Kesenjangan: {{ $gap_summary['total'] }}
                </div>
                <a href="{{ route('accreditation.gaps') }}" class="mt-3 block text-center text-sm font-medium text-teal-700 hover:underline">Lihat Detail Kesenjangan →</a>
            </div>
        </div>

        <div class="mb-6 grid gap-4 lg:grid-cols-3">
            @foreach ([
                ['label' => 'Self Assessment', 'score' => $self_assessment['score'], 'detail1' => 'Mulai: '.$self_assessment['started_at'], 'detail2' => 'Pembaruan: '.$self_assessment['updated_at'], 'link' => route('accreditation.self-assessment'), 'btn' => 'Lihat Detail Self Assessment'],
                ['label' => 'Hasil Survei Simulasi', 'score' => $mock_survey['score'], 'detail1' => 'Tanggal: '.$mock_survey['survey_date'], 'detail2' => 'Surveor: '.$mock_survey['surveyor'], 'link' => route('accreditation.mock-survey'), 'btn' => 'Lihat Laporan Survei Simulasi'],
                ['label' => 'Repositori Bukti', 'score' => $evidence['pct'], 'detail1' => 'Diperlukan: '.$evidence['total'], 'detail2' => 'Terunggah: '.$evidence['uploaded'], 'link' => route('accreditation.evidence'), 'btn' => 'Kelola Repositori Bukti'],
            ] as $card)
                <div class="simmak-card p-6 text-center">
                    <h3 class="text-sm font-semibold text-slate-700">{{ $card['label'] }}</h3>
                    <div class="relative mx-auto mt-4 flex h-24 w-24 items-center justify-center">
                        <svg class="h-24 w-24 -rotate-90" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="42" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                            <circle cx="50" cy="50" r="42" fill="none" stroke="#14b8a6" stroke-width="8"
                                    stroke-dasharray="{{ $card['score'] * 2.64 }} 264" stroke-linecap="round"/>
                        </svg>
                        <div class="absolute text-xl font-bold text-teal-700">{{ $card['score'] }}%</div>
                    </div>
                    <div class="mt-3 space-y-1 text-xs text-slate-500">
                        <div>{{ $card['detail1'] }}</div>
                        <div>{{ $card['detail2'] }}</div>
                    </div>
                    <a href="{{ $card['link'] }}" class="mt-4 inline-block w-full rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">{{ $card['btn'] }}</a>
                </div>
            @endforeach
        </div>

        <div class="mb-6 grid gap-6 lg:grid-cols-3">
            <div class="simmak-card lg:col-span-2">
                <div class="simmak-card-header flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Linimasa Menuju Survei Akreditasi</h3>
                    @php $days = max(0, now()->diffInDays($program->target_survey_date, false)); @endphp
                    <span class="rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-800">{{ $days }} hari lagi</span>
                </div>
                <div class="simmak-card-body">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        @foreach ($timeline as $step)
                            @php
                                $dotClass = match($step['status']) {
                                    'done' => 'bg-emerald-500 text-white',
                                    'active' => 'bg-teal-600 text-white ring-4 ring-teal-100',
                                    default => 'bg-slate-200 text-slate-500',
                                };
                            @endphp
                            <div class="flex flex-1 flex-col items-center text-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold {{ $dotClass }}">
                                    @if ($step['status'] === 'done') ✓ @else {{ $loop->iteration }} @endif
                                </div>
                                <div class="mt-2 text-xs font-medium text-slate-700">{{ $step['label'] }}</div>
                                @if ($step['date'])
                                    <div class="text-[10px] text-slate-500">{{ $step['date'] }}</div>
                                @endif
                            </div>
                            @if (! $loop->last)
                                <div class="hidden h-0.5 flex-1 bg-slate-200 sm:block"></div>
                            @endif
                        @endforeach
                    </div>
                    <div class="mt-6 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        Survei akreditasi dijadwalkan <strong>{{ $program->target_survey_date->format('d F Y') }}</strong>
                        oleh <strong>{{ $program->type?->issuing_body ?? 'KARS' }}</strong>.
                    </div>
                </div>
            </div>

            <div class="simmak-card">
                <div class="simmak-card-header">
                    <h3 class="font-semibold text-slate-800">Aktivitas Terkini</h3>
                </div>
                <div class="simmak-card-body space-y-4">
                    @foreach ($activities as $activity)
                        <div class="flex gap-3">
                            <div class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                                @if ($activity['icon'] === 'upload')
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                @elseif ($activity['icon'] === 'alert')
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                @else
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                @endif
                            </div>
                            <div>
                                <div class="text-sm text-slate-800">{{ $activity['text'] }}</div>
                                <div class="text-xs text-slate-500">{{ $activity['time'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if (count($structure) > 0)
            <div class="simmak-card">
                <div class="simmak-card-header">
                    <h3 class="font-semibold text-slate-800">Struktur Akreditasi {{ $accreditation_type }}</h3>
                </div>
                <div class="simmak-card-body grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($structure as $chapter)
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                            <div class="font-semibold text-slate-800">Bab {{ $chapter['number'] }}</div>
                            <div class="text-sm text-slate-600">{{ $chapter['title'] }}</div>
                            <ul class="mt-2 space-y-1 text-xs text-slate-500">
                                @foreach ($chapter['standards'] as $std)
                                    <li>{{ $std['code'] }} — {{ \Illuminate\Support\Str::limit($std['title'], 40) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
@endsection
