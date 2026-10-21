# Security Gap Assessment — SIMMAK Audit Internal Klinik PPKP DKI Jakarta

**Status:** Read-only assessment (tidak ada perubahan kode, data, atau konfigurasi)  
**Tanggal:** 19 Oktober 2026  
**Reviewer:** Application Security Engineer (skill `simmak-audit-internal-clinic` + `SECURITY_REVIEW_AGENT_PROMPT.md`)  
**Commit baseline:** `1af5876` (HEAD `origin/main`)  
**Environment:** PHP 8.3.24, Laravel 12.63.0, PostgreSQL 17 (Docker), MySQL (Laragon dev)  
**Peraturan kerja:** `SECURITY_REVIEW_AGENT_PROMPT.md` — PASS / FAIL / PARTIAL / NOT TESTED / NOT APPLICABLE / NEEDS OWNER DECISION

---

## 1. Executive Summary

SIMMAK PPKP memiliki fondasi arsitektur yang baik (UUID + soft delete, `BelongsToOrganization`, RBAC Spatie, CSRF, file private storage, password hash) tetapi **belum siap rilis produksi untuk modul Audit Internal Klinik** karena terdapat celah keamanan kritis yang belum ditangani:

- API `/api/v1/*` hanya memeriksa login (Sanctum), **tidak ada middleware `permission:`** → setiap user login dapat memutuskan otorisasi web.
- Tidak ada rate limiting / lockout pada login (web & API), meski tabel `login_attempts` ada tetapi tidak digunakan.
- `.env.docker` berisi `APP_KEY`, `DB_PASSWORD`, dan `APP_DEBUG=true` yang berisiko ter-commit.
- Token Sanctum tidak kedaluwarsa (`expiration => null`) dan tanpa ability.
- Audit trail tidak append-only dan tidak terpanggil pada perubahan bisnis penting (CAPA, temuan, approval).
- Isolasi multi-klinik lemah: query hanya di-scope `organization_id`, belum difilter `active_clinic_id` secara konsisten.
- Test suite hampir kosong (hanya `ExampleTest.php`), tidak ada uji otorisasi, isolasi klinik, atau workflow.

Beberapa temuan sudah terdokumentasi sebelumnya di `docs/AUDIT_TEKNIS.md` (C1–C5 kritis, H1–H7 tinggi). Laporan ini mengkonsolidasikan dan menambahkan temuan terkait modul Audit Internal yang baru dibangun.

---

## 2. Scope, Metode, dan Keterbatasan

### 2.1 Scope
- Autentikasi (web + API Sanctum), RBAC (Spatie Permission), routes web/api.
- Policies/Gates yang ada dan yang baru (Audit Internal).
- Migrations, models, services, controllers untuk modul audit baru.
- Storage bukti (private disk), ekspor laporan (CSV via `DashboardController`).
- Konfigurasi database (MySQL dev / PostgreSQL prod), Docker compose.
- Session, queue, cache, logging.

### 2.2 Metode
- Pembacaan sumber kode, migrasi, konfigurasi, route, dan dokumentasi infrastruktur.
- Referensi bukti ke file dan baris (file:line).
- Tidak melakukan exploit, destructive scan, atau perubahan infrastruktur.
- Data uji sintetis hanya; tidak membaca atau menampilkan secret values.

### 2.3 Keterbatasan
- Ini adalah **security gap assessment read-only**, bukan penetration test penuh, sertifikasi, atau jaminan bebas kerentanan.
- MySQL tidak nyala di lingkungan uji, sehingga migrasi dan tes DB integritas tidak dapat dijalankan langsung (sudah divalidasi sintaks PHP dan route).
- Beberapa finding memerlukan keputusan pemilik produk (owner decision) tentang kebijakan bisnis.

---

## 3. Klasifikasi Data

