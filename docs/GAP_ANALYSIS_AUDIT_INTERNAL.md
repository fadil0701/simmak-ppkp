# Gap Analysis — SIMMAK Audit Internal Klinik (Gate 0)

**Tanggal:** 19 Oktober 2026  
**Pengelola dokumen:** Agen implementasi (skill `simmak-audit-internal-clinic`)  
**Referensi:** `PRD.md`, `IMPLEMENTATION_PLAN.md`, `ERD.md`, `PERMISSION_MATRIX.md`, `WORKFLOWS_AND_STATES.md`, `UI_UX_SPEC.md`, `ACCEPTANCE_TESTS.md`, `REGULATORY_REGISTER.md` (skill base: `.agents/skills/simmak-audit-internal-clinic/references/`)

---

## 1. Ringkasan Eksekutif

Repositori SIMMAK PPKP sudah memiliki fondasi yang kuat untuk digunakan sebagai dasar pengembangan modul Audit Internal Klinik Release 1:

- Laravel 12 + PHP 8.3, PostgreSQL 17 (Docker) / MySQL (Laragon dev), UUID + soft delete konsisten.
- Organisasi dan klinik master sudah ada (16 klinik: 1 KU + 15 KP), sesuai cakupan PRD.
- RBAC Spatie sudah terpasang dan di-seed dengan peran (super-admin, admin-mutu, kepala-klinik, pj-mutu, auditor, staff, tim-akreditasi).
- Audit trail, activity log, dan CAPA dasar sudah ada.
- UI Blade + Tailwind v4 dengan layout `layouts.simmak` dan komponen `simmak-*`.

**Namun, belum ada inti spesifik modul Audit Internal Klinik sesuai PRD/ERD:** tidak ada instrument/standar versi, audit assignment & team, audit execution, checklist response, evidence privat, approval event, dan verifikasi terpisah dari CAPA. Otorisasi saat ini cukup primitif (`abort_unless` pada controller) dan belum ada Policy class. Test suite hampir kosong (hanya `ExampleTest.php`).

Rekomendasi: implementasi secara bertahap (vertical slice per Gate), add-only migration, tidak merusak data/logika yang ada, dan menambahkan uji otorisasi + regresi sebelum setiap rilis.

---

## 2. Hasil Inventarisasi Repositori (Gate 0)

### 2.1 Runtime & dependensi

| Item | Kondisi aktual |
|------|----------------|
| PHP | 8.3.24 (CLI) — sesuai `composer.json` (`^8.2`) |
| Laravel | `^12.0` (framework) |
| Paket kunci | `laravel/sanctum ^4.3`, `livewire/livewire ^4.3`, `spatie/laravel-permission ^8.3`, `simplesoftwareio/simple-qrcode ^4.2` |
| Dev | `phpunit/phpunit ^11.5.3`, `laravel/pint ^1.13`, `laravel/pail ^1.2.2` |
| Frontend | Vite 6, Tailwind CSS 4, Laravel Vite plugin, Axios |
| `vendor/` | **Belum ada** (perlu `composer install` sebelum `artisan` berjalan) |
| `node_modules/` | Ada (tidak perlu install ulang kecuali ubah `package.json`) |

### 2.2 Git status & branch

- Branch: `main`, sinkron dengan `origin/master` (tidak ada unmerged commit).
- File dimodifikasi (8): `README.md`, `app/Http/Controllers/AccreditationController.php`, `app/Models/AccreditationProgram.php`, `app/Services/DashboardService.php`, 2 migrasi (core organization, dms), `docker-compose.yml`, `docker/php/Dockerfile`.
- File tidak terlacak (7): `.agents/`, `.dockerignore`, `.env.docker`, `AGENTS.md`, `docker/php/entrypoint.sh`, `docs/AUDIT_TEKNIS.md`, `docs/DEPLOY_DOCKER.md`.
- **Tindakan jaga:** tidak mencoba commit/reset perubahan pengguna sebelum disepakati. Semua perubahan baru akan dilakukan pada branch terpisah atau commit terpisah yang jelas.

### 2.3 Konfigurasi database & PostgreSQL

