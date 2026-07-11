# Temuan Audit

## 1. Kegunaan
Mencatat, memfilter, dan menindaklanjuti temuan hasil audit internal (minor/mayor/kritis).

## 2. Route
| Halaman | URL |
|---------|-----|
| Daftar temuan | `/findings` |
| Detail temuan | `/findings/{id}` |

Permission: `audit.view` / `audit.manage`

## 3. Siapa yang Menggunakan
| Role | Akses |
|------|-------|
| super-admin, admin-mutu, pj-mutu, auditor | Lihat & kelola |
| kepala-klinik | Lihat |
| Lainnya | Tidak |

## 4. Cara Pengisian & Penggunaan

### 4.1 Melihat Temuan
1. Buka menu **Temuan**.
2. Filter status/severity bila tersedia.
3. Klik baris untuk melihat detail.

### 4.2 Membuat CAPA dari Temuan
1. Di detail temuan, pilih aksi **Buat CAPA**.
2. Sistem membuat kasus CAPA yang terhubung ke temuan.
3. Lanjutkan pengisian di modul **CAPA**.

### 4.3 Status Temuan
Umumnya: Terbuka → Dalam CAPA → Ditutup (setelah CAPA efektif).

## 5. Prioritas
Temuan `major` / `critical` muncul sebagai **Prioritas Tinggi** di dashboard eksekutif.