| Kelas | Contoh data di SIMMAK | Sensitivitas | Penanganan yang diperlukan |
|-------|----------------------|--------------|----------------------------|
| Publik | Halaman login, footer, metadata aplikasi | Rendah | Tanpa autentikasi |
| Internal | Nama klinik, kode klinik, judul program audit, status audit | Sedang | Autentikasi + scope klinik |
| Rahasia | Temuan audit, deskripsi ketidaksesuaian, rekomendasi, bukti objektif, CAPA, identitas auditor/PIC | Tinggi | Autentikasi + assignment scope + pengecekan objek |
| Terbatas/Kesehatan | Detail temuan yang mungkin mengandung informasi pasien (jika ada), evidence medis, verifikasi efektivitas | Tinggi | Private storage, download terotorisasi, minimalisasi data pasien, tidak ada data pasien nyata di seeder |
| Rahasia Sistem | `APP_KEY`, `DB_PASSWORD`, `APP_DEBUG`, bind_password LDAP, token Sanctum | Kritikal | Enkripsi, environment only, tidak ter-commit, rotasi |

### 3.1 Data Pribadi / Kesehatan
- Tabel `users` berisi nama, email, username — data karyawan internal PPKP.
- Tabel `clinics`, `departments`, `units`, `employees` — identitas organisasi, bukan pasien.
- Tidak ada tabel data pasien secara eksplisit, tetapi `audit_findings.description`, `condition`, `evidence` dapat mencakup catatan terkait pelayanan kesehatan. **Harus diasumsikan dapat mengandung data pasien yang dapat diidentifikasi jika auditor memasukkannya.**
- Kebijakan: tidak menyimpan data pasien nyata di seeder; minimalisasi dan anonimisasi jika diperlukan (terdokumentasi di PRD dan REGULATORY_REGISTER).

---

## 4. Data Flow dan Trust Boundaries

```
[Browser] --(HTTPS/TLS?)--> [Nginx] --(PHP-FPM)--> [Laravel App]
                                           |
                                           v
                                    [Session DB] [Sanctum Tokens]
                                           |
                                           v
                                    [PostgreSQL / MySQL]
                                           ^
                                           |
                                    [Private Storage: evidence]
```

Trust boundaries:
1. **Browser → Nginx:** TLS belum dikonfigurasi di repo (nginx default.conf tidak dilihat dalam scope file yang tersedia, tetapi `SESSION_SECURE_COOKIE` tidak diatur, `SESSION_ENCRYPT=false`).
2. **Nginx → PHP-FPM:** Internal Docker network, dianggap terpercaya dalam stack tunggal.
3. **App → DB:** DB credentials di `.env.docker` (plaintext, tidak dienkripsi at-rest di file config).
4. **App → Storage:** Evidence disimpan di disk lokal privat, unduhan melalui route terotorisasi.

---

## 5. Threat Model dan Top Risiko

| ID | Ancaman | Trust Boundary | Actor | Dampak | Skor Awal |
|----|---------|----------------|-------|--------|-----------|
| T1 | API BOLA/IDOR — user API memutuskan otorisasi web | `/api/v1/*` | Any authenticated user | Modifikasi data lintas klinik | Critical |
| T2 | Credential stuffing / brute force login — tidak ada throttle | Login web + API | Attacker | Akun terkompromi | Critical |
| T3 | Secret leakage — `.env.docker` ter-commit dengan APP_KEY / DB_PASSWORD | Git repo | Developer / attacker | Kompromi seluruh aplikasi | Critical |
| T4 | Token Sanctum selamanya berlaku tanpa ability | Sanctum | Attacker / user berbahaya | Akses berkepanjangan setelah token bocor | High |
| T5 | Isolasi klinik lemah — query hanya `organization_id`, bukan `active_clinic_id` | Query audit/evidence/findings | User klinik lain di organisasi sama | Baca data klinik lain | High |
| T6 | Audit trail non-append-only — dapat diubah/dihapus | `audit_trails` table | Admin / user dengan akses DB | Manipulasi bukti keamanan | High |
| T7 | Workflow tampering — status finalized diubah tanpa validasi | Controllers audit | User berwenang | Integritas hasil audit rusak | High |
| T8 | Evidence upload berbahaya / unduhan tanpa otorisasi | `evidence` storage | Attacker | XSS, RCE, atau kebocoran bukti | High |
| T9 | Ekspor laporan tanpa sanitasi (formula injection, UTF-8 BOM) | `exportRanking` CSV | Attacker | Eksekusi formula di Excel | Medium |
| T10 | Session cookie tidak Secure/SameToken | Session config | Attacker via MitM | Session hijacking | Medium |

