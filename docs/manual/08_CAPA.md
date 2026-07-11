# CAPA (Corrective & Preventive Action)

## 1. Kegunaan
Menindaklanjuti temuan dengan tindakan korektif/preventif, analisis akar masalah (RCA), dan verifikasi efektivitas.

## 2. Route
| Halaman | URL |
|---------|-----|
| Daftar CAPA | `/capa` |
| Detail CAPA | `/capa/{id}` |

Permission: `capa.view` / `capa.manage`

## 3. Siapa yang Menggunakan
| Role | Akses |
|------|-------|
| super-admin, admin-mutu, kepala-klinik, pj-mutu | View + Manage |
| tim-akreditasi, auditor, staff | View |

## 4. Cara Pengisian & Penggunaan

### 4.1 Membuka Kasus
1. Dari temuan → **Buat CAPA**, atau buka daftar `/capa`.
2. Isi deskripsi masalah, penanggung jawab, target tutup.

### 4.2 Analisis Akar Masalah (RCA)
1. Di detail CAPA, isi form **5 Why** dan/atau **Fishbone**.
2. Simpan hasil analisis.
3. Tentukan tindakan korektif & preventif.

### 4.3 Pelaksanaan Tindakan
1. Catat status aksi: belum mulai / berjalan / selesai.
2. Perbarui tanggal dan bukti pendukung bila ada.

### 4.4 Verifikasi Efektivitas
1. Setelah aksi selesai, isi form **Verifikasi**.
2. Catat hasil: efektif / tidak efektif.
3. Jika efektif, CAPA dapat ditutup; jika tidak, lanjut perbaikan.

## 5. Status Umum
Open → Analysis → Action → Verification → Closed  
Kasus melewati target tanpa selesai = **Terlambat** (tampil di dashboard).
