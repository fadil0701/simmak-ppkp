# SIMMAK PPKP — UAT Checklist (Fase 1–6)

| Versi | 1.0.0 |
| Tanggal | 9 Juli 2026 |
| Environment | MySQL lokal + `php artisan serve` |

## Kriteria Lulus
Minimal **80%** skenario harus **Lulus** sebelum fase dianggap selesai.

---

## Fase 1 — Core Platform

| # | Skenario | Langkah | Ekspektasi | Status |
|---|----------|---------|------------|--------|
| 1.1 | Login lokal | Buka `/login`, masuk admin | Redirect ke dashboard | ☐ |
| 1.2 | Logout | Klik logout | Kembali ke login | ☐ |
| 1.3 | RBAC | Login sebagai admin | Akses semua menu | ☐ |
| 1.4 | Daftar pengguna | Buka `/users` | Tabel user tampil | ☐ |
| 1.5 | Tambah pengguna | `/users/create` → simpan | User baru tercatat | ☐ |
| 1.6 | Pengaturan org | `/settings` → simpan | Setting tersimpan | ☐ |
| 1.7 | API auth | POST `/api/v1/auth/login` | Token Sanctum dikembalikan | ☐ |
| 1.8 | Konteks klinik | Session klinik aktif | Dashboard klinik sesuai konteks | ☐ |

## Fase 2 — DMS

| # | Skenario | Langkah | Ekspektasi | Status |
|---|----------|---------|------------|--------|
| 2.1 | Daftar dokumen | `/dms` | List + statistik | ☐ |
| 2.2 | Buat dokumen | Upload file PDF | Dokumen draft tercatat | ☐ |
| 2.3 | Workflow review | Submit review → approve | Status review → approved | ☐ |
| 2.4 | Publish | Terbitkan dokumen | Status published + QR | ☐ |
| 2.5 | Download | Klik download attachment | File terunduh | ☐ |
| 2.6 | Expiry job | `php artisan dms:check-expiry` | Alert kedaluwarsa terbuat | ☐ |

## Fase 3 — Audit & CAPA

| # | Skenario | Langkah | Ekspektasi | Status |
|---|----------|---------|------------|--------|
| 3.1 | Program audit | `/audit` | Program & jadwal tampil | ☐ |
| 3.2 | Checklist | Klik checklist jadwal | Item checklist tampil | ☐ |
| 3.3 | Laporan audit | `/audit/reports` | Laporan tercatat | ☐ |
| 3.4 | Temuan | `/findings` | Filter & detail berfungsi | ☐ |
| 3.5 | Buat CAPA | Dari temuan → buat CAPA | CAPA terhubung temuan | ☐ |
| 3.6 | RCA 5 Why | Form di detail CAPA | RCA tersimpan | ☐ |
| 3.7 | Verifikasi CAPA | Form verifikasi | Hasil efektivitas tercatat | ☐ |

## Fase 4 — Risk & Quality

| # | Skenario | Langkah | Ekspektasi | Status |
|---|----------|---------|------------|--------|
| 4.1 | Register risiko | `/risk` | Heatmap + daftar item | ☐ |
| 4.2 | Catat risiko | `/risk/create` | Item baru tercatat | ☐ |
| 4.3 | Mitigation plan | Detail risiko → tambah rencana | Plan tersimpan | ☐ |
| 4.4 | Risk matrix | Matrix default di seeder | Konfigurasi 5×5 ada | ☐ |
| 4.5 | Program mutu | `/quality` | Program + indikator tampil | ☐ |
| 4.6 | Pengukuran mutu | Data tren di dashboard klinik | Grafik dari measurements | ☐ |

## Fase 5 — Akreditasi

| # | Skenario | Langkah | Ekspektasi | Status |
|---|----------|---------|------------|--------|
| 5.1 | Dashboard akreditasi | `/accreditation` | Skor kesiapan + bab | ☐ |
| 5.2 | Self assessment | `/accreditation/self-assessment` | Skor per standar | ☐ |
| 5.3 | Gap analysis | `/accreditation/gaps` | Filter severity | ☐ |
| 5.4 | Mock survey | `/accreditation/mock-survey` | Detail survei tampil | ☐ |
| 5.5 | Evidence | `/accreditation/evidence` | Link ke dokumen DMS | ☐ |
| 5.6 | Standar FKTP/FKTL | `/accreditation/standards` | Hierarki bab/standar | ☐ |

## Fase 6 — Dashboard Enterprise

| # | Skenario | Langkah | Ekspektasi | Status |
|---|----------|---------|------------|--------|
| 6.1 | Dashboard eksekutif | `/dashboard` | KPI data real | ☐ |
| 6.2 | Ranking klinik | Tabel ranking | Data per klinik | ☐ |
| 6.3 | Export CSV | Tombol Export | File CSV terunduh | ☐ |
| 6.4 | Dashboard klinik | `/clinic/dashboard` | KPI per klinik | ☐ |
| 6.5 | Pelaporan | `/reports` | Ringkasan + link export | ☐ |
| 6.6 | Cache dashboard | Refresh 2x dalam 5 menit | Response konsisten (cache 300s) | ☐ |
| 6.7 | API executive | GET `/api/v1/dashboard/executive` | JSON stats valid | ☐ |

---

## Catatan
- LDAP AD **tidak termasuk** UAT (login tunggal lokal).
- Deploy PostgreSQL production diuji terpisah via `docker-compose.dev.yml`.