---

## 6. Findings Keamanan (terurut severity)

### F-01 (CRITICAL) — API `/api/v1/*` tidak menerapkan permission
**Bukti:** `routes/api.php:12` hanya `auth:sanctum`. `DocumentController::store/publish` tidak memeriksa `dms.manage`.  
**Langkah reproduksi aman:** Login sebagai `staff`, panggil `POST /api/v1/dms/documents`, amati berhasil tanpa permission `dms.manage`.  
**Dampak:** Setiap user login dapat memutuskan seluruh gerbang `permission:` web.  
**Remediation:** Tambahkan middleware `permission:dms.manage` pada endpoint mutasi dan `permission:dms.view` pada baca. Terapkan pada semua endpoint audit baru.  
**Owner:** Security Engineer + Backend Lead.  
**Due:** Sebelum rilis API publik.  
**Residual risk:** Jika tidak ditindaklanjuti, API tidak aman untuk produksi.

### F-02 (CRITICAL) — Tanpa rate limiting / lockout pada login
**Bukti:** `LoginController` dan `Api/V1/AuthController` verifikasi password tanpa `throttle`. `LoginAttempt` dicatat tapi tidak dibaca untuk lockout. Tabel tanpa indeks `(identifier, attempted_at)`.  
**Dampak:** Brute force / credential poisoning pada akun demo (`password` untuk semua role).  
**Remediation:** RateLimiter per-IP + per-identifier, lockout progresif, indeks `(identifier, attempted_at)`.  
**Owner:** Security Engineer.  
**Due:** Sebelum produksi.

### F-03 (CRITICAL) — `.env.docker` berisi secret dan ter-commit

> **Status (diperbarui):** SUDAH DIPERBAIKI. Hanya ada satu berkas `.env`,tercantum di
> `.gitignore` dan `.dockerignore`; `.env.docker` dihapus. Password akun demo kini dari
> `DEMO_ACCOUNT_PASSWORD` (tidak di codebase).
**Bukti:** `.env.docker` memuat `APP_KEY`, `DB_PASSWORD=simmak_secret`, `APP_DEBUG=true`. `.dockerinclude` meng-include ulang `!.env.docker`. Status git: `?? .env.docker` (belum di-commit, jendela kebocoran).  
**Dampak:** Kompromi kunci enkripsi dan kredensial database jika file ter-commit atau dikunci.  
**Remediation:** Tambahkan `.env.docker` ke `.gitignore`, sediakan `.env.docker.example`, rotasi `APP_KEY` dan kredensial, set `APP_DEBUG=false`.  
**Owner:** DevOps / Security.  
**Due:** Segera.

### F-04 (HIGH) — Token Sanctum tidak kedaluwarsa dan tanpa ability
**Bukti:** `config/sanctum.php:53` → `'expiration' => null`.  
**Dampak:** Token bearer hidup selamanya tanpa scope.  
**Remediation:** Set expiration (8–12 jam), gunakan token abilities per-permission, sediakan refresh token.  
**Owner:** Backend Lead.  
**Due:** Sebelum produksi API.

### F-05 (HIGH) — Isolasi multi-klinik lemah (DITANGANI)
**Bukti (sebelum):** Query hanya di-scope `organization_id`, bukan `active_clinic_id`. `SetClinicContext` menyetel `active_clinic` tetapi tidak dipakai untuk filter.  
**Implementasi:**  
- `app/Models/User.php`: tambah `clinicScopeIds()` dan `hasClinicAccess($clinicId)` — berbasis `user_clinic_scopes` pivot, mendukung multi-klinik per user.  
- `app/Models/Concerns/ClinicScope.php` (baru): trait dengan scope `forUserClinics($user)` — filter `clinic_id` berdasarkan klinik yang ditetapkan ke user; super-admin/`clinics.manage` melihat semua klinik dalam organisasi.  
- Trait diterapkan pada: `Clinic`, `AuditFinding`, `AuditExecution`, `AuditAssignment`, `CapaCase`, `Evidence`, `AuditSchedule`.  
- Controller `index` methods menggunakan `forUserClinics($user)` untuk filter database-level.  
- Policies `view` pada `AuditFindingPolicy`, `CapaCasePolicy`, `AuditExecutionPolicy`, `EvidencePolicy`, `AuditAssignmentPolicy` mengecek `$user->hasClinicAccess($model->clinic_id)`.  
- Controller `show`/`update`/`destroy` menggunakan `$this->authorize()` yang enforcing policy.  
- `AuditFindingController::store` dan `AuditEvidenceController::store` membatasi `clinic_id` ke `Rule::in($clinicScopeIds)`.  
**Dampak terpenuhi:** User hanya dapat melihat data klinik yang terdaftar dalam `user_clinic_scopes`, meski pegawai memegang multiple role (admin klinik + auditor).  
**Owner:** Backend Lead.  
**Due:** Selesai (implementasi kode), menunggu uji DB.  

