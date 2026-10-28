# Keterbatasan dan Pekerjaan Belum Selesai — SIMMAK PPKP

> Pembaruan 28 Okt 2026: verifikasi environment dev Laragon (MySQL 8.0.30).
> Item prioritas tinggi (migrasi, test suite, paket PDF/Excel) telah dieksekusi dan diverifikasi.

## 1. Keterbatasan Saat Ini

### Database

- ~~MySQL tidak tersedia di lingkungan pengembangan~~ **TERATASI (28 Okt 2026)**: MySQL 8.0.30 Laragon
  berjalan di `127.0.0.1:3306`. Database `simmak_ppkp` + `simmak_ppkp_test` dibuat via
  `php scripts/create_mysql_db.php`. `.env` memakai `DB_CONNECTION=mysql`.
- PostgreSQL 17 (produksi) belum diuji secara langsung — kompatibilitas tetap wajib dijaga
  (lihat `docs/DEPLOY_DOCKER.md` "Perbaikan PostgreSQL").

### PDF Export

- `barryvdh/laravel-dompdf` ^3.1 **terpasang** (v3.1.2 di composer.lock).
  `AuditReportService::generateReport()` memakai `Pdf::loadView()` untuk PDF stored report.
- `ReportsController::exportPdf()` memakai `Pdf::loadHtml()`; fallback HTML dicetak-to-PDF
  hanya aktif jika paket tidak terdeteksi (`class_exists` check).

### Excel Export

- `maatwebsite/excel` ^4.0 **terpasang** (v4.0.4 di composer.lock).
- `ReportsController::exportExcel()` kini menghasilkan **.xlsx asli** via `ReportExport`
  (`app/Exports/ReportExport.php`); CSV dengan BOM UTF-8 hanya fallback.
- Ekspor CSV lain (temuan, CAPA, ranking klinik) masih CSV — sesuai kebutuhan (stream ringan).

### UI/UX

- Halaman **Standar & Instrumen** sudah diperbarui (28 Okt 2026): form tambah EP berlabel lengkap
  (kode, judul, persyaratan, kelengkapan bukti, panduan penilaian, skor), empty-state yang
  memandu pembuatan versi pertama, dan tombol **Template Impor** di kepala halaman.
  **Perbaikan isi tanpa versi baru (28 Okt 2026):** versi yang sudah dipakai eksekusi tidak lagi
  ditandai **Dikunci**. Pengelola dapat menambah/menyunting seksi dan EP pada versi yang sama.
- Halaman **Impor EP** didesain ulang menjadi 3 langkah (unduh template → unggah → pratinjau & simpan)
  dengan tabel penjelasan kolom, statistik pratinjau, serta tabel baris bermasalah/duplikat.
- **Daur hidup status standar** diperbaiki: sebelumnya `audit_standards.status` selalu `draft`
  karena tidak ada kode yang pernah mengubahnya. Sekarang status dapat diubah dari form
  Tambah/Sunting, tombol cepat **Aktifkan/Nonaktifkan** di daftar & detail, otomatis menjadi
  `active` saat versi instrumen diaktifkan, dan ditampilkan dengan label Indonesia
  (Draf/Aktif/Nonaktif/Diarsipkan) beserta warna badge.
- **Form Tambah & Edit standar** didesain ulang: dua bagian bernomor (Identitas Standar,
  Status), grid kode+judul, hint per kolom, dan bilah simpan lengket (sticky save bar).
- **Istilah antarmuka diseragamkan** (28 Okt 2026): tombol memakai istilah singkat
  `Unggah → Upload`, `Sunting/Ubah → Edit`, `Lihat → Detail`. Istilah **CAPA** diganti
  **Tindak Lanjut** di menu, judul, tombol, pesan, ekspor, dan dashboard; nomor kasus
  berubah dari `CAPA-2026-0001` menjadi `TL-2026-0001`
  (`AuditCapaService::generateCapaNumber()`). Nama izin (`capa.view`) dan rute (`/capa`)
  tetap dipertahankan agar kompatibel.
- **Palet warna tombol baku per aksi** (28 Okt 2026). Warna tidak lagi dipilih bebas per
  view; satu jenis aksi selalu memakai warna yang sama. Sumber kebenaran:
  `app/Support/UiAction.php` + kelas `.sim-btn-*` di `resources/css/app.css`.

  | Aksi | Kelas | Warna | Ikon |
  |---|---|---|---|
  | Kembali / navigasi | `sim-btn-back` | netral (abu outline) | `chevron-left` |
  | Tambah / buat / mulai | `sim-btn-create` | teal solid | `plus` / `play-circle` |
  | Simpan | `sim-btn-save` | navy solid | `check-circle` |
  | Edit / ubah / isi tabel | `sim-btn-edit` | amber solid | `pencil` |
  | Hapus / eskalasi | `sim-btn-delete` | merah solid | `trash` / `alert-triangle` |
  | Detail / lihat | `sim-btn-detail` | biru outline | `file-text` / `eye` |
  | Setujui / terbitkan / ajukan / verifikasi | `sim-btn-approve` | hijau solid | `check-circle` |
  | Tolak / kembalikan / nonaktifkan | `sim-btn-reject` | oranye outline | `x-mark` |
  | Batal / nonaktif / filter-reset | `sim-btn-neutral` | abu solid | `x-mark` |
  | Upload / unggah / impor | `sim-btn-upload` | teal outline | `upload` |
  | Unduh / ekspor / template | `sim-btn-download` | slate outline | `download` |
  | Cetak | `sim-btn-print` | navy outline | `file-text` |

  Pemakaian: `<x-action action="save" :href="…" />` — ikon, label, dan warna otomatis
  mengikuti aksi. Prop `variant` lama masih diterima tetapi diabaikan bila `action` diisi.
  Penyelarasan massal (idempoten): `php scripts/apply_button_palette.php`.
  Dikunci oleh test `tests/Unit/Ui/ActionPaletteTest.php` (6 test).
