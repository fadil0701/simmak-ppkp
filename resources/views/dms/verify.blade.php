<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Dokumen — SIMMAK PPKP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100">
    <div class="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-10">
        <div class="mb-6 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-teal-600 text-xl font-bold text-white">+</div>
            <h1 class="text-xl font-bold text-slate-900">Verifikasi Dokumen</h1>
            <p class="mt-1 text-sm text-slate-500">SIMMAK PPKP — PPKP DKI Jakarta</p>
        </div>

        <div class="simmak-card overflow-hidden">
            <div class="border-b border-emerald-100 bg-emerald-50 px-5 py-4 text-center">
                <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <p class="font-semibold text-emerald-800">Dokumen Terverifikasi</p>
                <p class="text-xs text-emerald-600">QR code valid · dokumen resmi PPKP</p>
            </div>

            <div class="simmak-card-body space-y-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">{{ $document->title }}</h2>
                    <p class="font-mono text-sm text-slate-500">{{ $document->document_number }}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <span class="text-xs text-slate-500">Status</span>
                        <p class="font-medium capitalize text-emerald-700">{{ $document->status }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Versi</span>
                        <p class="font-medium">v{{ $document->currentVersion?->version_number ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Kategori</span>
                        <p class="font-medium">{{ $document->documentType?->category?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Klinik</span>
                        <p class="font-medium">{{ $document->clinic?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Berlaku Sejak</span>
                        <p class="font-medium">{{ $document->effective_date?->format('d M Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Kedaluwarsa</span>
                        <p class="font-medium">{{ $document->expiry_date?->format('d M Y') ?? '—' }}</p>
                    </div>
                </div>

                <p class="text-xs text-slate-400">
                    Diverifikasi pada {{ now()->format('d M Y H:i') }} WIB
                    · QR dibuat {{ $qrCode->generated_at?->format('d M Y') ?? '—' }}
                </p>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">© {{ date('Y') }} PPKP Provinsi DKI Jakarta</p>
    </div>
</body>
</html>