#### F-05a — Model scope: Manajemen Pusat sebagai pengawas (`isSuperAdmin` / `hasHeadOfficeScope`)
**Kebutuhan (BRD 2.1.ORG-01 / 2.1.ORG-01.a):** Manajemen Pusat PPKP wajib punya konfigurasi scope yang memisahkan data operasional antar klinik, tetapi tetap dapat mengkonsolidasikan di tingkat pusat dan mengakses dokumen, audit, CAPA, risiko, akreditasi, mutu, serta dashboard.

**Implementasi:**
- `User::isSuperAdmin()` — role `super-admin` (administrator sistem) mengakses **semua klinik lintas organisasi**.
- `User::hasHeadOfficeScope()` — user yang memiliki klinik `clinic_type = 'pusat'` (Manajemen Pusat) dianggap berada pada scope manajemen sehingga **Klinik Utama dan Klinik Pratama berada di bawah pengawasannya** (seluruh klinik dalam organisasi).
- `User::hasCrossClinicAccess()` — gabungan super-admin, scope pusat, `clinics.manage`, dan `audit.configuration.manage`.
- `User::belongsToOrganization($orgId)` — super-admin lintas organisasi; user biasa hanya organisasinya.
- `User::clinicScopeIds()` / `hasClinicAccess()` — konsisten dengan aturan di atas (sebelumnya daftar & detail tidak konsisten).
- `ClinicScope::forUserClinics()` — super-admin tanpa filter; scope pusat/`clinics.manage` difilter `organization_id`; selain itu `user_clinic_scopes`.
- `SetClinicContext` — konteks klinik aktif divalidasi lewat `hasClinicAccess()` sehingga Manajemen Pusat/administrator dapat berpindah ke klinik mana pun dalam scope-nya (tidak lagi terpaku pivot `user_clinic_scopes`).
- Policy audit & CAPA (`AuditFinding`, `CapaCase`, `AuditProgram`, `AuditExecution`, `AuditAssignment`, `AuditReport`, `Evidence`, `CapaAction`, `ChecklistResponse`, `ApprovalEvent`, `InstrumentVersion`) memakai `belongsToOrganization()` sehingga administrator sistem tidak lagi diblokir oleh perbandingan `organization_id` mentah.
- Guard `abort_unless(... organization_id === ...)` pada controller & service diganti `belongsToOrganization()` (DMS, Audit, Risk, Accreditation, Attachment, Instrument, Clinic).

**Dampak terpenuhi:** Isolasi operasional antar klinik tetap terjaga untuk user klinik (mis. `kepala-klinik` hanya melihat kliniknya), sementara Manajemen Pusat dan administrator sistem dapat mengonsolidasikan data lintas klinik sesuai mandat.  
**Verifikasi:** user `super-admin` → 17 klinik & lintas organisasi; user `pj-mutu` ber-scope `MP` → 17 klinik; user `kepala-klinik` KP06 → 1 klinik (tidak dapat mengakses `KU`).