- Beberapa view lain (assignments, executions, evidence, capabilities) masih menggunakan layout
  yang perlu ditinjau ulang konsistensinya dengan desain institusional baru.
- Responsivitas mobile perlu diuji pada perangkat aktual.

### Integrasi

- Modul Manajemen Mutu dan Manajemen Akreditasi belum dibangun (fase 2 dan 3).
- Integrasi LDAP Active Directory Pemprov DKI Jakarta belum diaktifkan (`LDAP_ENABLED=false`).
- Integrasi object storage (S3/Supabase) belum dikonfigurasi untuk produksi.

## 2. Pekerjaan Belum Selesai

### Prioritas Tinggi

- [x] ~~Menjalankan `php artisan migrate:fresh --seed`~~ **SELESAI (28 Okt 2026)** — 42/42 migration
      + 11 seeder sukses. Perbaikan yang menyertai: nama unique index
      `checklist_responses_audit_execution_id_standard_criterion_id_unique` >64 karakter menyebabkan
      error 1059 di MySQL — diganti `checklist_responses_execution_criterion_unique`
      (file `database/migrations/2026_10_19_110004_create_checklist_responses_table.php`).
- [x] ~~Menjalankan seluruh test suite~~ **SELESAI (28 Okt 2026)** — `Tests: 145, Assertions: 487`,
      0 error/0 failure (deprecation PHPUnit 12 doc-comment metadata non-blocking).
      Perbaikan yang menyertai: `AuditTrailTest` dan `EvidenceSecurityTest` kini memakai trait
      `RefreshDatabase` (sebelumnya menulis langsung tanpa migrasi → race "table doesn't exist").
      Catatan operasional: setelah run yang crash, reset `simmak_ppkp_test` sebelum re-run
      (`DROP DATABASE` + `CREATE`) agar tidak kena duplicate entry.
- [x] ~~Menginstal paket PDF dan Excel untuk ekspor produksi~~ **TERATASI** — kedua paket sudah
      ada di composer.json sejak sebelumnya; `ReportsController::exportExcel()` di-upgrade ke .xlsx.
- [ ] Memverifikasi referensi regulasi ke JDIH Kemenkes (lihat docs/REGULATION_VERIFICATION.md)
- [ ] Menguji responsivitas tampilan pada mobile

### Prioritas Menengah

- [ ] Menambahkan Form Request untuk validasi terpusat pada semua controller
- [ ] Menambahkan policy class untuk otorisasi berbasis model (mis. AuditFindingPolicy)
- [ ] Menambahkan notifikasi email untuk keterlambatan CAPA dan eskalasi
- [x] ~~Menambahkan fitur kalender audit (tampilan kalender)~~ **SELESAI (28 Okt 2026)** —
      halaman `/audit/calendar` menampilkan grid bulan dari jadwal `audit_assignments`
      (ruang lingkup klinik pengguna). Pintasan dari daftar penugasan dan menu sidebar.
- [x] ~~Menambahkan grafis visualisasi pada dashboard (chart library)~~ **SELESAI (28 Okt 2026)** —
      Chart.js (`chart.js` via Vite) pada dashboard audit: donat status eksekusi dan
      batang temuan terbuka menurut tingkat risiko, dari `AuditDashboardService::chartSeries()`.

### Prioritas Rendah

- [x] ~~Menambahkan fitur autosave pada checklist~~ **SELESAI (28 Okt 2026)** —
      tabel pemeriksaan mengirim `POST …/table/autosave` (debounce ~1,4 dtk).
      Autosave hanya menyimpan baris; tombol **Simpan Tabel** tetap membuat temuan.
- [x] ~~Menambahkan fitur import/export standar dan instrumen~~ **SEBAGIAN SELESAI (28 Okt 2026)** —
      impor EP dari Excel/CSV kini punya template resmi yang bisa diunduh, panduan kolom
      di dalam halaman, pratinjau bertahap (3 langkah), deteksi baris bermasalah, serta
      penerimaan alias judul kolom (`Standar`, `EP`, `Bukti`, `Skor`, dst.).
      Ekspor instrumen (standar → Excel) belum dibuat.
- [ ] Menambahkan API publik untuk integrasi sistem lain
- [x] ~~Menambahkan fitur pencarian full-text pada bukti dan temuan~~ **SELESAI (28 Okt 2026)** —
      kolom `q` pada daftar temuan dan arsip bukti. MySQL: `MATCH … AGAINST` + LIKE;
      PostgreSQL: `to_tsvector`. Indeks: migrasi `2026_10_28_150000_add_fulltext_indexes_to_evidence_and_findings`.

## 3. Asumsi Desain

- Data klinik (16 klinik) adalah data demonstrasi, bukan data resmi PPKP DKI Jakarta.
- Nomor regulasi yang disebutkan dalam sistem belum diverifikasi dan ditandai sebagai `UNVERIFIED`.
- Akun demonstrasi memakai password dari environment `DEFAULT_USER_PASSWORD` (fallback `DEMO_ACCOUNT_PASSWORD`, tidak ada di codebase); di produksi akun demo dilewati kecuali password di-set eksplisit. Akun super admin dibuat terpisah lewat `php artisan simmak:create-superadmin`.
- File bukti disimpan pada storage lokal (private), bukan object storage.