- `config/database.php`: default `sqlite`, connection `pgsql` tersedia (host `127.0.0.1`, port `5432`).
- `.env` (dev): PostgreSQL `DB_CONNECTION=pgsql`, host `127.0.0.1`, port `5432`, DB `simmak_ppkp`, user `simmak`.
- `.env.docker` (prod/Docker): PostgreSQL `pgsql`, host `postgres`, port `5432`, DB `simmak_ppkp`, user `simmak`.
- **Diperbarui:** kini hanya ada **satu** berkas `.env` (lihat `.env.example`). Docker Compose memakai `.env` yang sama dan menimpa nilai khusus container (`DB_HOST=postgres`, `REDIS_HOST=redis`, `CLAMAV_HOST=clamav`, `APP_URL`) lewat blok `environment:`. Berkas `.env.docker`/`.env.docker.example` dihapus.
- Migrasi sudah diperbaiki untuk kompatibilitas PostgreSQL (lihat `docs/DEPLOY_DOCKER.md` "Perbaikan PostgreSQL"):
  - FK self-referensi ditunda via `Schema::table` setelah tabel dibuat.
  - `FIELD()` diganti `CASE ... WHEN`.
  - Kutip ganda SQL diganti kutip tunggal.
- `pgcrypto` extension diaktifkan pada migrasi organisasi untuk PG.

### 2.4 Struktur database (migrasi yang relevan audit)

Tabel yang **sudah ada**:
- `organizations`, `clinics` (code, name, clinic_type, is_main_clinic, accreditation_type/status, is_active), `departments`, `units`, `employees`.
- `users` (organization_id, employee_id, default_clinic_id, email, password, auth_provider, is_active), `user_clinic_scopes`.
- `audit_programs` (program_code, name, status, created_by, updated_by, fiscal_year_id).
- `audit_schedules` (schedule_code, title, clinic_id, scheduled_start/end, lead_auditor_id, status).
- `audit_findings` (finding_code, title, severity, description, requirement_reference, status, assigned_to, clinic_id, audit_schedule_id).
- `capa_cases` (case_number, source_type/source_id, priority, owner_id, target_close_date, status).
- `capa_actions` (action_type, description, assigned_to, due_date, status).
- `capa_root_cause_analyses`, `capa_verifications` (result, verified_by).
- `audit_trails` (event, auditable_type/id, old/new_values, ip_address, metadata).
- `activity_logs` (log_name, description, subject_type/id, properties).
- `system_settings`, `organization_settings`, `fiscal_years`.
- Spatie permission tables (`roles`, `permissions`, `model_has_*`, `role_has_permissions`).
- `personal_access_tokens` (Sanctum).

Tabel yang **belum ada** (diperlukan ERD Audit Internal):
- `audit_standards`, `regulatory_sources`, `instrument_versions`, `standard_sections`, `standard_criteria`.
- `audit_assignments`, `audit_team_members`, `audit_executions`.
- `checklist_responses`, `evidence`, `response_evidence`.
- `findings` (kemungkinan diperluas dari `audit_findings`), `finding_evidence`.
- `corrective_actions` (kemungkinan diperluas dari `capa_cases`/`capa_actions`), `action_evidence`.
- `verifications` (kemungkinan terpisah dari `capa_verifications` untuk finding).
- `approval_events`.

Catatan: ERD skill menyarankan `audit_assignments` satu klinik atau multi-klinik **belum ditetapkan** (design decision tertunda).

### 2.5 Sistem autentikasi

- Web: Laravel built-in Auth (`LoginController`: login/logout), sesi database, CSRF pada semua form.
- API (`/api/v1/*`): Sanctum (`auth:sanctum`), endpoint `auth/login`, `auth/me`, `auth/logout`, dashboard executive, DMS.
- **Gap keamanan (terdokumentasi `docs/AUDIT_TEKNIS.md` C1):** endpoint API hanya memeriksa login, **tidak ada middleware `permission:`** pada API. Setiap user yang login dapat memutuskan otorisasi web.
- `config/sanctum.php`: token expiration `null` (token hidup selamanya), tanpa ability.
- Tidak ada rate limiting / lockout pada login (web & API), meski tabel `login_attempts` ada.
- Tidak ada `Policies/` directory. Otorisasi dilakukan inline via `abort_unless($model->organization_id === $user->organization_id, 403)` di controller.

### 2.6 RBAC

