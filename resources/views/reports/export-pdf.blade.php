<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 15mm 20mm 15mm;
            @bottom-center {
                content: "SIMMAK — Audit Internal Klinik PPKP DKI Jakarta — Halaman " counter(page) " dari " counter(pages);
                font-family: 'Segoe UI', sans-serif;
                font-size: 8pt;
                color: #64748b;
            }
        }
        body {
            font-family: 'Segoe UI', ui-sans-serif, system-ui, sans-serif;
            font-size: 9pt;
            color: #0f172a;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .brand {
            font-size: 16pt;
            font-weight: bold;
            color: #1e3a5f;
            letter-spacing: 0.5px;
        }
        .brand-sub {
            font-size: 8pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .title {
            font-size: 13pt;
            font-weight: bold;
            margin-top: 10px;
            color: #1e3a5f;
        }
        .meta {
            font-size: 8pt;
            color: #64748b;
            margin-top: 4px;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        .meta-label {
            font-weight: bold;
            color: #334155;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #475569;
            text-align: left;
        }
        td {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            font-size: 8pt;
            vertical-align: top;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .summary {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 12px;
            margin-bottom: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 8.5pt;
        }
        .summary-label {
            color: #475569;
        }
        .summary-value {
            font-weight: bold;
            color: #0f172a;
        }
        .footer-note {
            font-size: 7pt;
            color: #64748b;
            margin-top: 20px;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }
        .badge-critical { background: #fef2f2; color: #dc2626; }
        .badge-high { background: #fff7ed; color: #ea580c; }
        .badge-medium { background: #fffbeb; color: #d97706; }
        .badge-low { background: #ecfdf5; color: #059669; }
        .badge-observation { background: #f8fafc; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">SIMMAK</div>
        <div class="brand-sub">Audit Internal Klinik &middot; PPKP DKI Jakarta</div>
        <div class="title">Laporan Hasil Audit Internal</div>
        <div class="meta">
            <div class="meta-row">
                <span class="meta-label">Periode:</span>
                <span>{{ $period_from }} s.d. {{ $period_to }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Dibuat pada:</span>
                <span>{{ $generated_at }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Dibuat oleh:</span>
                <span>{{ $generated_by }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Total Temuan:</span>
                <span class="summary-value">{{ $total_findings }}</span>
            </div>
        </div>
    </div>

    <div class="summary">
        <div class="summary-row">
            <span class="summary-label">Jumlah Temuan Terbuka:</span>
            <span class="summary-value">{{ $findings->whereIn('status', ['draft', 'under_review', 'confirmed', 'action_required', 'pending_verification'])->count() }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Temuan Risiko Tinggi:</span>
            <span class="summary-value">{{ $findings->whereIn('severity', ['critical', 'major'])->count() }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Temuan yang Ditutup:</span>
            <span class="summary-value">{{ $findings->where('status', 'closed')->count() }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Kode Temuan</th>
                <th style="width: 16%;">Klinik</th>
                <th style="width: 10%;">Tipe</th>
                <th style="width: 10%;">Risiko</th>
                <th style="width: 12%;">Status</th>
                <th style="width: 14%;">PIC</th>
                <th style="width: 12%;">Tenggat</th>
                <th style="width: 14%;">Judul Temuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($findings as $finding)
                <tr>
                    <td style="font-family: monospace; font-size: 7.5pt;">{{ $finding->finding_code }}</td>
                    <td style="font-size: 7.5pt;">{{ $finding->clinic?->code }} — {{ $finding->clinic?->name }}</td>
                    <td style="font-size: 7.5pt;">{{ ucfirst(str_replace('_', ' ', $finding->finding_type ?? '-')) }}</td>
                    <td style="font-size: 7.5pt;">
                        <span class="badge badge-{{ $finding->severity }}">
                            {{ ucfirst($finding->severity) }}
                        </span>
                    </td>
                    <td style="font-size: 7.5pt;">{{ \App\Support\UiLabel::findingStatus($finding->status) }}</td>
                    <td style="font-size: 7.5pt;">{{ $finding->owner?->name ?? $finding->assignee?->name ?? '—' }}</td>
                    <td style="font-family: monospace; font-size: 7.5pt;">{{ $finding->due_at?->format('d M Y') ?? '—' }}</td>
                    <td style="font-size: 7.5pt;">{{ \Illuminate\Support\Str::limit($finding->title, 40) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; font-size: 8pt; color: #64748b; padding: 20px;">Tidak ada temuan audit untuk periode dan filter yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Laporan ini dihasilkan dari sistem SIMMAK Audit Internal Klinik PPKP DKI Jakarta.
        Semua data dihitung dari basis data aktual pada saat laporan dibuat.
        Laporan ini bukan sertifikat atau hasil akreditasi resmi dari Kementerian Kesehatan RI.
    </div>
</body>
</html>