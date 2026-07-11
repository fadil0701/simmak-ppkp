# Audit Internal

## 1. Kegunaan
Merencanakan dan melaksanakan audit internal: program, jadwal, checklist fieldwork, dan laporan audit.

## 2. Route Utama
| Halaman | URL |
|---------|-----|
| Program & jadwal | `/audit` |
| Checklist | `/audit/{schedule}/checklist` |
| Laporan | `/audit/reports` |

Permission: `audit.view` / `audit.manage`

## 3. Siapa yang Menggunakan
| Role | Akses |
|------|-------|
| super-admin, admin-mutu, pj-mutu, auditor | View + Manage |
| kepala-klinik | View |
| Lainnya | Tidak |

## 4. Cara Pengisian & Penggunaan

### 4.1 Program & Jadwal
1. Buka **Audit Internal**.
2. Lihat program aktif dan daftar jadwal per klinik/unit.
3. Pilih jadwal untuk membuka checklist.

### 4.2 Checklist Fieldwork
1. Buka checklist jadwal.
2. Isi hasil per item (sesuai/tidak sesuai/observasi).
3. Catat catatan lapangan bila perlu.
4. Simpan progres checklist.

### 4.3 Laporan Audit
1. Buka `/audit/reports`.
2. Tinjau laporan yang dihasilkan dari pelaksanaan audit.
3. Temuan yang muncul dilanjutkan di modul **Temuan**.

## 5. Hubungan Modul Lain
Audit → Temuan → CAPA → Dashboard.