- Paket: `spatie/laravel-permission`, guard `web`, konfigurasi `config/permission.php` (teams nonaktif).
- Permission yang di-seed (`DatabaseSeeder`): `dashboard.view`, `dashboard.executive`, `dashboard.clinic`, `dms.view/manage`, `audit.view/manage`, `capa.view/manage`, `risk.view/manage`, `accreditation.view/manage`, `quality.view/manage`, `users.view/manage`, `settings.view/manage`, `workflow.manage`.
- **Gap vs `PERMISSION_MATRIX.md`:** permission spesifik audit belum ada di seeder dan belum ada di route:
  - `audit.program.view/create/update/submit/approve`
  - `audit.standard.view/manage/review/publish`
  - `audit.assignment.view/manage`
  - `audit.execution.view/execute/submit`
  - `audit.evidence.upload/view/download`
  - `audit.finding.view/create/update/review`
  - `audit.report.review/finalize/export`
  - `audit.action.view/update/submit`
  - `audit.verification.create`
  - `audit.finding.close/reopen`
  - `audit.log.view`
  - `audit.configuration.manage`
- Peran yang di-seed: `super-admin`, `admin-mutu`, `kepala-klinik`, `pj-mutu`, `auditor`, `staff`, `tim-akreditasi`. Belum ada peran `reviewer`, `verifier`, `pic-tindak-lanjut`, `report-reader` yang disebutkan PRD/Permission Matrix.
- Route web sudah dikelompokkan dengan middleware `permission:audit.view` dan `permission:audit.manage`, tetapi hanya mencakup audit/index, findings, capa. Tidak ada route untuk program, assignment, standard/instrument, execution, verification, approval.

### 2.7 Master klinik

- 16 klinik sudah di-seed: 1 Klinik Utama (`KU`) + 15 Klinik Pratama (`KP01`–`KP15`), sekaligus 1 `Manajemen Pusat` (`MP`).
- `clinics.is_main_clinic` membedakan KU. `clinic_type` = `pratama`/`utama`/`pusat`.
- `user_clinic_scopes` mendukung multi-klinik per user. `SetClinicContext` resolves `active_clinic_id` dari session / `default_clinic_id`.
- **Gap:** klinik masih dalam satu organisasi (`PPKP-DKI`). Multi-organisasi bukan cakupan R1.

### 2.8 Komponen UI / design system

- Layout: `resources/views/layouts/simmak.blade.php` (digunakan audit/capa/dashboard) + `layouts/app.blade.php` (lebih sederhana, pakai emoji ikon).
- Partials: `sidebar.blade.php`, `header.blade.php`, `footer.blade.php`, `partials/dashboard/*`.
- CSS: `resources/css/app.css` (Tailwind v4, `@import 'tailwindcss'`), komponen `.simmak-card`, `.simmak-badge`, `.simmak-kpi-card`, `.simmak-status-outline`.
- Palet: navy, teal, primary. Font sans (Segoe UI / system-ui).
- View yang ada untuk audit: `audit/index.blade.php`, `audit/checklist.blade.php`, `audit/reports.blade.php`, `audit/findings/index.blade.php`, `audit/findings/show.blade.php`, `capa/index.blade.php`, `capa/show.blade.php`.
- `SimmakMenu::items()`: menu statis dengan permission. Tidak memiliki item untuk program audit, assignment, standard/instrument, verification, approval, audit trail.

**Gap vs `UI_UX_SPEC.md`:**
- Halaman saat ini banyak pakai kartu metrik berukuran sama (dashboards) — dihindari sesuai spec.
- `app.blade.php` menggunakan emoji sebagai ikon utama — dihindari sesuai spec.
- Belum ada komponen reusable: filter bar, data table terstruktur, empty state, confirmation dialog, activity timeline, evidence uploader, workflow stepper, permission-aware action menu.
- Belum ada prioritas antrian kerja (work queue) pada dashboard audit.
- Belum ada breadcrumb dan konteks klinik/program/periode pada halaman detail.

### 2.9 Test suite

