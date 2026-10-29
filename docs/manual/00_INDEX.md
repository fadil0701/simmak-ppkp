# SIMMAK PPKP — Indeks Panduan Pengguna

| Atribut | Nilai |
|---------|-------|
| Sistem | SIMMAK PPKP |
| Organisasi | PPKP Provinsi DKI Jakarta |
| Versi | 2.1.0 |
| Tanggal | 29 Oktober 2026 |
| Format | Markdown + PDF (`pdf/manual/`) |

**Dokumen utama operasional:** `docs/manual/JUKNIS_PENGGUNAAN_SIMMAK.md` (PDF: `00_Juknis_Penggunaan_SIMMAK.pdf`) — petunjuk teknis lengkap dari masuk sistem sampai laporan.

Bab 01–13 di bawah ini adalah rincian per modul (sebagian masih memakai istilah rilis lama). Jika berbeda dengan juknis, **ikuti juknis**.

Dokumen internal tim (bukan PDF panduan pengguna): `docs/UI_PELAKSANAAN_AUDIT_TEMUAN_CAPA.md` — perubahan layar pelaksanaan, temuan, dan tindak lanjut.

## Daftar Dokumen

| No | Dokumen | PDF |
|----|---------|-----|
| 00 | Indeks (dokumen ini) | — |
| Juknis | Petunjuk Teknis Penggunaan SIMMAK | `00_Juknis_Penggunaan_SIMMAK.pdf` |
| 01 | Role, Permission & Matriks Akses | `01_Role_dan_Akses.pdf` |
| 02 | Login & Autentikasi | `02_Login_dan_Autentikasi.pdf` |
| 03 | Dashboard Eksekutif | `03_Dashboard_Eksekutif.pdf` |
| 04 | Dashboard Klinik | `04_Dashboard_Klinik.pdf` |
| 05 | Manajemen (DMS) | `05_Dokumen_DMS.pdf` |
| 06 | Audit Internal | `06_Audit_Internal.pdf` |
| 07 | Temuan Audit | `07_Temuan_Audit.pdf` |
| 08 | CAPA | `08_CAPA.pdf` |
| 09 | Manajemen Risiko | `09_Manajemen_Risiko.pdf` |
| 10 | Program Mutu | `10_Program_Mutu.pdf` |
| 11 | Akreditasi FKTP/FKTL | `11_Akreditasi.pdf` |
| 12 | Pelaporan | `12_Pelaporan.pdf` |
| 13 | Pengguna & Pengaturan | `13_Pengguna_dan_Pengaturan.pdf` |

## Cara Generate Ulang PDF

```bash
python scripts/generate_manual_pdfs.py
```

Output tersimpan di folder `pdf/manual/`.

## Akun Demo

Password akun demo tidak ada di codebase — diambil dari environment
`DEFAULT_USER_PASSWORD` (fallback lama `DEMO_ACCOUNT_PASSWORD`), atau dibuat acak
oleh seeder dan dicetak sekali ke console saat seeding. Akun super admin dibuat
terpisah lewat `php artisan simmak:create-superadmin`.

| Email | Username | Role |
|-------|----------|------|
| `$SUPERADMIN_EMAIL` (default `admin@simmak.ppkp.local`) | `admin` | super-admin |
| sistem@simmak.ppkp.local | adminsistem | admin-sistem |
| kepalapusat@simmak.ppkp.local | kepalapusat | kepala-pusat |
| tu@simmak.ppkp.local | kasubbagtu | kasubbag-tu |
| mutu@simmak.ppkp.local | ketuamutu | ketua-tim-mutu (legacy) |
| ketuaaudit@simmak.ppkp.local | ketuaaudit | ketua-tim-audit (legacy) |
| akreditasi@simmak.ppkp.local | akreditasi | ketua-tim-akreditasi (legacy) |
| auditor@simmak.ppkp.local | auditor | auditor (legacy) |
| kepala@simmak.ppkp.local | kepala | kepala-klinik |
| staff@simmak.ppkp.local | auditi | auditi (legacy) |