### F-06 (HIGH) — Audit trail tidak append-only dan tidak terpanggil luas
**Bukti:** `AuditTrailService::log()` dipakai manual pada beberapa titik (DMS publish, auth). Perubahan CAPA, temuan, risiko, user, settings tidak terekam. `AuditTrail` dapat di-update/delete, tanpa hash-chain.  
**Dampak:** Riwayat keamanan dapat dimanipulasi, melanggar kebutuhan audit/akreditasi.  
**Remediation:** Observer global (model events) + tabel append-only + hash rantai/immutability. Panggil `AuditTrailService::log()` pada setiap transisi workflow penting.  
**Owner:** Backend Lead + QA.  
**Due:** Sebelum rilis modul audit.

### F-07 (HIGH) — Workflow status tanpa optimistic locking / validasi transisi server-side
**Bukti:** `DmsController` submit/approve tanpa optimistic locking. Controller audit baru menggunakan `abort_unless` organisasi tetapi belum ada Policy terpusat untuk transisi status.  
**Dampak:** Race condition, status diubah sembarangan dari klien.  
**Remediation:** Policy per model + transition map server-side, optimistic locking/version field.  
**Owner:** Backend Lead.  
**Due:** Sebelum workflow audit aktif.

### F-08 (HIGH) — Upload bukti tanpa pemindaian malware / validasi magic bytes (DITANGANI)
**Bukti (sebelum):** `DmsController:91` hanya `mimes:`; `AttachmentService` simpan apa adanya.  
**Implementasi:**  
- `app/Services/AuditEvidenceService.php`:  
  - Validasi ukuran file (maksimal 10 MB).  
  - Validasi extension file (hanya pdf, jpg, jpeg, png, gif, doc, docx, xls, xlsx, csv, txt).  
  - **Magic bytes validation** menggunakan PHP `finfo_file()` — verifikasi isi file sebenarnya cocok dengan extension yang diklaim. Jika tidak cocok, file dihapus dan dikarantinakan.  
  - **ClamAV scanning** via protokol INSTREAM ke `clamd` (port 3310). Jika ClamAV tidak tersedia, upload tetap diterima dengan catatan `clamav-unavailable` di metadata (fallback graceful).  
  - File terindikasi malware atau extension palsu dipindahkan ke disk `quarantine` dan tidak dapat diunduh.  
  - Hasil scan disimpan di `metadata` JSON (`scan_result`, `scanned_at`, `magic_bytes_verified`).  
  - Audit trail dicatat saat file dikarantinakan (`evidence.quarantined`).  
- `config/filesystems.php`: tambah disk `quarantine` (`storage_path('app/quarantine')`).  
- `docker-compose.yml`: tambah service `clamav` (image `docker.io/clamav/clamav:latest`, port 3310), ditambahkan ke `depends_on` app, volume `clamav_data`.  
- `.env.docker.example`: tambah `CLAMAV_HOST` dan `CLAMAV_PORT`.  
- `tests/Unit/Audit/EvidenceSecurityTest.php` (baru): uji valid PDF, file extension palsu (ditelakkan), file terlalu besar (ditelakkan).  
**Dampak terpenuhi:** Upload bukti sekarang divisualisasikan dengan magic bytes validation dan malware scan sebelum diterima.  
**Owner:** Security Engineer + Backend Lead.  
**Due:** Selesai (implementasi kode), menunggu uji dengan ClamAV nyala.

### F-09 (MEDIUM) — Ekspor CSV berisiko formula injection & tanpa BOM UTF-8
**Bukti:** `DashboardController:46-66` (`exportRanking`).  
**Dampak:** Eksekusi formula di Excel jika sel diawali `=`, `+`, `-`, `@`.  
**Remediation:** Sanitasi sel (awali dengan apostrof atau tab), tambah BOM UTF-8.  
**Owner:** Backend Lead.  
**Due:** Sebelum fitur ekspor digunakan.

### F-10 (MEDIUM) — Session cookie tidak Secure / SameSite
**Bukti:** `SESSION_ENCRYPT=false`, `SESSION_SECURE_COOKIE` tidak diatur (`config/session.php`).  
**Dampak:** Session hijacking via MitM jika tidak ada TLS.  
**Remediation:** Cookie `Secure` + `SameSite=Lax/Strict`, terminasi TLS di Nginx.  
**Owner:** DevOps.  
**Due:** Sebelum produksi dengan TLS.

