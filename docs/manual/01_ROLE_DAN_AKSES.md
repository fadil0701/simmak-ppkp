# Role, Permission & Matriks Akses

## 1. Tujuan
Dokumen ini menjelaskan peran (role) pengguna SIMMAK PPKP, permission yang dimiliki, menu yang dapat diakses, serta halaman landing setelah login.

## 2. Daftar Role

| Kode Role | Label UI | Fungsi utama |
|-----------|----------|--------------|
| `super-admin` | Direktur Utama | Akses penuh seluruh modul termasuk pengguna |
| `admin-mutu` | Admin Mutu PPKP | Kelola mutu lintas klinik (tanpa users.manage) |
| `kepala-klinik` | Kepala Klinik | Pantau mutu & CAPA di klinik masing-masing |
| `pj-mutu` | Penanggung Jawab Mutu | Operasional mutu, audit, CAPA, risiko, akreditasi klinik |
| `tim-akreditasi` | Tim Akreditasi | Fokus self assessment, gap, bukti, mock survey |
| `auditor` | Auditor Internal | Program audit, checklist, temuan |
| `staff` | Staff Klinik | Lihat dokumen, CAPA, akreditasi (terbatas) |

## 3. Permission Sistem

| Permission | Arti |
|------------|------|
| `dashboard.view` | Akses dasar setelah login |
| `dashboard.executive` | Dashboard eksekutif + pelaporan |
| `dashboard.clinic` | Dashboard klinik |
| `dms.view` / `dms.manage` | Lihat / kelola dokumen |
| `audit.view` / `audit.manage` | Lihat / kelola audit & temuan |
| `capa.view` / `capa.manage` | Lihat / kelola CAPA |
| `risk.view` / `risk.manage` | Lihat / kelola risiko |
| `accreditation.view` / `accreditation.manage` | Lihat / kelola akreditasi |
| `quality.view` / `quality.manage` | Lihat / kelola program mutu |
| `users.view` / `users.manage` | Lihat / kelola pengguna |
| `settings.view` / `settings.manage` | Lihat / kelola pengaturan |
| `workflow.manage` | Kelola workflow (admin) |

## 4. Matriks Akses Menu

Keterangan: **V** = view (lihat), **M** = manage (ubah/buat), **—** = tidak ada akses.

| Modul / Menu | super-admin | admin-mutu | kepala-klinik | pj-mutu | tim-akreditasi | auditor | staff |
|--------------|:-----------:|:----------:|:-------------:|:-------:|:--------------:|:-------:|:-----:|
| Dashboard Eksekutif | V | V | — | — | — | — | — |
| Dashboard Klinik | V | V | V | V | V | — | — |
| Audit Internal | M | M | V | M | — | M | — |
| Temuan | M | M | V | M | — | M | — |
| CAPA | M | M | M | M | V | V | V |
| Risiko | M | M | V | M | — | — | — |
| Program Mutu | M | M | V | M | V | — | — |
| Akreditasi | M | M | V | M | M | — | V |
| Dokumen (DMS) | M | M | V | M | V | — | V |
| Pelaporan | V | V | — | — | — | — | — |
| Pengguna | M | — | — | — | — | — | — |
| Pengaturan | M | M | — | — | — | — | — |

## 5. Halaman Utama Setelah Login

| Role | Redirect |
|------|----------|
| super-admin, admin-mutu | `/dashboard` |
| tim-akreditasi | `/accreditation` |
| kepala-klinik, pj-mutu | `/clinic/dashboard` |
| auditor | `/audit` |
| staff | `/dms` |

## 6. Akun Demo

Password semua akun: **`password`**

| Email | Username | Role | Klinik default |
|-------|----------|------|----------------|
| admin@simmak.ppkp.local | admin | super-admin | KU |
| mutu@simmak.ppkp.local | adminmutu | admin-mutu | KU |
| kepala@simmak.ppkp.local | kepala | kepala-klinik | KP06 |
| pjmutu@simmak.ppkp.local | pjmutu | pj-mutu | KP06 |
| akreditasi@simmak.ppkp.local | akreditasi | tim-akreditasi | KP06 |
| auditor@simmak.ppkp.local | auditor | auditor | KU |
| staff@simmak.ppkp.local | staff | staff | KP06 |

## 7. Prinsip Keamanan Akses
1. Menu sidebar difilter otomatis berdasarkan permission.
2. Route web dilindungi middleware `permission:*`.
3. Data scoped ke `organization_id` pengguna.
4. Konteks klinik aktif mempengaruhi filter data klinik.
5. Aksi kritis dicatat di audit trail (login/logout, dll.).
