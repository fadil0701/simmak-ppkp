# Manajemen Risiko

## 1. Kegunaan
Mendaftarkan risiko, menilai skor inherent/residual, menampilkan heatmap, dan merencanakan mitigasi.

## 2. Route
| Halaman | URL |
|---------|-----|
| Register & heatmap | `/risk` |
| Catat risiko | `/risk/create` |
| Detail risiko | `/risk/{id}` |

Permission: `risk.view` / `risk.manage`

## 3. Siapa yang Menggunakan
| Role | Akses |
|------|-------|
| super-admin, admin-mutu, pj-mutu | View + Manage |
| kepala-klinik | View |
| Lainnya | Tidak |

## 4. Cara Pengisian & Penggunaan

### 4.1 Melihat Heatmap
1. Buka **Risiko**.
2. Baca matriks 5×5 (Dampak × Kemungkinan).
3. Angka di sel = jumlah risiko pada level tersebut.

### 4.2 Mencatat Risiko Baru
1. Klik **Catat Risiko** / buka `/risk/create`.
2. Isi: kategori, deskripsi, likelihood, impact (inherent).
3. Opsional isi residual setelah kontrol.
4. Simpan. Skor dihitung otomatis.

### 4.3 Rencana Mitigasi
1. Buka detail risiko.
2. Tambah **rencana mitigasi**: tindakan, PIC, target tanggal.
3. Pantau status hingga risiko turun/ditutup.

## 5. Tip Penilaian
- Skor tinggi (merah) = prioritas mitigasi.
- Tutup risiko hanya jika residual sudah diterima manajemen.
