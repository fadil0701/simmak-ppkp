<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report_type }} Report</title>
    <style>
        body {
            font-family: 'Segoe UI', ui-sans-serif, system-ui, sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header h1 {
            color: #1e3a5f;
            font-size: 16px;
            margin: 0;
        }
        .header .subtitle {
            font-size: 10px;
            color: #6b7280;
            margin-top: 4px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .meta-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            font-size: 10px;
        }
        .meta-table td.label {
            background-color: #f1f5f9;
            font-weight: bold;
            width: 30%;
            color: #475569;
        }
        .section {
            margin-bottom: 16px;
        }
        .section h2 {
            font-size: 12px;
            color: #1e3a5f;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        table.data-table th {
            background-color: #1e3a5f;
            color: white;
            padding: 4px 6px;
            text-align: left;
            border: 1px solid #1e3a5f;
        }
        table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 6px;
            vertical-align: top;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .summary-box {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 8px;
            margin-bottom: 8px;
            border-radius: 4px;
        }
        .summary-box .label {
            font-size: 9px;
            color: #64748b;
        }
        .summary-box .value {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a5f;
        }
        .footer {
            margin-top: 20px;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-size: 8px;
            color: #6b7280;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $document_title ?? 'SIMMAK Audit Internal Klinik' }}</h1>
        <div class="subtitle">PPKP Provinsi DKI Jakarta · dokumen sistem, bukan sertifikat atau hasil akreditasi resmi</div>
    </div>

    <table class="meta-table">
        <tr>
            <td class="label">Nomor Laporan</td>
            <td>{{ $report_number ?? '-' }}</td>
            <td class="label">Tipe / Status</td>
            <td>{{ ucfirst(str_replace('_', ' ', $report_type ?? '-')) }} · {{ $document_status ?? 'generated' }}</td>
        </tr>
        <tr>
            <td class="label">Periode</td>
            <td>{{ $period_start ?? '-' }} s.d. {{ $period_end ?? '-' }}</td>
            <td class="label">Klinik</td>
            <td>{{ $clinic_name ?? 'Ruang lingkup pengguna' }}</td>
        </tr>
        <tr>
            <td class="label">Dibuat Oleh</td>
            <td>{{ $generated_by ?? '-' }}</td>
            <td class="label">Tanggal / Versi</td>
            <td>{{ $generated_at ?? '-' }} · v{{ $document_version ?? '1' }}</td>
        </tr>
    </table>

    @if (! empty($executive_summary))
        <div class="section">
            <h2>Ringkasan</h2>
            <p>{{ $executive_summary }}</p>
        </div>
    @endif

    @if (isset($plan_vs_actual) && count($plan_vs_actual))
        <div class="section">
            <h2>Rencana vs Realisasi Program</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Program</th>
                        <th>Status</th>
                        <th class="text-right">Eksekusi</th>
                        <th class="text-right">Difinalisasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plan_vs_actual as $row)
                        <tr>
                            <td>{{ $row['code'] }} — {{ $row['name'] }}</td>
                            <td>{{ $row['status'] }}</td>
                            <td class="text-right">{{ $row['planned'] }}</td>
                            <td class="text-right">{{ $row['finalized'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if (isset($findings_summary) && is_array($findings_summary))
        <div class="section">
            <h2>Ringkasan Temuan Audit</h2>
            <div class="summary-box">
                <div class="label">Total Temuan</div>
                <div class="value">{{ $findings_summary['total'] ?? 0 }}</div>
            </div>
            @if (isset($findings_summary['by_status']) && is_array($findings_summary['by_status']))
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th class="text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($findings_summary['by_status'] as $status => $count)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $status)) }}</td>
                                <td class="text-right">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif

    @if (isset($capa_summary) && is_array($capa_summary))
        <div class="section">
            <h2>Ringkasan CAPA</h2>
            <div class="summary-box">
                <div class="label">Total CAPA</div>
                <div class="value">{{ $capa_summary['total'] ?? 0 }}</div>
            </div>
            @if (isset($capa_summary['by_status']) && is_array($capa_summary['by_status']))
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th class="text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($capa_summary['by_status'] as $status => $count)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $status)) }}</td>
                                <td class="text-right">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif

    @if (isset($findings) && $findings->count() > 0)
        <div class="section">
            <h2>Daftar Temuan Audit</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Kode Temuan</th>
                        <th style="width: 30%;">Judul</th>
                        <th style="width: 20%;">Klinik</th>
                        <th style="width: 12%;">Risiko</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 18%;">PIC / Tenggat</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($findings as $finding)
                        <tr>
                            <td>{{ $finding->finding_code ?? '-' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($finding->title ?? '-', 40) }}</td>
                            <td>{{ $finding->clinic?->name ?? '-' }}</td>
                            <td>{{ ucfirst($finding->severity ?? '-') }}</td>
                            <td>{{ $finding->status ?? '-' }}</td>
                            <td>
                                {{ $finding->owner?->name ?? $finding->assignee?->name ?? '-' }}<br>
                                <small>{{ $finding->due_at?->format('d M Y') ?? '-' }}</small>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if (isset($capa_cases) && $capa_cases->count() > 0)
        <div class="section">
            <h2>CAPA dan Verifikasi</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Klinik</th>
                        <th>Status</th>
                        <th>Verifikasi terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($capa_cases as $capa)
                        @php $last = $capa->verifications->sortByDesc('verification_date')->first(); @endphp
                        <tr>
                            <td>{{ $capa->case_number }}</td>
                            <td>{{ $capa->clinic?->name ?? '—' }}</td>
                            <td>{{ $capa->status }}</td>
                            <td>{{ $last ? $last->result.' ('.$last->verification_date?->format('d M Y').')' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if (isset($approvals) && $approvals->count() > 0)
        <div class="section">
            <h2>Riwayat Persetujuan Program</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Langkah</th>
                        <th>Keputusan</th>
                        <th>Aktor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($approvals as $event)
                        <tr>
                            <td>{{ $event->acted_at?->format('d M Y H:i') }}</td>
                            <td>{{ $event->step_key }}</td>
                            <td>{{ $event->decision }}</td>
                            <td>{{ $event->actor?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="footer">
        {{ $document_title ?? 'SIMMAK Audit Internal Klinik' }} · dihasilkan {{ $generated_at ?? now()->format('d M Y H:i') }}.
        Bukan sertifikat atau hasil akreditasi resmi.
    </div>
</body>
</html>
