# Dashboard Eksekutif

## 1. Kegunaan
Ringkasan mutu lintas klinik untuk pimpinan: kesiapan akreditasi, temuan terbuka, progres CAPA, peta panas risiko, dokumen kedaluwarsa, aktivitas, dan peringkat klinik.

## 2. Route
`/dashboard` — permission: `dashboard.executive`

## 3. Siapa yang Menggunakan
| Role | Akses |
|------|-------|
| super-admin | Ya |
| admin-mutu | Ya |
| Lainnya | Tidak (menu disembunyikan) |

## 4. Cara Membaca & Menggunakan

### 4.1 Kartu KPI Atas
1. **Kesiapan Akreditasi** — skor rata-rata organisasi + target survei.
2. **Temuan Terbuka** — jumlah temuan status terbuka/in CAPA + prioritas tinggi.
3. **Progres CAPA** — batang Selesai / Dalam Proses / Belum Dimulai / Terlambat.
4. **Peta Panas Risiko** — matriks dampak × kemungkinan.

### 4.2 Bagian Bawah
1. **Dokumen Kedaluwarsa** — klik *Lihat Semua* ke DMS.
2. **Aktivitas Terbaru** — jejak aktivitas sistem.
3. **Peringkat Klinik** — filter wilayah, lihat skor, klik **Ekspor** untuk unduh CSV.

### 4.3 Filter & Ekspor
1. Pilih wilayah di dropdown → halaman memuat ulang ranking.
2. Tombol **Ekspor** mengunduh `ranking-klinik-YYYY-MM-DD.csv`.

## 5. Catatan Data
- Data di-cache ±5 menit (`Cache::remember`).
- Untuk data paling baru: `php artisan cache:clear`.