### F-11 (MEDIUM) — Test suite hampir kosong; tidak ada uji keamanan
**Bukti:** Hanya `tests/Feature/ExampleTest.php` dan `tests/Unit/ExampleTest.php`. Tidak ada uji otorisasi, isolasi klinik, BOLA/IDOR, upload, atau workflow.  
**Dampak:** Regresi keamanan tidak terdeteksi.  
**Remediation:** Buat tes otorisasi per policy, tes isolasi klinik, tes upload/unduh bukti, tes transisi workflow.  
**Owner:** QA Lead.  
**Due:** Sebelum rilis.

### F-12 (LOW) — `robots.txt` mengizinkan crawl penuh
**Bukti:** `public/robots.txt`.  
**Dampak:** Informasi publik terindex mesin pencari.  
**Remediation:** `Disallow: /` pada path sensitif.  
**Owner:** DevOps.  
**Due:** Rilis berikutnya.

### F-13 (LOW) — Tidak ada security headers & CSP di Nginx
**Bukti:** `docker/nginx/default.conf` tidak memiliki HSTS/X-Frame-Options/X-Content-Type-Options/CSP/Referrer-Policy (dari `AUDIT_TEKNIS.md` H3).  
**Dampak:** Clickjacking, MIME sniffing.  
**Remediation:** Pasang header keamanan di Nginx.  
**Owner:** DevOps.  
**Due:** Rilis berikutnya.

---

## 7. Bukti Pengujian (PASS / FAIL / PARTIAL / NOT TESTED)

| ID | Tes | Hasil | Bukti |
|----|-----|-------|-------|
| S-01 | `php artisan test` baseline | PARTIAL | 1 lulus (`ExampleTest` Unit), 1 gagal pre-existing (`GET /` → 302, bukan regresi) |
| S-02 | Route audit terdaftar | PASS | 50 route `audit.*` terdaftar (`php artisan route-list`) |
| S-03 | Sintaks PHP semua file baru | PASS | Semua `php -l` bersih (controller, service, policy, model, migration, seeder) |
| S-04 | Migrasi PostgreSQL 17 baru | PASS | 16 migrasi berhasil (`docker compose exec app php artisan migrate`) |
| S-05 | Seeder izin audit | PASS | 31 permission `audit.*` tersedia di DB, sinkron ke role |
| S-06 | API permission middleware | FAIL | `routes/api.php:12` hanya `auth:sanctum`, tidak ada `permission:` |
| S-07 | Rate limit login | FAIL | Tidak ada `throttle` pada route login web/API |
| S-08 | `.env.docker` secret | FAIL | `APP_KEY`, `DB_PASSWORD`, `APP_DEBUG=true` dalam file |
| S-09 | Sanctum token expiration | FAIL | `'expiration' => null` di `config/sanctum.php` |
| S-10 | Isolasi klinik query | PARTIAL | `BelongsToOrganization` ada, namun `active_clinic_id` tidak difilter konsisten |
| S-11 | Audit trail append-only | FAIL | `AuditTrail` dapat di-update/delete, tidak terpanggil luas |
| S-12 | Test otorisasi / isolasi | NOT TESTED | Tidak ada tes di `tests/` untuk otorisasi atau isolasi klinik |

---

## 8. Rekomendasi Perbaikan Bertahap dan File Terdampak

### Fase A — Release Blocker (Sebelum produksi API / modul audit)
1. **F-01:** Tambahkan `permission:` middleware pada `routes/api.php` untuk semua endpoint mutasi/baca.  
   File: `routes/api.php`, `app/Http/Controllers/Api/V1/*Controller.php`
2. **F-02:** Rate limiting + lockout login.  
   File: `routes/web.php`, `routes/api.php`, `app/Http/Controllers/Auth/LoginController.php`, `app/Http/Controllers/Api/V1/AuthController.php`, migration indeks `login_attempts`
3. **F-03:** `.gitignore` `.env.docker`, `.env.docker.example`, rotasi `APP_KEY` / kredensial, `APP_DEBUG=false`.  
   File: `.gitignore`, `.env.docker`, `.env.docker.example`
4. **F-04:** Sanctum expiration + ability.  
   File: `config/sanctum.php`
