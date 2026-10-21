# Dokumentasi Perubahan Layout dan Laporan Audit

Tanggal: 19 Oktober 2026

Dokumen ini mencatat perbaikan antarmuka responsif dan penyesuaian skema basis data laporan audit yang dilakukan untuk menjaga aplikasi SIMMAK tetap konsisten pada desktop, tablet, dan perangkat seluler, serta kompatibel dengan basis data PostgreSQL yang sudah memiliki data.

## 1. Perbaikan layout

### Permasalahan

- Sidebar, header, dan area konten tidak selalu tersusun sejajar pada berbagai lebar layar.
- Sidebar perlu tetap terlihat saat halaman digulir di desktop, tetapi tidak boleh mengambil ruang layar pada perangkat kecil saat tertutup.
- Elemen header seperti pemilih klinik, tanggal, judul halaman, notifikasi, bantuan, dan profil dapat saling mendesak. Ikon pemilih klinik yang tidak dibatasi ukurannya juga membuat header terlalu tinggi.
- Dashboard Klinik menggunakan kelas `simmak-card`, `simmak-card-header`, `simmak-card-body`, dan `simmak-badge` yang perlu memiliki aturan styling.

### Perubahan

- Shell utama menggunakan susunan vertikal pada layar kecil dan susunan sidebar-konten pada desktop.
- Sidebar menjadi drawer pada layar kecil, dilengkapi overlay; pada desktop sidebar tetap berada di sisi kiri dan mengikuti scroll container.
- Header diberi aturan ukuran dan pembungkusan konten pada beberapa breakpoint. Ukuran ikon dan pemilih klinik dibatasi, label klinik dipotong dengan ellipsis, dan elemen header pengguna tidak menyusut.
- Kelas kartu dan badge lama disediakan kembali agar halaman dashboard klinik dan halaman lain yang menggunakannya tetap tertata.

### Berkas terkait

- `resources/css/app.css` — aturan shell, sidebar, kartu, badge, dan breakpoint header.
- `resources/views/layouts/simmak.blade.php` — class serta state drawer, overlay, tombol menu, dan atribut aksesibilitas header/sidebar.

## 2. Kompatibilitas skema laporan audit

### Permasalahan

Halaman daftar laporan mengurutkan record berdasarkan `audit_reports.generated_at`. Pada basis data PostgreSQL yang aktif saat ditemukan masalah, tabel `audit_reports` berasal dari migrasi lama dan belum memiliki kolom tersebut. Skema lama juga mempunyai kolom wajib `audit_schedule_id` dan `report_date`, sedangkan proses pembuatan laporan aplikasi yang lebih baru tidak mengisi keduanya.

### Perubahan

Migrasi aditif `2026_10_19_160001_extend_audit_reports_for_reporting` memperluas tabel lama tanpa menghapus tabel, kolom, atau record. Migrasi menambahkan kolom yang digunakan alur laporan saat ini bila belum ada:

- Relasi dan cakupan: `audit_execution_id`, `clinic_id`, `generated_by`.
- Informasi laporan: `title`, `report_type`, `period_start`, `period_end`.
- Ringkasan: `findings_summary`, `capa_summary`.
- Berkas: `file_path`, `file_type`, `file_size`, `checksum`, `metadata`.
- Waktu pembuatan: `generated_at` dan indeksnya.

Untuk data laporan yang sudah ada, migrasi mengisi nilai berikut:

- `title` dari `report_number`.
- `report_type` dengan nilai `summary`.
- `generated_at` dari `created_at`.
- `generated_by` dari `prepared_by`, jika kolom `prepared_by` ada dan berisi nilai.

Kolom lama `audit_schedule_id` dan `report_date` tetap dipertahankan, tetapi dibuat nullable agar pembuatan laporan baru tidak diwajibkan mengisi atribut skema lama tersebut. Kolom tambahan yang tidak selalu tersedia untuk data lama dibuat nullable untuk menjaga kompatibilitas.

Migrasi tidak menghapus kolom saat `down()`. Ini disengaja agar rollback migrasi tidak menghapus metadata laporan atau merusak data yang mungkin sudah dibuat setelah migrasi dijalankan. Jika perlu membatalkan perubahan, buat migrasi korektif tersendiri setelah meninjau data dan dependensi.

### Berkas terkait

- `database/migrations/2026_10_19_160001_extend_audit_reports_for_reporting.php` — perluasan skema dan pengisian nilai laporan lama.
- `app/Models/AuditReport.php` — model menggunakan kolom laporan seperti `generated_at`, `generated_by`, `metadata`, ringkasan, dan relasi terkait.
- `app/Services/AuditReportService.php` — daftar laporan diurutkan menurut `generated_at`; pembuatan laporan mengisi metadata laporan.
- `docs/DDD.md` — catatan kompatibilitas skema pada entitas `audit_reports`.

## 3. Penerapan migrasi

Migrasi kompatibilitas telah dijalankan pada container aplikasi/PostgreSQL pengembangan yang aktif. Pada lingkungan lain, setelah membuat backup dan memeriksa target koneksi, jalankan migrasi normal:

```bash
php artisan migrate --force
```

Untuk deployment Docker sesuai konfigurasi proyek:

```bash
docker compose exec app gosu www-data php artisan migrate --force
```

Pastikan aplikasi terhubung ke basis data yang benar sebelum menjalankan perintah. Jangan menjalankan `migrate:fresh` pada basis data yang berisi data yang ingin dipertahankan.

## 4. Validasi yang dilakukan

- `npm run build` berhasil setelah perubahan CSS.
- `git diff --check` dan `php -l` pada migrasi berhasil.
- Layout diperiksa melalui browser pada lebar viewport 390, 800, 1024, 1200, dan 1280 piksel. Tidak ditemukan horizontal overflow; sidebar dan picker klinik juga diperiksa.
- Migrasi kompatibilitas berhasil dijalankan dan tercatat pada tabel migrasi PostgreSQL.
- Skema aktif diperiksa: kolom baru tersedia, kolom lama tetap ada, dan tidak ditemukan laporan yang `generated_at`-nya kosong.
- Query layanan daftar laporan yang mengurutkan berdasarkan `generated_at` berhasil dijalankan.

Suite test Laravel penuh tidak dijalankan sebagai bagian dari perubahan ini.

## 5. Batas cakupan

Perubahan layout ini memperbaiki shell navigasi dan komposisi header lintas ukuran layar; perubahan ini bukan redesign menyeluruh untuk setiap halaman. Migrasi laporan ini menangani perbedaan skema lama dan alur laporan yang berjalan saat ini; migrasi ini tidak mengubah kebijakan bisnis atau menghapus struktur lama.
