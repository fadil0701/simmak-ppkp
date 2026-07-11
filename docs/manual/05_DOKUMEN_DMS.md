# Dokumen (DMS)

## 1. Kegunaan
Manajemen dokumen mutu: kategori, versi, upload file, review/approval, penerbitan, QR verifikasi, dan monitoring kedaluwarsa.

## 2. Route Utama
| Halaman | URL |
|---------|-----|
| Daftar dokumen | `/dms` |
| Buat dokumen | `/dms/create` |
| Detail dokumen | `/dms/{id}` |
| Verifikasi publik (QR) | `/verify/document/{token}` |

Permission: `dms.view` (lihat), `dms.manage` (buat/ubah/publish).

## 3. Siapa yang Menggunakan
| Role | Akses |
|------|-------|
| super-admin, admin-mutu, pj-mutu | View + Manage |
| kepala-klinik, tim-akreditasi, staff | View |
| auditor | Tidak |

## 4. Cara Pengisian & Penggunaan

### 4.1 Membuat Dokumen
1. Buka **Dokumen** → **Buat**.
2. Isi judul, kategori/tipe, klinik (jika relevan), deskripsi.
3. Unggah file (PDF/dokumen pendukung).
4. Simpan sebagai draf.

### 4.2 Alur Review & Publish
1. Di detail dokumen, **Submit** untuk review.
2. Reviewer **Approve** (atau tolak sesuai workflow).
3. Setelah disetujui, **Publish** dokumen.
4. Sistem menghasilkan nomor/versi dan **QR Code**.

### 4.3 Verifikasi QR
1. Scan QR di dokumen terbit, atau buka URL `/verify/document/{token}`.
2. Halaman publik menampilkan status keaslian dokumen (tanpa login).

### 4.4 Monitoring Kedaluwarsa
1. Dashboard eksekutif menampilkan jumlah dokumen expired.
2. Job harian: `php artisan dms:check-expiry`.
3. Perbarui dokumen yang mendekati/kedaluwarsa melalui DMS.

## 5. Status Umum
Draf → Tinjauan → Disetujui → Dipublikasikan → Kedaluwarsa (jika melewati tanggal).