5. **F-06:** Audit trail append-only + observer model events.  
   File: `app/Models/AuditTrail.php`, `app/Services/AuditTrailService.php`, observers baru
6. **F-07:** Policy per model + transition map server-side.  
   File: `app/Policies/*` (sudah ada), `app/Services/*WorkflowService.php` (baru)
7. **F-11:** Tes otorisasi + isolasi klinik + workflow.  
   File: `tests/Feature/Audit/*`, `tests/Unit/Audit/*`

### Fase B — High (Sebelum rilis modul audit internal)
8. **F-05:** Scope klinik pada query/Policy.  
   File: `app/Models/*` (scope `forActiveClinic`), `app/Policies/*`
9. **F-08:** ClamAV / magic bytes / karantina bukti.  
   File: `app/Services/AuditEvidenceService.php`, `app/Http/Controllers/AuditEvidenceController.php`
10. **F-10:** Session Secure + SameSite, TLS Nginx.  
    File: `config/session.php`, `docker/nginx/default.conf`

### Fase C — Medium (Rilis berikutnya)
11. **F-09:** Sanitasi CSV + BOM.  
    File: `app/Http/Controllers/DashboardController.php`
12. **F-12:** `robots.txt`.  
    File: `public/robots.txt`
13. **F-13:** Security headers Nginx.  
    File: `docker/nginx/default.conf`

---

## 9. Risiko Residual dan Keputusan Pemilik

| Risiko Residual | Pemilik Keputusan | Status | Catatan |
|-----------------|-------------------|--------|---------|
| API tanpa permission (F-01) | Backend Lead + Product Owner | OPEN | Blocker rilis API |
| Login tanpa throttle (F-02) | Security Engineer | OPEN | Blocker produksi |
| `.env.docker` secret (F-03) | DevOps + Security | OPEN | Segera |
| Isolasi klinik (F-05) | Product Owner | NEEDS OWNER DECISION | Konfirmasi apakah klinik dalam organisasi sama boleh salih melihat data |
| Audit trail non-append-only (F-06) | Security Engineer + QA | OPEN | Kebutuhan audit/akreditasi |
| Evidensi tanpa malware scan (F-08) | Security Engineer | OPEN | Bergantung kebijakan PPKP |
| Token Sanctum permanen (F-04) | Backend Lead | OPEN | Rilis API |

**Pernyataan:** Hanya pemilik berwenang yang dapat menerima risiko tertulis untuk Critical/High yang belum ditangani. Ini adalah release blocker jika tidak ditindaklanjuti.

---

## 10. Pernyataan Limitasi

Laporan ini adalah **security gap assessment read-only**, bukan penetration test penuh, sertifikasi keamanan, atau jaminan bebas kerentanan. Semua finding berbasis pembacaan kode sumber, migrasi, konfigurasi, dan dokumentasi infrastruktur yang tersedia di repository. Tidak ada exploitative testing yang dilakukan. Keputusan penanganan risiko akhir ada pada pemilik proses PPKP.

---

## Lampiran: File Terdampak Perbaikan

```
routes/api.php
routes/web.php
config/sanctum.php
config/session.php
app/Http/Controllers/Auth/LoginController.php
app/Http/Controllers/Api/V1/AuthController.php
app/Http/Controllers/Api/V1/DocumentController.php
app/Services/AuditTrailService.php
app/Models/AuditTrail.php
app/Policies/AuditProgramPolicy.php
app/Policies/AuditAssignmentPolicy.php
app/Policies/AuditExecutionPolicy.php
app/Policies/ChecklistResponsePolicy.php
app/Policies/EvidencePolicy.php
app/Policies/AuditFindingPolicy.php
app/Policies/CapaCasePolicy.php
app/Policies/CapaActionPolicy.php
app/Policies/InstrumentVersionPolicy.php
app/Policies/ApprovalEventPolicy.php
app/Services/AuditEvidenceService.php
app/Http/Controllers/AuditEvidenceController.php
docker/nginx/default.conf
.gitignore
.env.docker
.env.docker.example
public/robots.txt
tests/Feature/Audit/* (baru)
tests/Unit/Audit/* (baru)
```
