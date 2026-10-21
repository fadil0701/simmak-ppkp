# Audit Teknis & Keamanan — SIMMAK PPKP

> Dokumen ini adalah hasil audit langsung terhadap codebase (Laravel 12, Blade/Tailwind, Docker + PostgreSQL), migrasi, konfigurasi, route, dan infrastruktur. Bukan review dokumen saja.
>
> **Tanggal audit:** 7 Oktober 2026
> **Ruang lingkup:** seluruh repositori `E:\laragon\www\simmak-ppkp`
> **Metode:** pembacaan kode sumber, migrasi, config, route, dan infra; setiap temuan disertai bukti `file:line`.

---

## Ringkasan Eksekutif

Fondasi aplikasi **layak produksi awal**: UUID + soft delete konsisten, `BelongsToOrganization`, RBAC Spatie, CSRF, file upload di disk privat, password di-hash, health check `/up`, PHP-FPM non-root. Namun sistem **belum layak** untuk audit/akreditasi skala nasional karena ada **celah keamanan kritis** (autentikasi API tanpa otorisasi, tanpa rate limit, rahasia berpotensi bocor, audit trail tidak utuh) dan **kesenjangan kepatuhan** (tanpa MFA, tanpa e-signature, instrumen akreditasi masih data demo).

**Skor kematangan kasar:** Keamanan 4/10 · Ketahanan/Operasi 4/10 · Kesesuaian Domain 5/10 · Kualitas Teknis 6/10.

---

## KRITIS

### C1 — API `/api/v1/*` tidak menerapkan permission, hanya login
`routes/api.php:12` hanya `auth:sanctum`. `DocumentController::store/publish` (`app/Http/Controllers/Api/V1/DocumentController.php:48,99`) tidak memeriksa `dms.manage`. Setiap user yang dapat login (mis. `staff`) dapat membuat/mempublikasikan dokumen via API, melewati seluruh gerbang `permission:` web.

**Rekomendasi:** tambahkan middleware `permission:dms.manage` pada endpoint mutasi, dan `permission:dms.view` pada operasi baca.

### C2 — Tanpa rate limiting / lockout pada login (web & API)
`app/Http/Controllers/Auth/LoginController.php:26-74` dan `app/Http/Controllers/Api/V1/AuthController.php:19-69` memverifikasi password tanpa throttle. `LoginAttempt` dicatat tetapi **tidak pernah dibaca** untuk lockout. Tidak ada `throttle` di `routes/api.php` maupun `app/Providers/AppServiceProvider.php`. Tabel `login_attempts` bahkan tanpa indeks (`database/migrations/2026_07_08_000002_create_users_and_auth_tables.php:76-84`).

**Rekomendasi:** `RateLimiter` per-IP + per-identifier, lockout progresif, indeks `(identifier, attempted_at)`.

### C3 — Rahasia & APP_KEY pada `.env.docker` berisiko ter-commit

> **Status (diperbarui):** SUDAH DIPERBAIKI. Kini hanya ada satu berkas `.env` yang
> tercantum di `.gitignore` dan `.dockerignore`; `.env.docker`/`.env.docker.example`
> dihapus. Template tunggal: `.env.example`.
`.gitignore:11` hanya menutup `.env`; `.dockerignore:14` justru meng-*include* ulang `!.env.docker`. Berkas `.env.docker` memuat `APP_KEY`, `DB_PASSWORD=simmak_secret`, dan `APP_DEBUG=true`. Status git: `?? .env.docker` (belum di-commit — jendela kebocoran).

**Rekomendasi:** tambahkan `.env.docker` ke `.gitignore`, sediakan `.env.docker.example`, rotasi `APP_KEY`/kredensial, set `APP_DEBUG=false`.

### C4 — Token Sanctum tidak kedaluwarsa & tanpa ability
`config/sanctum.php:53` → `'expiration' => null`. Token Bearer hidup selamanya tanpa scope.

