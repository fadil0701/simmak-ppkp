@extends('layouts.simmak')

@section('title', 'Dashboard Audit Internal — SIMMAK PPKP')
@section('page-title', 'Dashboard Audit Internal')
@section('page-subtitle', 'Tinjauan tugas prioritas dan status audit internal seluruh klinik')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span>Dashboard Audit</span>
    </li>
@endsection

@section('content')
    <!-- Pekerjaan Prioritas -->
    <div class="sim-card mb-6">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Pekerjaan yang Perlu Diselesaikan</h3>
        </div>
        <div class="sim-card-body">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @if ($reviewQueue->isNotEmpty())
                    <div class="sim-card p-4 border-l-4 border-sim-warning bg-sim-warning/5">
                        <div class="text-xs font-semibold text-sim-warning uppercase mb-2">Antrean Tinjauan Eksekusi</div>
                        <div class="text-2xl font-bold text-sim-ink">{{ $reviewQueue->count() }}</div>
                        <div class="text-xs text-sim-muted mt-1">Eksekusi yang diserahkan dan menunggu tinjauan verifikator.</div>
                        <a href="{{ route('audit.executions.checklist', $reviewQueue->first()->id ?? null) }}" class="mt-3 inline-block text-sm font-medium text-sim-primary hover:underline">Tinjau sekarang →</a>
                    </div>
                @else
                    <div class="sim-card p-4 border-l-4 border-sim-success bg-sim-success-bg">
                        <div class="text-xs font-semibold text-sim-success uppercase mb-2">Antrean Tinjauan Eksekusi</div>
                        <div class="text-2xl font-bold text-sim-ink">0</div>
                        <div class="text-xs text-sim-muted mt-1">Tidak ada eksekusi yang menunggu tinjauan.</div>
                    </div>
                @endif

                @if ($highRiskFindings->isNotEmpty())
                    <div class="sim-card p-4 border-l-4 border-sim-danger bg-sim-danger-bg">
                        <div class="text-xs font-semibold text-sim-danger uppercase mb-2">Temuan Risiko Tinggi</div>
                        <div class="text-2xl font-bold text-sim-ink">{{ $highRiskFindings->count() }}</div>
                        <div class="text-xs text-sim-muted mt-1">Temuan kritis dan tinggi yang membutuhkan perhatian segera.</div>
                        <a href="{{ route('findings.index') }}?severity=critical" class="mt-3 inline-block text-sm font-medium text-sim-primary hover:underline">Lihat semua temuan →</a>
                    </div>
                @else
                    <div class="sim-card p-4 border-l-4 border-sim-success bg-sim-success-bg">
                        <div class="text-xs font-semibold text-sim-success uppercase mb-2">Temuan Risiko Tinggi</div>
                        <div class="text-2xl font-bold text-sim-ink">0</div>
                        <div class="text-xs text-sim-muted mt-1">Tidak ada temuan risiko tinggi terbuka.</div>
                    </div>
                @endif

                @if ($overdueActions->isNotEmpty())
                    <div class="sim-card p-4 border-l-4 border-sim-danger bg-sim-danger-bg">
                        <div class="text-xs font-semibold text-sim-danger uppercase mb-2">Tindak Lanjut Terlambat</div>
                        <div class="text-2xl font-bold text-sim-ink">{{ $overdueActions->count() }}</div>
                        <div class="text-xs text-sim-muted mt-1">CAPA yang telah melewati tenggat penyelesaian.</div>
                        <a href="{{ route('capa.index') }}?status=overdue" class="mt-3 inline-block text-sm font-medium text-sim-primary hover:underline">Tindak lanjuti →</a>
                    </div>
                @else
                    <div class="sim-card p-4 border-l-4 border-sim-success bg-sim-success-bg">
                        <div class="text-xs font-semibold text-sim-success uppercase mb-2">Tindak Lanjut Terlambat</div>
                        <div class="text-2xl font-bold text-sim-ink">0</div>
                        <div class="text-xs text-sim-muted mt-1">Semua tindak lanjut tepat waktu.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Jadwal Audit Terdekat dan Temuan per Klinik -->
    <div class="grid gap-6 lg:grid-cols-12">
        <!-- Jadwal Audit -->
        <div class="lg:col-span-5">
            <div class="sim-card mb-6">
                <div class="sim-card-header flex items-center justify-between">
                    <div>
                        <h3 class="sim-card-title">Jadwal Audit Berikutnya</h3>
                        <p class="text-xs text-sim-muted">Program audit yang telah disetujui dan dijadwalkan</p>
                    </div>
                    <a href="{{ route('audit.programs.index') }}" class="text-sm font-medium text-sim-primary hover:underline">Semua Program →</a>
                </div>
                <div class="sim-card-body p-0">
                    @php
                        $upcoming = \App\Models\AuditSchedule::forUserClinics(auth()->user())
                            ->where('status', 'scheduled')
                            ->with(['clinic', 'program', 'leadAuditor'])
                            ->orderBy('scheduled_start')
                            ->limit(5)
                            ->get();
                    @endphp
                    <table class="sim-table w-full">
                        <thead>
                            <tr>
                                <th class="w-20">Tanggal</th>
                                <th>Klinik</th>
                                <th class="text-right w-24">Program</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($upcoming->isNotEmpty())
                                @foreach ($upcoming as $schedule)
                                    <tr>
                                        <td class="text-sim-mono text-sm">{{ $schedule->scheduled_start?->format('d M Y') }}</td>
                                        <td class="text-sm">{{ $schedule->clinic?->code }} — {{ $schedule->clinic?->name }}</td>
                                        <td class="text-right text-xs text-sim-muted">{{ $schedule->program?->program_code }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3" class="sim-table-empty">Tidak ada audit yang dijadwalkan.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Temuan Terbuka per Klinik -->
        <div class="lg:col-span-7">
            <div class="sim-card mb-6">
                <div class="sim-card-header flex items-center justify-between">
                    <div>
                        <h3 class="sim-card-title">Temuan Terbuka per Klinik</h3>
                        <p class="text-xs text-sim-muted">Jumlah temuan dengan status terbuka, dalam CAPA, atau menunggu verifikasi</p>
                    </div>
                    <a href="{{ route('findings.index') }}" class="text-sm font-medium text-sim-primary hover:underline">Lihat Semua →</a>
                </div>
                <div class="sim-card-body p-0">
                    <table class="sim-table w-full">
                        <thead>
                            <tr>
                                <th>Klinik</th>
                                <th class="text-center w-20">Terbuka</th>
                                <th class="text-center w-24">Tertinggi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($findingsByClinic->isNotEmpty())
                                @foreach ($findingsByClinic as $clinic)
                                    <tr>
                                        <td class="text-sm">{{ $clinic['code'] }} — {{ $clinic['name'] }}</td>
                                        <td class="text-center text-sim-mono text-sm {{ $clinic['open_findings'] > 0 ? 'text-sim-danger font-medium' : 'text-sim-muted' }}">
                                            {{ $clinic['open_findings'] }}
                                        </td>
                                        <td class="text-center text-sm">
                                            @php
                                                $severity = \App\Models\AuditFinding::forOrganization(auth()->user()->organization_id)
                                                    ->where('clinic_id', $clinic['clinic_id'])
                                                    ->whereIn('status', ['draft', 'under_review', 'confirmed', 'action_required', 'pending_verification'])
                                                    ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'major' THEN 2 WHEN 'minor' THEN 3 ELSE 4 END")
                                                    ->value('severity');
                                            @endphp
                                            <span class="sim-badge sim-badge-status
                                                @switch($severity)
                                                    @case('critical') border-sim-danger bg-sim-danger-bg text-sim-danger
                                                    @case('major') border-sim-warning bg-sim-warning-bg text-sim-warning
                                                    @default border-sim-success bg-sim-success-bg text-sim-success
                                                @endswitch
                                            ">
                                                {{ $severity ? \App\Support\UiLabel::findingSeverity($severity) : '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3" class="sim-table-empty">Belum ada data temuan.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Aktivitas Terbaru -->
    <div class="sim-card mb-6">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Aktivitas Terbaru</h3>
        </div>
        <div class="sim-card-body">
            @if (count($recentActivities) > 0)
                <ul class="divide-y divide-sim-border-light">
                    @foreach ($recentActivities as $activity)
                        <li class="px-4 py-3 text-sm">
                            <div class="font-medium text-sim-ink">{{ $activity['event'] }}</div>
                            <div class="flex items-center gap-2 text-xs text-sim-muted mt-0.5">
                                <span>{{ $activity['actor'] }}</span>
                                <span class="hidden sm:inline">•</span>
                                <span>{{ $activity['clinic'] }}</span>
                                <span class="hidden sm:inline">•</span>
                                <span>{{ $activity['time'] }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="sim-empty-state">
                    <div class="sim-empty-icon">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <div class="sim-empty-title">Belum ada aktivitas tercatat</div>
                    <div class="sim-empty-description">Aktivitas sistem akan muncul di sini setelah audit dimulai.</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Statistik Ringkasan (Ringkas, bukan 8 kartu) -->
    <div class="grid gap-4 sm:grid-cols-4">
        <div class="sim-card p-4">
            <div class="text-xs font-semibold text-sim-muted uppercase">Program Audit</div>
            <div class="text-2xl font-bold text-sim-ink mt-1">{{ $stats['programs'] }}</div>
            <div class="text-xs text-sim-muted mt-1">{{ $stats['programs_approved'] }} disetujui</div>
        </div>
        <div class="sim-card p-4">
            <div class="text-xs font-semibold text-sim-muted uppercase">Temuan Terbuka</div>
            <div class="text-2xl font-bold text-sim-ink mt-1">{{ $stats['findings_open'] }}</div>
            <div class="text-xs text-sim-muted mt-1">{{ $stats['findings_closed'] }} ditutup</div>
        </div>
        <div class="sim-card p-4">
            <div class="text-xs font-semibold text-sim-muted uppercase">CAPA Aktif</div>
            <div class="text-2xl font-bold text-sim-ink mt-1">{{ $stats['capa_open'] }}</div>
            <div class="text-xs text-sim-muted mt-1">{{ $stats['capa_closed'] }} selesai</div>
        </div>
        <div class="sim-card p-4">
            <div class="text-xs font-semibold text-sim-muted uppercase">Eksekusi Berjalan</div>
            <div class="text-2xl font-bold text-sim-ink mt-1">{{ $stats['executions_in_progress'] }}</div>
            <div class="text-xs text-sim-muted mt-1">{{ $stats['executions_submitted'] }} tinjauan</div>
        </div>
    </div>
@endsection