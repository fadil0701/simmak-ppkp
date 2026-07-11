# Dashboard Klinik

## 1. Kegunaan
Pantau kinerja mutu **satu klinik aktif**: audit, CAPA, risiko, kesiapan akreditasi, tren indikator, dokumen, dan tugas tim.

## 2. Route
`/clinic/dashboard` — permission: `dashboard.clinic`

## 3. Siapa yang Menggunakan
| Role | Akses |
|------|-------|
| super-admin, admin-mutu | Ya |
| kepala-klinik, pj-mutu | Ya (klinik masing-masing) |
| tim-akreditasi | Ya |
| auditor, staff | Tidak |

## 4. Cara Menggunakan
1. Login dengan role yang memiliki `dashboard.clinic`.
2. Pastikan konteks **Klinik Aktif** di sidebar sesuai.
3. Baca kartu KPI: Audit terjadwal/selesai, CAPA terbuka/proses, ringkasan risiko, kesiapan akreditasi.
4. Gunakan tautan *Lihat Semua* untuk masuk ke modul terkait.
5. Pantau tren mutu (line chart) dan tugas/dokumen terkait klinik.

## 5. Pengisian Data
Dashboard bersifat **read-only**. Data diisi melalui modul sumber (Audit, CAPA, Risiko, Akreditasi, Mutu, DMS).