**Rekomendasi:** set `expiration` (mis. 8–12 jam), gunakan token abilities per-permission, sediakan refresh token.

### C5 — Audit trail tidak lengkap dan tidak tamper-evident
`app/Services/AuditTrailService.php` hanya dipanggil manual pada publish/create dokumen dan auth. Perubahan CAPA, temuan audit, risiko, user, dan settings **tidak terekam**. `app/Models/AuditTrail.php` dapat di-update/delete, tanpa hash-chain/append-only. Ini kontradiksi langsung dengan sistem audit/akreditasi.

**Rekomendasi:** observer global (model events) + tabel append-only + hash rantai/immutability (mis. trigger DB atau ledger bertanda tangan).

---

## TINGGI

### H1 — Isolasi multi-klinik lemah
Query hanya di-scope `organization_id`, bukan klinik aktif: `app/Http/Controllers/DmsController.php:28-31,61-63`, `app/Http/Controllers/AttachmentController.php:14`, `app/Http/Controllers/Api/V1/DocumentController.php:23-24`. Peran klinik dapat membaca/mengunduh data klinik lain dalam satu organisasi. `SetClinicContext` menyetel `active_clinic` tetapi tidak dipakai untuk filter.

**Rekomendasi:** terapkan scope klinik pada model/query atau Policy per-resource.

### H2 — Kebijakan kata sandi lemah, tanpa MFA
`app/Http/Controllers/UserController.php:49` memakai `Password::defaults()` (default framework); tidak ada rotasi/history/breach-check. Super-admin tanpa 2FA.

**Rekomendasi:** kebijakan kata sandi eksplisit + MFA wajib untuk peran istimewa.

### H3 — Tanpa security headers & TLS
`docker/nginx/default.conf` tidak memiliki HSTS/X-Frame-Options/X-Content-Type-Options/CSP/Referrer-Policy; `SESSION_ENCRYPT=false` dan `SESSION_SECURE_COOKIE` tidak diset (`config/session.php:50,172`).

**Rekomendasi:** header keamanan di nginx, cookie `Secure` + `SameSite`, terminasi TLS.

### H4 — Nyaris tanpa test & tanpa CI/CD
Hanya `tests/Feature/ExampleTest.php` dan `tests/Unit/ExampleTest.php`. Tidak ada `.github/workflows`. Skala nasional tanpa regression gate berisiko tinggi.

### H5 — Upload tanpa pemindaian malware
`app/Http/Controllers/DmsController.php:91` hanya `mimes:`; `app/Services/AttachmentService.php` menyimpan apa adanya.

**Rekomendasi:** antivirus (ClamAV) + verifikasi magic bytes + karantina.

### H6 — Rahasia LDAP tersimpan plaintext
`database/migrations/2026_07_08_000002_create_users_and_auth_tables.php:54` (`bind_password text`). Flag `is_encrypted` ada di `system_settings` tetapi tidak dipakai.

**Rekomendasi:** cast `encrypted` / secret manager.

### H7 — `"minimum-stability": "dev"`
`composer.json:80` → dependensi tidak reproducible; `composer audit` belum masuk CI.

---

## SEDANG

| # | Temuan | Bukti | Rekomendasi |
|---|--------|-------|-------------|
| M1 | Race condition penomoran dokumen/versi (`count+1`/`max+1`) beradu dengan unique constraint → 500 saat konkuren | `app/Services/DocumentService.php:19-23,28` | Sequence DB / transaksi + retry |
| M2 | `per_page` API tanpa batas atas → DoS/eksfiltrasi | `app/Http/Controllers/Api/V1/DocumentController.php:43` | Batasi `per_page` maksimum |
| M3 | CSV export berisiko formula injection & tanpa BOM UTF-8 | `app/Http/Controllers/DashboardController.php:46-66` | Sanitasi sel + tambah BOM |
| M4 | `audit_trails`/`activity_logs` tanpa strategi retensi/arsip | `database/migrations/2026_07_08_000003_...:50-83` | Kebijakan retensi + arsip |
| M5 | `qr_token` plaintext & tidak diputar ulang saat versi berubah | `app/Services/DocumentService.php:77` | Hash token + rotasi |
| M6 | Workflow status (submit/approve) tanpa optimistic locking | `app/Http/Controllers/DmsController.php:140-199` | Version column / lock |
| M7 | `robots.txt` mengizinkan crawl penuh | `public/robots.txt` | `Disallow: /` |
| M8 | Belum ada klasifikasi data/DPIA/retensi sesuai UU PDP No.27/2022 | — | Tata kelola data |