- `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` (contoh standar Laravel).
- `tests/TestCase.php` ada.
- `phpunit.xml`: testsuite Unit + Feature, `DB_CONNECTION`/**tidak** di-override (test terhadap DB `.env` MySQL per AGENTS.md), `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`, `BCRYPT_ROUNDS=4`.
- **Gap:** tidak ada uji otorisasi, uji isolasi klinik, uji workflow transisi, uji upload/unduh evidence, uji laporan PDF/Excel. Hampir nol cakupan regression.

### 2.10 Audit trail

- `AuditTrailService::log()` ada, dipakai secara manual pada beberapa titik (DMS publish, auth).
- `AuditTrail` model dapat di-update/delete, **tidak append-only**, tidak ada hash chain / tamper-evident.
- **Gap vs skill:** audit trail harus mencakup aksi bisnis penting (program approve, assignment, finding close, verification, CAPA action, perubahan finalized result) dengan actor, timestamp, reason, before/after.

### 2.11 Dokumen / infrastruktur lain

- `docs/AUDIT_TEKNIS.md`: audit keamanan (7 Oktober 2026), menemukan C1–C5 kritis dan H1–H7.
- `docs/DEPLOY_DOCKER.md`: stack Docker, PostgreSQL 17, perbaikan kompatibilitas PG, backup/restore belum detail.
- `routes/console.php`: jadwal `dms:check-expiry` harian 06:00. Belum ada jadwal notifikasi audit.
- `app/Support/SimmakMenu.php`, `RoleLabel.php`, `UiLabel.php`: helper UI/label.

---

## 3. Analisis Gap vs Referensi Skill

| Aspek | Referensi (skill/PRD/ERD) | Kondisi aktual | Status |
|-------|---------------------------|----------------|--------|
| Clinic master reuse | Klinik terkonfigurasi, tidak hard-code | Ada (16 klinik, seeder) | ✓ Cukup |
| User / RBAC reuse | Users, roles, permissions, clinic scope | Ada (Spatie, 7 role, user_clinic_scopes) | ✓ Cukup (perlu tambah role/permission) |
| Standards & instrument versioning | `audit_standards`, `regulatory_sources`, `instrument_versions`, `standard_sections`, `standard_criteria`, immutable published version | **Belum ada** | ✗ Belum ada |
| Audit program | `audit_programs` + approval history | Ada (`audit_programs`, tapi tidak ada approval event terpisah) | △ Parsial |
| Audit assignment + team | `audit_assignments`, `audit_team_members`, conflict-of-interest | **Belum ada** (`audit_schedules` ada tapi bukan assignment dengan team member) | ✗ Belum ada |
| Audit execution | `audit_executions`, instrument_version snapshot | **Belum ada** | ✗ Belum ada |
| Checklist responses | `checklist_responses`, response values, N/A reason, evidence link | **Belum ada** (`audit_checklists`/`audit_checklist_items` ada tapi bukan respons per eksekusi) | ✗ Belum ada |
| Evidence (private) | `evidence`, private storage, authorized download, MIME/size validation | DMS `documents`/`attachments` ada (publik-ish), belum khusus audit evidence privat | △ Parsial |
| Findings | `findings` linked to criterion/evidence/clinic/audit, risk, recommendation, owner, due | `audit_findings` ada (terkait `audit_schedules`, belum terkait instrument version/criterion) | △ Parsial |
| CAPA-lite | `corrective_actions`, root cause, PIC, progress, evidence, verification | `capa_cases`/`capa_actions`/`capa_verifications` ada | △ Parsial (perlu verifikasi independen & separation of duties) |
| Verification & closure | `verifications`, closure/reopen workflow, separation of duties | Ada CAPA verification, belum ada verifikasi temuan terpisah, belum ada reopening audit trail terjamin | △ Parsial |
| Approval/review workflow | `approval_events`, transition map, self-approval blocked | **Belum ada** | ✗ Belum ada |
| Dashboard real data | Agregat DB, drill-down, clinic scope | `DashboardService::executiveData` ada (real data, cached) | ✓ Cukup |
| PDF/Excel reports | Filtered exports respecting scope | `ReportsController`, `exportRanking` ada, belum spesifik audit | △ Parsial |
| Notifications | In-app + optional email | Belum ada notifikasi audit | ✗ Belum ada |
| Audit trail | Append-oriented, protected, before/after | `audit_trails` ada, tpa append-only & belum terpanggil luas | △ Parsial |
| Otorisasi server-side | Policy + scope + assignment + state + SoD | Inline `abort_unless`, tidak ada `Policies/`, tidak ada SoD teruji | ✗ Belum ada |
| Test suite | Unit, feature, authorization, workflow, regression | Hanya `ExampleTest.php` | ✗ Belum ada |
| UI/UX | Custom institutional, no emoji/fake metrics, responsive | Layout ada tapi generic (kartu metrik, emoji di `app.blade.php`) | △ Parsial |
| Regulatory register | `regulatory_sources`, verification status, UNVERIFIED draft | **Belum ada** | ✗ Belum ada |

Kesimpulan: fondasi organisasi/klinik/RBAC/dashboard/CAPA dasar sudah ada, tetapi **intih audit internal spesifik (standar versi, assignment, eksekusi, checklist, evidence privat, approval, verifikasi terpisah, uji otorisasi) belum ada dan harus dibangun sebagai vertical slice baru.**

---

## 4. Keputusan Belum Ditetapkan (Open Decisions)

Berikut adalah keputusan yang perlu ditetapkan sebelum/di selama implementasi, menurut `IMPLEMENTATION_PLAN.md` dan `ERD.md`, serta PRD §10. Mereka **belum boleh diangap sudah disetujui**:

1. **Satu assignment per klinik atau satu assignment mencakup beberapa klinik?** (`ERD.md` design decision 1; `audit_assignments.clinic_id` nullable)
2. **Apakah perlu assessment berulang (repeated checklist assessment) atau satu kali per eksekusi?** (`ERD.md` design decision 2)
3. **Tahap approval persis siapa dan judul otorisasi apa?** (`ERD.md` design decision 3; PRD §10 open decision 1)
4. **Peran `reviewer`, `verifier`, `pic-tindak-lanjut`, `report-reader` — apakah dibuat sebagai role Spatie baru atau ditangani lewak permission scope?**
5. **Retention & archival periode untuk evidence, audit trail, dan finalized result.** (PRD §10 open decision 4, 8)
6. **Versi standar/instrumen resmi mana yang akan digunakan?** (KMK HK.01.07/MENKES/1983/2022 dan Permenkes 34/2022 masih **UNVERIFIED**; tidak boleh ditetapkan sebagai final sebelum dokumen resmi diperiksa per `REGULATORY_REGISTER.md`)
7. **Aturan skoring, opsi penilaian, bobot, dan ambang kelulusan** — hanya jika instrumen resmi secara eksplisit menentukannya (PRD non-goal #6).
8. **Apakah notifikasi email diperlukan atau cukup in-app?** (PRD §10 open decision 5)
9. **Format dan tanda tangan PDF/Excel yang disetujui.** (PRD §10 open decision 7)
10. **IdentitasProvider saat ini dan implementasi RBAC yang efektif** — apakah LDAP akan diaktifkan? (PRD §10 open decision 6; saat ini `LDAP_ENABLED=false`)
11. **Master klinik organisasi dan jabatan resmi** — daftar 16 klinik adalah data contoh seeder, belum divalidasi sebagai master resmi PPKP (PRD §10 open decision 9).

Keputusan ini akan dicatat dalam `docs/decisions/` (atau register setara) dengan format ADR, alasan, dan dampak, sebelum implementasi menyentuh area terkait.

---

## 5. Risiko Teknis

| Risiko | Dampak | Mitigasi |
|--------|--------|----------|
| **API tanpa permission middleware** (C1 `AUDIT_TEKNIS.md`) | User dengan role rendah dapat memutuskan otorisasi web via API | Tambahkan `permission:` middleware pada endpoint mutasi/baca audit baru sebelum expose |
| **Otorisasi inline tanpa Policy** | Rentan terhadap lupa pemeriksaan pada route baru, mass assignment mengubah status finalized | Buat `Policy` per model audit, gunakan `$this->authorize()` di setiap controller, guard mass assignment |
| **Audit trail bukan append-only** | Riwayat bisa diubah/sisip, melanggar integritas audit | Batasi `AuditTrail` (hanya create, tidak ada update/delete di controller), pertimbangkan trigger DB atau soft-delete terpisah |
| **Collision penomoran kode (count+1)** | Error 500 saat kompetisi (M1 `AUDIT_TEKNIS.md`) | Gunakan DB sequence / transaksi + retry untuk `program_code`, `finding_code`, `schedule_code` |
| **Isolasi klinik lemah** (H1) | Klinik dapat membaca data klinik lain dalam satu organisasi | Terapkan scope clinic pada semua query audit (via model scope atau Policy), tidak hanya `organization_id` |
| **Separation of duties tidak terpaksa** | User memverifikasi action sendiri / author finding melakukan final verification | Paksa SoD di Policy + service layer, uji secara eksplisit |
| **Token Sanctum tanpa expired & ability** | Token hidup selamanya tanpa scope | Set `expiration`, gunakan abilities, pertimbangkan refresh token (Fase lanjutan) |
| **PostgreSQL vs MySQL** | Raw SQL / `FIELD()` / `MAX(uuid)` / double-quoted string pecah di PG | Hindari raw SQL, gunakan Query Builder / Eloquent, ikuti perbaikan `docs/DEPLOY_DOCKER.md` |
| **Migrasi destruktif** | Hilangnya data produksi saat rollback | Selalu additive, `down()` hanya drop tabel baru yang dibuat sendiri, jangan mengubah tabel yang ada selain kolom baru nullable |
| **Test DB = `.env` MySQL** | Gagal jika MySQL tidak nyala saat `php artisan test` | Pastikan MySQL (Laragon) running sebelum menjalankan test; jangan ubah `phpunit.xml` untuk sqlite |
| **Konten regulasi ditetapkan sebelum verifikasi** | Klaim kepatuhan yang tidak terjamin | Tandai semua instrumen `UNVERIFIED` / draft, jangan menyebut "disetujui Kemenkes" |

---

## 6. Rancangan Implementasi Disesuaikan dengan Repository

Pendekatan mengikuti **8 Gate** dari `IMPLEMENTATION_PLAN.md`, dengan sesuaian pada kondisi repo yang sudah ada. Semua perubahan **add-only**, tidak merusak migrasi/tabel/logika yang ada, dan dilakukan per vertical slice (database → authorization → service → controller → view → test → dokumentasi).

### 6.1 Langkah awal (sebelum kode)
1. `composer install` (vendor hilang), pastikan MySQL/Laragon atau PostgreSQL/Docker menyala.
2. Buat branch fitur, mis. `feature/audit-internal-gate0-baseline`.
3. Catat baseline test (`php artisan test`) dan simpan pre-existing failures (jika ada) — **tidak boleh disalahkan pada pekerjaan baru**.
4. Buat `docs/decisions/` untuk mencatat open decisions di atas.

### 6.2 Gate 1 — Produk & regulasi (non-kode utama)
- Verifikasi sumber resmi Kemenkes/JDIH (KMK HK.01.07/MENKES/1983/2022, Permenkes 34/2022). Tandai `UNVERIFIED` sampai dokumen resmi diperiksa.
- Konfirmasi hierarki approval, severity/due-date policy dengan pemilik produk.
- Buat decision log untuk opsi di atas.

### 6.3 Gate 2 — UX & arsitektur fondasi
- Rute dan navigasi untuk modul audit (program, assignment, execution, checklist, finding, verification, report, standard/instrument, audit trail).
- Update `SimmakMenu` (hanya menampilkan modul yang sudah diimplementasi).
- Design token dan komponen reusable (filter bar, data table, empty state, evidence uploader, workflow stepper, confirmation dialog) sesuai `UI_UX_SPEC.md`, menghindari emoji & kartu metrik generik.

### 6.4 Gate 3 — Foundation vertical slice
- **DB (add-only):** `regulatory_sources`, `audit_standards`, `instrument_versions`, `standard_sections`, `standard_criteria`, `audit_assignments`, `audit_team_members`, `audit_executions`, `checklist_responses`, `evidence`, `response_evidence`, `finding_evidence`, `action_evidence`, `approval_events`. Kolom tambahan pada `audit_findings` / `capa_cases` / `capa_actions` jika diperlukan (nullable, tidak menghapus kolom lama).
- **Model:** sesuai ERD, menggunakan `BaseModel` + `BelongsToOrganization`, UUID, soft delete.
- **RBAC:** tambahkan permission dari `PERMISSION_MATRIX.md` ke seeder, tambahkan role `reviewer`, `verifier` (jika disetujui), update `SimmakMenu`.
- **Policy:** buat `app/Policies` untuk setiap sumber daya audit (program, assignment, execution, finding, action, verification, evidence, standard).
- **Audit trail:** panggil `AuditTrailService::log()` pada setiap transisi workflow penting; batasi `AuditTrail` hanya create.
- **Test:** uji otorisasi + isolasi klinik untuk setiap endpoint baru.

### 6.5 Gate 4 — Perencanaan & penugasan audit
- Audit program: create/submit/approve, approved scope immutable (revision event), program-clinic scope.
- Audit assignment: team member, conflict-of-interest declaration, blokir aktivasi jika conflict belum teratasi.
- Calendar/list view, pagination, filter, sorting.

### 6.6 Gate 5 — Eksekusi & temuan
- Audit execution: instrument version snapshot (immutable setelah digunakan), status `preparation -> in_progress -> submitted_for_review -> finalized`.
- Checklist responses: response values, N/unable-to-assess with reason, completion hitung dari data tersimpan.
- Evidence privat: disk privat, nama acak, validasi MIME/size/extension, download terotorisasi.
- Findings: link ke eksekusi/klinik/criterion/evidence, risk, recommendation, owner, due date.
- Report review & finalization, self-approval blokir.

### 6.7 Gate 6 — CAPA, verifikasi, penutupan
- Action plan: PIC, due date, root cause, correction, corrective/preventive action, evidence.
- Independent verification: verifier tidak boleh memverifikasi action sendiri; finding author tidak boleh final verify temuan sendiri.
- Closure/reopen with reason + audit trail, overhead status dihitung dari tanggal/status (bukan flag hard-code).

### 6.8 Gate 7 — Reporting & dashboard
- Dashboard audit (real data): work queue, overdue actions, review queue, risk priority, drill-down ke sumber.
- PDF/Excel export dengan scope yang sama seperti UI, metadata laporan.
- Notifikasi in-app (email opsional jika disetujui).

### 6.9 Gate 8 — QA, keamanan, release readiness
- Unit, feature, workflow, authorization, upload, report, regression test.
- Ulasan responsif & aksesibilitas (desktop/tablet/mobile).
- Uji migrasi pada DB bersih dan upgrade path pada DB representatif.
- Dokumentasi setup, deployment, backup, restore.
- Catat known limitations & remaining decisions.

---

## 7. Daftar File yang Akan Dibuat / Diubah

### 7.1 File baru (migrasi)
1. `database/migrations/yyyy_mm_dd_hhmmss_create_regulatory_sources.php`
2. `database/migrations/yyyy_mm_dd_hhmmss_create_audit_standards.php`
3. `database/migrations/yyyy_mm_dd_hhmmss_create_instrument_versions.php`
4. `database/migrations/yyyy_mm_dd_hhmmss_create_standard_sections.php`
5. `database/migrations/yyyy_mm_dd_hhmmss_create_standard_criteria.php`
6. `database/migrations/yyyy_mm_dd_hhmmss_create_audit_assignments.php`
7. `database/migrations/yyyy_mm_dd_hhmmss_create_audit_team_members.php`
8. `database/migrations/yyyy_mm_dd_hhmmss_create_audit_executions.php`
9. `database/migrations/yyyy_mm_dd_hhmmss_create_checklist_responses.php`
10. `database/migrations/yyyy_mm_dd_hhmmss_create_evidence.php`
11. `database/migrations/yyyy_mm_dd_hhmmss_create_response_evidence.php`
12. `database/migrations/yyyy_mm_dd_hhmmss_create_finding_evidence.php`
13. `database/migrations/yyyy_mm_dd_hhmmss_create_action_evidence.php`
14. `database/migrations/yyyy_mm_dd_hhmmss_create_approval_events.php`

### 7.2 File baru (model)
15. `app/Models/RegulatorySource.php`
16. `app/Models/AuditStandard.php`
17. `app/Models/InstrumentVersion.php`
18. `app/Models/StandardSection.php`
19. `app/Models/StandardCriterion.php`
20. `app/Models/AuditAssignment.php`
21. `app/Models/AuditTeamMember.php`
22. `app/Models/AuditExecution.php`
23. `app/Models/ChecklistResponse.php`
24. `app/Models/Evidence.php`
25. `app/Models/ApprovalEvent.php`
26. `app/Models/Verification.php` (jika dipisah dari `capa_verifications`)

### 7.3 File baru (policy)
27. `app/Policies/AuditProgramPolicy.php`
28. `app/Policies/AuditAssignmentPolicy.php`
29. `app/Policies/AuditExecutionPolicy.php`
30. `app/Policies/ChecklistResponsePolicy.php`
31. `app/Policies/EvidencePolicy.php`
32. `app/Policies/AuditFindingPolicy.php` (perluas)
33. `app/Policies/CorrectiveActionPolicy.php` (perluas)
34. `app/Policies/VerificationPolicy.php`
35. `app/Policies/ApprovalEventPolicy.php`
36. `app/Policies/AuditTrailPolicy.php`

### 7.4 File baru (service)
37. `app/Services/AuditStandardService.php`
38. `app/Services/AuditProgramService.php`
39. `app/Services/AuditAssignmentService.php`
40. `app/Services/AuditExecutionService.php`
41. `app/Services/ChecklistResponseService.php`
42. `app/Services/EvidenceService.php`
43. `app/Services/AuditFindingService.php` (perluas)
44. `app/Services/AuditWorkflowService.php` (transition map + SoD)
45. `app/Services/AuditDashboardService.php` (perluas `DashboardService`)
46. `app/Services/AuditReportService.php`
47. `app/Services/AuditNotificationService.php`

### 7.5 File baru (controller)
48. `app/Http/Controllers/AuditProgramController.php`
49. `app/Http/Controllers/AuditStandardController.php`
50. `app/Http/Controllers/AuditAssignmentController.php`
51. `app/Http/Controllers/AuditExecutionController.php`
52. `app/Http/Controllers/ChecklistResponseController.php`
53. `app/Http/Controllers/AuditEvidenceController.php`
54. `app/Http/Controllers/AuditVerificationController.php`
55. `app/Http/Controllers/AuditApprovalController.php`
56. `app/Http/Controllers/AuditReportController.php`
57. `app/Http/Controllers/AuditDashboardController.php`

### 7.6 File baru (route)
58. Tambahan grup route di `routes/web.php` (program, standard, assignment, execution, checklist, evidence, verification, approval, report, audit trail)
59. Tambahan route di `routes/api.php` (dengan `permission:` middleware)

### 7.7 File baru (view)
60. `resources/views/audit/programs/index.blade.php`, `create.blade.php`, `show.blade.php`
61. `resources/views/audit/standards/index.blade.php`, `versions.blade.php`
62. `resources/views/audit/assignments/index.blade.php`, `show.blade.php`
63. `resources/views/audit/executions/show.blade.php`
64. `resources/views/audit/checklists/execute.blade.php`
65. `resources/views/audit/findings/create.blade.php`, `edit.blade.php`
66. `resources/views/audit/verifications/show.blade.php`
67. `resources/views/audit/dashboard/index.blade.php`
68. `resources/views/audit/trail/index.blade.php`
69. Komponen partial reusable: filter bar, data table, empty state, evidence uploader, workflow stepper, confirmation dialog

### 7.8 File baru (test)
70. `tests/Feature/Audit/...` (authorization, workflow, program, assignment, execution, checklist, evidence, finding, verification, report)
71. `tests/Unit/Audit/...` (service, workflow transition, SoD)

### 7.9 File yang diubah
- `database/seeders/DatabaseSeeder.php`: tambah permission baru + role `reviewer`, `verifier`.
- `database/seeders/RoleUsersSeeder.php`: tambah akun demo untuk role baru (jika disetujui).
- `app/Support/SimmakMenu.php`: tambah item menu audit internal (permission-gated).
- `app/Providers/AuthServiceProvider.php`: register policy.
- `config/permission.php`: (opsional) jika perlu penyesuaian.
- `routes/web.php`, `routes/api.php`: tambah grup route audit.
- `app/Http/Controllers/DashboardController.php` / `app/Services/DashboardService.php`: tambah metrik audit (add-only, tidak mengubah metrik yang ada).
- `resources/views/layouts/simmak.blade.php` / `partials/sidebar.blade.php`: navigasi audit internal.
- `resources/css/app.css`: komponen baru (jika diperlukan).

---

## 8. Catatan Integritas Regulasi (Kemenkes)

- Semua sumber hukum (KMK HK.01.07/MENKES/1983/2022, Permenkes 34/2022, dan turunan) masih berstatus **`UNVERIFIED`** dalam `REGULATORY_REGISTER.md`. Saya **tidak menyatakan** bahwa instrumen atau regulasi tersebut telah terverifikasi sebelum dokumen resmi diperiksa melalui JDIH Kemenkes / sumber resmi.
- Konten standar/instrument dalam R1 akan tetap sebagai **draft** dan ditandai `UNVERIFIED` sampai verifikasi resmi selesai.
- Keputusan tentang versi standar yang berlaku, opsi penilaian, bobot skoring, dan ambang kelulusan adalah keputusan PPKP, bukan asumsi perangkat lunak.

---

## 9. Langkah Selanjutnya (Direkomendasikan)

1. `composer install` dan jalankan `php artisan test` untuk mencatat baseline.
2. Buat branch fitur dan `docs/decisions/` untuk open decisions.
3. Mulai Gate 1 (verifikasi regulasi + decision log) dan Gate 2 (rute, menu, design token).
4. Implementasi Gate 3–8 secara bertahap, satu vertical slice sekaligus, dengan uji otorisasi dan regresi setiap slice.
5. Jangan menjalankan migrasi destruktif dan tidak menimpa perubahan pengguna yang ada.
