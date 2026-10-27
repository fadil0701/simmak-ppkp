@extends('layouts.simmak')

@section('title', 'Form Pelaksanaan Audit — SIMMAK PPKP')
@section('page-title', 'Form Pelaksanaan Audit')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span>Audit Internal</span>
    </li>
    <li class="flex items-center gap-1 text-sim-muted">
        @include('partials.icons.chevron-right')
        <a href="{{ route('audit.executions.index') }}" class="hover:text-sim-ink hover:underline">Pelaksanaan Audit</a>
    </li>
    <li class="flex items-center gap-1 text-sim-ink">
        @include('partials.icons.chevron-right')
        <span class="font-medium text-sim-mono">{{ $execution->execution_code }}</span>
    </li>
@endsection

@section('content')
    @php
        $statusTone = [
            'preparation' => 'border-sim-neutral bg-sim-neutral-bg text-sim-neutral',
            'in_progress' => 'border-sim-info bg-sim-info-bg text-sim-info',
            'submitted_for_review' => 'border-sim-warning bg-sim-warning-bg text-sim-warning',
            'finalized' => 'border-sim-success bg-sim-success-bg text-sim-success',
        ];
        $statusLabels = [
            'preparation' => 'Persiapan',
            'in_progress' => 'Berjalan',
            'submitted_for_review' => 'Menunggu tinjauan',
            'finalized' => 'Final',
        ];
        // Skala nilai kesesuaian: 10 = sesuai, 5 = sesuai sebagian, 0 = tidak
        // sesuai. `not_applicable` tetap tersedia untuk EP yang tidak relevan.
        $scoreOptions = \App\Support\AuditScoreScale::options();
        $notApplicableMeta = ['label' => 'Tidak berlaku', 'class' => 'border-sim-neutral bg-sim-neutral-bg text-sim-neutral'];

        $canExecute = auth()->user()->can('execute', $execution);
        $canSubmit = auth()->user()->can('submit', $execution);
        $canFinalize = auth()->user()->can('finalize', $execution);
        // Super admin (administrator sistem) boleh mengoreksi hasil audit yang
        // sudah diserahkan/difinalisasi; peran lain tetap mengikuti status.
        $superAdminOverride = auth()->user()->hasUnrestrictedAccess();
        $editable = $canExecute && (
            $execution->status === 'in_progress'
            || ($superAdminOverride && in_array($execution->status, ['submitted_for_review', 'finalized'], true))
        );

        $criteriaIds = $sections->flatMap(fn ($section) => $section->criteria)->pluck('id');
        $scopedResponses = $execution->responses->whereIn('standard_criterion_id', $criteriaIds->all());
        $responsesByCriterion = $scopedResponses->keyBy('standard_criterion_id');
        $assessedResponses = $scopedResponses->filter(fn ($response) => filled($response->response_value));

        $distribution = [
            'compliant' => $assessedResponses->where('response_value', 'compliant')->count(),
            'partial' => $assessedResponses->where('response_value', 'partial')->count(),
            'non_compliant' => $assessedResponses->where('response_value', 'non_compliant')->count(),
            'not_applicable' => $assessedResponses->where('response_value', 'not_applicable')->count(),
            'not_assessable' => $assessedResponses->where('response_value', 'not_assessable')->count(),
            'unassessed' => max(0, $criteriaIds->count() - $assessedResponses->count()),
        ];
        $incomplete = $incompleteCriteria ?? collect();
    @endphp

    {{-- Kepala halaman: identitas eksekusi + aksi utama --}}
    <div class="sim-page-header">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sim-mono text-xs text-sim-muted">{{ $execution->execution_code }}</span>
                <span class="sim-badge sim-badge-status {{ $statusTone[$execution->status] ?? 'border-sim-border bg-sim-surface text-sim-muted' }}">
                    {{ $statusLabels[$execution->status] ?? $execution->status }}
                </span>
            </div>
            <h2 class="sim-page-title mt-1">{{ $execution->title }}</h2>
            <p class="sim-page-subtitle">
                {{ $execution->clinic?->name ?? 'Klinik belum ditetapkan' }}
                · Instrumen {{ $execution->instrumentVersion?->version_label ?? '—' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-action href="{{ route('audit.executions.index') }}" icon="chevron-left" label="Kembali" variant="outline" />

            @if ($execution->status === 'preparation' && $canExecute)
                <x-action href="{{ route('audit.executions.start', $execution) }}" method="POST" icon="play-circle"
                          label="Mulai Audit" confirm="Mulai eksekusi audit ini sekarang?" />
            @endif

            @if ($execution->status === 'in_progress' && $canSubmit)
                <x-action href="{{ route('audit.executions.submit', $execution) }}" method="POST" icon="check-circle"
                          label="Serahkan untuk Tinjauan" variant="secondary"
                          confirm="Serahkan hasil audit untuk tinjauan? Pastikan seluruh EP sudah dinilai." />
            @endif

            @if ($execution->status === 'submitted_for_review' && $canFinalize)
                <x-action href="{{ route('audit.executions.finalize', $execution) }}" method="POST" icon="check-circle"
                          label="Finalisasi" confirm="Finalisasi hasil audit ini?" />
            @endif
        </div>
    </div>

    @if ($errors->any())
        <div class="sim-alert sim-alert-error mb-6">
            <p class="font-semibold">Periksa kembali isian berikut:</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Identitas singkat eksekusi --}}
    <div class="sim-card mb-6">
        <div class="sim-card-body">
            <div class="sim-meta-grid">
                <div>
                    <div class="sim-meta-label">Klinik / Unit</div>
                    <div class="sim-meta-value">{{ $execution->clinic?->code ?? '—' }} — {{ $execution->clinic?->name ?? 'Belum ditetapkan' }}</div>
                </div>
                <div>
                    <div class="sim-meta-label">Penugasan</div>
                    <div class="sim-meta-value text-sim-mono">{{ $execution->assignment?->assignment_code ?? '—' }}</div>
                </div>
                <div>
                    <div class="sim-meta-label">Versi Instrumen</div>
                    <div class="sim-meta-value">{{ $execution->instrumentVersion?->version_label ?? '—' }}</div>
                </div>
                <div>
                    <div class="sim-meta-label">Mulai Audit</div>
                    <div class="sim-meta-value">{{ $execution->started_at?->format('d M Y') ?? 'Belum dimulai' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Progres pengisian instrumen --}}
    <div class="sim-card mb-6">
        <div class="sim-card-body">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h3 class="sim-card-title">Progres Pengisian Tabel</h3>
                    <p class="mt-1 text-sm text-sim-muted">
                        <strong class="text-sim-ink">{{ $stats['completed'] }} dari {{ $stats['total'] }} EP</strong> telah dinilai.
                        Angka ini kelengkapan pengisian, bukan persentase kepatuhan Kemenkes.
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-semibold text-sim-ink" id="progress-persentase">{{ $stats['percentage'] }}%</div>
                    <div class="text-xs text-sim-muted">terisi</div>
                </div>
            </div>

            <div class="sim-progress mt-4">
                <div class="sim-progress-bar {{ $stats['percentage'] >= 100 ? 'sim-progress-bar-success' : '' }}"
                     id="progress-pengisian" style="width: {{ $stats['percentage'] }}%"></div>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ([
                    ['key' => 'compliant', 'label' => 'Sesuai', 'dot' => 'bg-sim-success'],
                    ['key' => 'partial', 'label' => 'Sesuai sebagian', 'dot' => 'bg-sim-warning'],
                    ['key' => 'non_compliant', 'label' => 'Tidak sesuai', 'dot' => 'bg-sim-danger'],
                    ['key' => 'not_applicable', 'label' => 'Tidak berlaku', 'dot' => 'bg-sim-neutral'],
                    ['key' => 'not_assessable', 'label' => 'Belum dinilai', 'dot' => 'bg-sim-border'],
                    ['key' => 'unassessed', 'label' => 'Belum diisi', 'dot' => 'bg-sim-border-light'],
                ] as $item)
                    <div class="rounded-md border border-sim-border-light bg-sim-surface-alt px-3 py-2">
                        <dt class="flex items-center gap-1.5 text-xs text-sim-muted">
                            <span class="inline-block h-2 w-2 rounded-full {{ $item['dot'] }}"></span>{{ $item['label'] }}
                        </dt>
                        <dd class="mt-0.5 text-lg font-semibold text-sim-ink" data-counter="{{ $item['key'] }}">
                            {{ $distribution[$item['key']] }}
                        </dd>
                    </div>
                @endforeach
            </dl>

            @if ($incomplete->isNotEmpty())
                <details class="mt-4 rounded-md border border-sim-warning/30 bg-sim-warning-bg px-3 py-2">
                    <summary class="cursor-pointer text-sm font-medium text-sim-warning">
                        {{ $incomplete->count() }} EP belum dinilai — lihat daftar
                    </summary>
                    <ul class="mt-2 grid gap-1 text-xs text-sim-muted sm:grid-cols-2">
                        @foreach ($incomplete as $criterion)
                            <li>
                                <span class="text-sim-mono">{{ $criterion->code }}</span> —
                                {{ \Illuminate\Support\Str::limit($criterion->title, 70) }}
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-sim-muted">
                        Seluruh EP wajib dinilai sebelum eksekusi dapat diserahkan untuk tinjauan.
                    </p>
                </details>
            @endif
        </div>
    </div>

    {{-- Keterangan mode halaman --}}
    @if ($execution->status === 'preparation')
        <div class="sim-alert sim-alert-info mb-6">
            <p class="font-semibold">Eksekusi belum dimulai</p>
            <p class="text-sim-muted">
                Tabel baru dapat diisi setelah audit dimulai.
                {{ $canExecute ? 'Gunakan tombol Mulai Audit di atas.' : 'Auditor penanggung jawab yang memulai audit ini.' }}
            </p>
        </div>
    @elseif ($execution->status === 'submitted_for_review')
        <div class="sim-alert sim-alert-info mb-6">
            <p class="font-semibold">Menunggu tinjauan</p>
            <p class="text-sim-muted">
                {{ $superAdminOverride
                    ? 'Anda masuk sebagai super admin sehingga tabel tetap dapat dikoreksi; setiap perubahan terekam pada riwayat revisi.'
                    : 'Tabel bersifat baca. Perubahan hanya dapat dilakukan melalui pengembalian oleh peninjau.' }}
            </p>
        </div>
    @elseif ($execution->status === 'finalized')
        <div class="sim-alert sim-alert-success mb-6">
            <p class="font-semibold">Hasil audit final</p>
            <p class="text-sim-muted">
                Ditetapkan {{ $execution->finalized_at?->format('d M Y') ?? '—' }}. {{ $superAdminOverride ? 'Tabel tetap dapat dikoreksi sebagai super admin; perubahan terekam pada riwayat revisi.' : 'Tabel bersifat baca.' }}
            </p>
        </div>
    @elseif (! $canExecute)
        <div class="sim-alert sim-alert-info mb-6">
            <p class="font-semibold">Mode baca</p>
            <p class="text-sim-muted">Anda bukan anggota tim penugasan ini, sehingga nilai kesesuaian hanya dapat dilihat.</p>
        </div>
    @endif

    @if ($sections->isEmpty())
        <div class="sim-card">
            <div class="sim-card-body">
                <div class="sim-empty-state">
                    <div class="sim-empty-icon">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <p class="sim-empty-title">Instrumen belum memiliki EP</p>
                    <p class="sim-empty-description">
                        Versi instrumen ini belum memuat elemen penilaian. Tambahkan EP pada menu Standar &amp; Instrumen
                        sebelum audit dilaksanakan.
                    </p>
                    <div class="mt-4">
                        @can('audit.standard.view')
                            <x-action href="{{ route('audit.standards.index') }}" icon="book-open" label="Buka Standar & Instrumen" variant="secondary" size="sm" />
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    @else
        @if ($editable)
            <form method="POST" action="{{ route('audit.executions.table', $execution) }}" id="form-tabel-audit">
                @csrf
        @endif

        <div class="space-y-4">
            @foreach ($sections as $section)
                @php
                    $sectionTotal = $section->criteria->count();
                    $sectionAssessed = $section->criteria
                        ->filter(fn ($criterion) => filled($responsesByCriterion->get($criterion->id)?->response_value))
                        ->count();
                    $sectionPercent = $sectionTotal > 0 ? (int) round(($sectionAssessed / $sectionTotal) * 100) : 0;
                @endphp

                <div class="sim-card">
                    <div class="sim-card-header flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="sim-card-title">{{ $section->code }} — {{ $section->title }}</h3>
                            @if ($section->description)
                                <p class="mt-1 text-sm text-sim-muted">{{ $section->description }}</p>
                            @endif
                        </div>
                        <div class="flex flex-shrink-0 items-center gap-2">
                            <div class="sim-progress w-24">
                                <div class="sim-progress-bar {{ $sectionPercent >= 100 ? 'sim-progress-bar-success' : '' }}"
                                     style="width: {{ $sectionPercent }}%"></div>
                            </div>
                            <span class="text-xs text-sim-muted">{{ $sectionAssessed }}/{{ $sectionTotal }} terisi</span>
                        </div>
                    </div>

                    <div class="sim-card-body overflow-x-auto p-0">
                        <table class="sim-table w-full">
                            <thead>
                                <tr>
                                    <th class="w-24">EP</th>
                                    <th class="min-w-[260px]">Elemen Penilaian</th>
                                    <th class="min-w-[220px]">Kelengkapan Bukti</th>
                                    <th class="w-52">Nilai Kesesuaian</th>
                                    <th class="min-w-[240px]">Rekomendasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($section->criteria as $criterion)
                                    @php
                                        $response = $responsesByCriterion->get($criterion->id);
                                        $storedValue = (string) old("rows.{$criterion->id}.response_value", $response?->response_value ?? '');
                                        $scoreValue = \App\Support\AuditScoreScale::toScore($storedValue);
                                        $isNotApplicable = $storedValue === \App\Support\AuditScoreScale::NOT_APPLICABLE;
                                        $selectedValue = $scoreValue !== null
                                            ? (string) $scoreValue
                                            : ($isNotApplicable ? \App\Support\AuditScoreScale::NOT_APPLICABLE : '');
                                        $recommendationValue = old("rows.{$criterion->id}.recommendation", $response?->recommendation);
                                        $needsRecommendation = \App\Support\AuditScoreScale::requiresRecommendation($scoreValue);
                                        $lastRevision = $response?->revisions->first();
                                    @endphp
                                    <tr data-criterion-row
                                        @class([
                                            'align-top',
                                            'bg-sim-danger-bg/50' => $scoreValue === \App\Support\AuditScoreScale::NONE,
                                            'bg-sim-warning-bg/50' => $scoreValue === \App\Support\AuditScoreScale::PARTIAL,
                                        ])>
                                        <td class="text-sim-mono text-xs text-sim-muted">{{ $criterion->code }}</td>
                                        <td class="text-sm text-sim-ink">{{ $criterion->title }}</td>
                                        <td class="text-xs text-sim-muted">
                                            @if ($criterion->evidence_guidance)
                                                <ul class="list-disc space-y-0.5 pl-4">
                                                    @foreach (preg_split('/\r\n|\r|\n/', $criterion->evidence_guidance) as $line)
                                                        @if (trim($line) !== '')
                                                            <li>{{ trim($line) }}</li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($editable)
                                                <input type="hidden" name="rows[{{ $criterion->id }}][standard_criterion_id]" value="{{ $criterion->id }}">
                                                <select name="rows[{{ $criterion->id }}][response_value]" class="sim-form-select text-sm"
                                                        data-conformity-select aria-label="Nilai kesesuaian {{ $criterion->code }}">
                                                    <option value="">— Pilih —</option>
                                                    @foreach ($scoreOptions as $optionValue => $optionLabel)
                                                        <option value="{{ $optionValue }}" @selected($selectedValue === (string) $optionValue)>
                                                            {{ $optionValue }} — {{ $optionLabel }}
                                                        </option>
                                                    @endforeach
                                                    <option value="{{ \App\Support\AuditScoreScale::NOT_APPLICABLE }}" @selected($selectedValue === \App\Support\AuditScoreScale::NOT_APPLICABLE)>
                                                        NA — Tidak berlaku
                                                    </option>
                                                </select>
                                            @else
                                                @if ($scoreValue !== null)
                                                    <span class="sim-badge sim-badge-status {{ \App\Support\AuditScoreScale::badgeClassFor($scoreValue) }}">
                                                        {{ \App\Support\AuditScoreScale::displayFor($scoreValue) }}
                                                    </span>
                                                @elseif ($isNotApplicable)
                                                    <span class="sim-badge sim-badge-status {{ $notApplicableMeta['class'] }}">
                                                        {{ $notApplicableMeta['label'] }}
                                                    </span>
                                                @else
                                                    <span class="sim-badge sim-badge-status border-sim-border bg-sim-surface text-sim-muted">Belum dinilai</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            @if ($editable)
                                                <textarea name="rows[{{ $criterion->id }}][recommendation]" rows="2"
                                                          class="sim-form-textarea text-sm" data-recommendation
                                                          aria-label="Rekomendasi {{ $criterion->code }}"
                                                          placeholder="Wajib diisi jika tidak sesuai atau sesuai sebagian">{{ $recommendationValue }}</textarea>
                                                <p class="sim-form-hint {{ $needsRecommendation ? '' : 'hidden' }}" data-recommendation-hint>
                                                    Rekomendasi wajib diisi agar temuan otomatis dibuat.
                                                </p>
                                            @else
                                                <div class="text-sm text-sim-ink">{{ filled($recommendationValue) ? $recommendationValue : '—' }}</div>
                                            @endif

                                            @if ($lastRevision)
                                                <p class="mt-1 text-xs text-sim-muted">
                                                    Riwayat perubahan: {{ $response->revisions->count() }}× · terakhir
                                                    {{ $lastRevision->created_at?->format('d M Y H:i') }}
                                                    @if ($lastRevision->assessedBy)
                                                        oleh {{ $lastRevision->assessedBy->name }}
                                                    @endif
                                                </p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($editable)
                <div class="sim-save-bar">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-sim-muted">
                        <span class="font-medium text-sim-ink">
                            <span data-counter="assessed">{{ $stats['completed'] }}</span> dari {{ $stats['total'] }} EP dinilai
                        </span>
                        <span class="hidden sm:inline">
                            EP tidak sesuai / sesuai sebagian yang disertai rekomendasi otomatis menjadi temuan &amp; CAPA.
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="hidden text-xs text-sim-muted lg:inline">
                            Temuan tidak tertutup sebelum auditor memvalidasi tindak lanjut klinik.
                        </span>
                        <x-action icon="check-circle" label="Simpan Tabel" />
                    </div>
                </div>
            </form>
        @endif
    @endif

    @if ($editable)
        <script>
            (function () {
                var form = document.getElementById('form-tabel-audit');
                if (!form) return;

                var bar = document.getElementById('progress-pengisian');
                var percentLabel = document.getElementById('progress-persentase');

                function bucketOf(value) {
                    if (!value) return 'unassessed';
                    if (value === 'not_applicable') return 'not_applicable';
                    var score = parseInt(value, 10);
                    if (isNaN(score)) return 'not_assessable';
                    if (score >= 10) return 'compliant';
                    if (score > 0) return 'partial';
                    return 'non_compliant';
                }

                function refresh() {
                    var rows = Array.prototype.slice.call(form.querySelectorAll('[data-criterion-row]'));
                    var tally = { compliant: 0, partial: 0, non_compliant: 0, not_applicable: 0, not_assessable: 0, unassessed: 0 };

                    rows.forEach(function (row) {
                        var select = row.querySelector('[data-conformity-select]');
                        var textarea = row.querySelector('[data-recommendation]');
                        var hint = row.querySelector('[data-recommendation-hint]');
                        var value = select ? select.value : '';
                        var score = value === '' || value === 'not_applicable' ? null : parseInt(value, 10);
                        var bucket = bucketOf(value);
                        var needsRecommendation = score !== null && score < 10;

                        tally[bucket] = (tally[bucket] || 0) + 1;

                        row.classList.toggle('bg-sim-danger-bg/50', bucket === 'non_compliant');
                        row.classList.toggle('bg-sim-warning-bg/50', bucket === 'partial');

                        if (hint) {
                            hint.classList.toggle('hidden', !needsRecommendation);
                        }
                        if (textarea) {
                            textarea.classList.toggle('border-sim-danger', needsRecommendation && textarea.value.trim() === '');
                        }
                    });

                    var assessed = rows.length - tally.unassessed;

                    document.querySelectorAll('[data-counter]').forEach(function (element) {
                        var key = element.getAttribute('data-counter');
                        element.textContent = String(key === 'assessed' ? assessed : (tally[key] || 0));
                    });

                    var percent = rows.length > 0 ? Math.round((assessed / rows.length) * 100) : 0;
                    if (bar) bar.style.width = percent + '%';
                    if (percentLabel) percentLabel.textContent = percent + '%';
                }

                form.addEventListener('change', function (event) {
                    if (event.target.matches('[data-conformity-select]')) refresh();
                });

                form.addEventListener('input', function (event) {
                    if (event.target.matches('[data-recommendation]')) refresh();
                });

                refresh();
            })();
        </script>
    @endif
@endsection