---

## KESESUAIAN DOMAIN (Akreditasi Klinik Pratama & Utama)

Fondasi benar: model `AccreditationType` FKTP/FKTL, chapter→standard, self-assessment, gap, mock survey, evidence (`app/Services/AccreditationService.php`, `database/seeders/AccreditationSeeder.php`). Namun:

- **D1** Instrumen masih **data demo sintetis** ("Standar FKTP KARS", 6/3 bab), bukan instrumen resmi berlaku & **tidak berversi**. Untuk skala nasional perlu katalog standar resmi (KARS/Kemenkes), versioning per periode, aturan skoring resmi (bobot, N/A, threshold kelulusan).
- **D2** Belum ada **tanda tangan elektronik BSrE** untuk dokumen resmi/berita acara/sertifikat.
- **D3** Belum ada peran **surveyor eksternal & penjadwalan survei resmi**; baru mock survey internal.
- **D4** Belum ada integrasi **SSO LDAP/AD**, RME/SIPN, atau HR (masih `LDAP_ENABLED=false`).
- **D5** Hanya mendukung **satu organisasi**; skala nasional butuh multi-tenant/multi-org, isolasi data, dan skalabilitas horizontal.

---

## Roadmap Rekomendasi

### Fase 0 — Segera (1–2 minggu)
1. Tutup C1: pasang `permission:` di seluruh endpoint API.
2. Tutup C2: throttle login + lockout (web & API).
3. Tutup C3: `.gitignore` `.env.docker`, rotasi kredensial/APP_KEY, `APP_DEBUG=false` + `.env.docker.example`.
4. Tutup C4: expiration + abilities token Sanctum.
5. Pasang lint/typecheck/test di CI, aktifkan `composer audit` + `npm audit`.

### Fase 1 — 1–2 bulan
6. C5: observer audit menyeluruh + tabel append-only.
7. H1: enforcement scope klinik via Policy.
8. H2: kebijakan kata sandi + MFA peran istimewa.
9. H3: TLS + security headers + cookie aman.
10. H5/H6: ClamAV, validasi magic bytes, enkripsi rahasia LDAP.
11. H4: test suite minimal untuk authz, isolasi tenant, workflow.

### Fase 2 — 3–6 bulan (kesiapan nasional)
12. Instrumen akreditasi resmi + versioning + aturan skoring (D1).
13. e-Signature BSrE + registry sertifikat (D2).
14. Portal surveyor eksternal & penjadwalan survei (D3).
15. Multi-org/multi-tenant + DR teruji (backup otomatis, RPO/RTO) (D5).
16. Integrasi SSO LDAP/AD (D4), DPIA & tata kelola UU PDP (M8).

---

## Kekuatan (Pertahankan)

- UUID + soft delete konsisten (`app/Models/Concerns/BaseModel.php`).
- Organisasi scoping terpusat (`app/Models/Concerns/BelongsToOrganization.php`).
- RBAC terpusat di `database/seeders/DatabaseSeeder.php` + `app/Support/SimmakMenu.php`.
- CSRF pada seluruh form; upload ke disk privat dengan checksum SHA-256.
- Password ter-hash + pencatatan `login_attempts`.
- Pemisahan volume Docker (`vendor`/`node_modules` named volume).
- Dokumentasi lengkap; kompatibilitas MySQL/PostgreSQL sudah ditangani (`docs/DEPLOY_DOCKER.md`).
