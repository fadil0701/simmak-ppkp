<section class="mt-8 space-y-6" aria-labelledby="audit-dashboard-heading">
    <div class="border-t border-sim-border pt-6">
        <h2 id="audit-dashboard-heading" class="text-lg font-bold tracking-tight text-sim-navy-900">Dashboard Audit Internal</h2>
        <p class="mt-1 text-sm text-sim-muted">Antrian kerja, temuan prioritas, dan status audit seluruh klinik.</p>
    </div>

    <div class="sim-card">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Pekerjaan yang Perlu Diselesaikan</h3>
        </div>
        <div class="sim-card-body">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="sim-card p-4 border-l-4 {{ $reviewQueue->isNotEmpty() ? 'border-sim-warning bg-sim-warning/5' : 'border-sim-success bg-sim-success-bg' }}">
                    <div class="mb-2 text-xs font-semibold uppercase {{ $reviewQueue->isNotEmpty() ? 'text-sim-warning' : 'text-sim-success' }}">Antrean Tinjauan Eksekusi</div>
                    <div class="text-2xl font-bold text-sim-ink">{{ $reviewQueue->count() }}</div>
                    <div class="mt-1 text-xs text-sim-muted">
                        {{ $reviewQueue->isNotEmpty() ? 'Eksekusi yang diserahkan dan menunggu tinjauan.' : 'Tidak ada eksekusi yang menunggu tinjauan.' }}
                    </div>
                    @if ($reviewQueue->isNotEmpty())
                        <a href="{{ route('audit.executions.checklist', $reviewQueue->first()) }}" class="mt-3 inline-block text-sm font-medium text-sim-primary hover:underline">Tinjau sekarang →</a>
                    @endif
                </div>

                <div class="sim-card p-4 border-l-4 {{ $highRiskFindings->isNotEmpty() ? 'border-sim-danger bg-sim-danger-bg' : 'border-sim-success bg-sim-success-bg' }}">
                    <div class="mb-2 text-xs font-semibold uppercase {{ $highRiskFindings->isNotEmpty() ? 'text-sim-danger' : 'text-sim-success' }}">Temuan Risiko Tinggi</div>
                    <div class="text-2xl font-bold text-sim-ink">{{ $highRiskFindings->count() }}</div>
                    <div class="mt-1 text-xs text-sim-muted">
                        {{ $highRiskFindings->isNotEmpty() ? 'Temuan kritis yang membutuhkan perhatian segera.' : 'Tidak ada temuan kritis terbuka.' }}
                    </div>
                    <a href="{{ route('findings.index', ['severity' => 'critical']) }}" class="mt-3 inline-block text-sm font-medium text-sim-primary hover:underline">Lihat semua temuan →</a>
                </div>

                <div class="sim-card p-4 border-l-4 {{ $overdueActions->isNotEmpty() ? 'border-sim-danger bg-sim-danger-bg' : 'border-sim-success bg-sim-success-bg' }}">
                    <div class="mb-2 text-xs font-semibold uppercase {{ $overdueActions->isNotEmpty() ? 'text-sim-danger' : 'text-sim-success' }}">Tindak Lanjut Terlambat</div>
                    <div class="text-2xl font-bold text-sim-ink">{{ $overdueActions->count() }}</div>
                    <div class="mt-1 text-xs text-sim-muted">
                        {{ $overdueActions->isNotEmpty() ? 'CAPA yang telah melewati tenggat penyelesaian.' : 'Semua tindak lanjut tepat waktu.' }}
                    </div>
                    <a href="{{ route('capa.index') }}" class="mt-3 inline-block text-sm font-medium text-sim-primary hover:underline">Tindak lanjuti →</a>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-12">
        <div class="sim-card lg:col-span-5">
            <div class="sim-card-header flex items-center justify-between">
                <div>
                    <h3 class="sim-card-title">Jadwal Audit Berikutnya</h3>
                    <p class="text-xs text-sim-muted">Penugasan aktif atau diajukan, diurutkan menurut jadwal</p>
                </div>
                <a href="{{ route('audit.assignments.index') }}" class="text-xs font-medium text-sim-primary hover:underline">Penugasan</a>
            </div>
            <div class="overflow-x-auto">
                <table class="sim-table w-full">
                    <thead>
                        <tr>
                            <th>Jadwal</th>
                            <th>Klinik</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($upcomingAssignments as $assignment)
                            <tr>
                                <td class="whitespace-nowrap text-sm">{{ $assignment->scheduled_start?->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('audit.assignments.show', $assignment) }}" class="font-medium text-sim-ink hover:underline">{{ $assignment->clinic?->code ?? '—' }} — {{ $assignment->clinic?->name ?? $assignment->title }}</a>
                                </td>
                                <td class="text-xs text-sim-muted">{{ \App\Support\UiLabel::auditStatus($assignment->status) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="sim-table-empty">Belum ada penugasan terjadwal.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sim-card lg:col-span-7">
            <div class="sim-card-header flex items-center justify-between">
                <div>
                    <h3 class="sim-card-title">Temuan Terbuka per Klinik</h3>
                    <p class="text-xs text-sim-muted">Temuan terbuka, kritis, dan CAPA terlambat</p>
                </div>
                <a href="{{ route('findings.index') }}" class="text-xs font-medium text-sim-primary hover:underline">Lihat semua</a>
            </div>
            <div class="overflow-x-auto">
                <table class="sim-table w-full">
                    <thead>
                        <tr>
                            <th>Klinik</th>
                            <th class="text-center">Terbuka</th>
                            <th class="text-center">Kritis</th>
                            <th class="text-center">CAPA terlambat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($findingsByClinic as $clinicRow)
                            <tr>
                                <td>
                                    <a href="{{ route('findings.index', ['clinic_id' => $clinicRow['clinic_id']]) }}" class="font-medium text-sim-ink hover:underline">{{ $clinicRow['code'] }} — {{ $clinicRow['name'] }}</a>
                                    <div class="text-xs text-sim-muted">{{ $clinicRow['region'] }}</div>
                                </td>
                                <td class="text-center {{ $clinicRow['open_findings'] > 0 ? 'font-medium text-sim-ink' : 'text-sim-muted' }}">{{ $clinicRow['open_findings'] }}</td>
                                <td class="text-center {{ $clinicRow['critical_findings'] > 0 ? 'font-medium text-sim-danger' : 'text-sim-muted' }}">{{ $clinicRow['critical_findings'] }}</td>
                                <td class="text-center {{ $clinicRow['capa_overdue'] > 0 ? 'font-medium text-sim-danger' : 'text-sim-muted' }}">{{ $clinicRow['capa_overdue'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="sim-table-empty">Belum ada klinik yang dapat diaudit.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-12">
        <div class="sim-card lg:col-span-8">
            <div class="sim-card-header">
                <h3 class="sim-card-title">Aktivitas Audit Terbaru</h3>
            </div>
            <div class="sim-card-body p-0">
                @forelse ($recentActivities as $activity)
                    <div class="border-b border-sim-border-light px-4 py-3 text-sm last:border-0">
                        <div class="font-medium text-sim-ink">{{ $activity['event'] }}</div>
                        <div class="mt-0.5 text-xs text-sim-muted">{{ $activity['actor'] }} · {{ $activity['clinic'] }} · {{ $activity['time'] }}</div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-sim-muted">Belum ada jejak aktivitas audit.</p>
                @endforelse
            </div>
        </div>

        <div class="sim-card lg:col-span-4">
            <div class="sim-card-header">
                <h3 class="sim-card-title">Ringkasan periode berjalan</h3>
            </div>
            <div class="sim-card-body space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <span class="text-sim-muted">Program audit</span>
                    <span class="font-medium">{{ $auditStats['programs'] }} <span class="font-normal text-sim-muted">({{ $auditStats['programs_approved'] }} disetujui)</span></span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-sim-muted">Penugasan aktif</span>
                    <a href="{{ route('audit.assignments.index') }}" class="font-medium text-sim-primary hover:underline">{{ $auditStats['assignments_active'] }}</a>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-sim-muted">Eksekusi berjalan</span>
                    <a href="{{ route('audit.executions.index') }}" class="font-medium text-sim-primary hover:underline">{{ $auditStats['executions_in_progress'] }}</a>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-sim-muted">CAPA terbuka</span>
                    <a href="{{ route('capa.index') }}" class="font-medium text-sim-primary hover:underline">{{ $auditStats['capa_open'] }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
