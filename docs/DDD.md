# Database Design Document (DDD) SIMMAK PPKP

**Versi Dokumen:** 1.0.0
**Tanggal:** 25 June 2026
**Platform Database:** PostgreSQL 17
**Rujukan ERD:** `../diagrams/erd.mmd`
**Cakupan:** desain database enterprise untuk Sistem Informasi Manajemen Mutu, Audit Internal, CAPA, Risiko, Akreditasi, DMS, Workflow, Notifikasi, dan Operasi Sistem PPKP Provinsi DKI Jakarta.

## 1. Ringkasan Eksekutif

SIMMAK PPKP dirancang sebagai sistem multi-klinik dengan satu basis data PostgreSQL 17 yang mengelola data mutu, audit internal, dokumen terkendali, CAPA, risiko, akreditasi, workflow, dan notifikasi. Desain database menempatkan `organization_id` sebagai tenant boundary utama, sedangkan `clinic_id` dan `unit_id` digunakan untuk membatasi data operasional sesuai konteks layanan.

Dokumen ini mendokumentasikan 104 tabel yang berasal dari ERD enterprise dan perluasan domain System. Semua tabel bisnis dijelaskan dalam bentuk data dictionary lengkap: nama kolom, tipe PostgreSQL, nullability, deskripsi, dan constraint. Strategi indeks, partisi, foreign key, soft delete, audit trail, serta contoh DDL disediakan sebagai acuan implementasi migration.

## 2. Prinsip Desain
- Multi-tenant by design: semua data bisnis membawa `organization_id` dan query aplikasi wajib memfilter tenant aktif.
- Scoped operation: data yang terjadi di klinik atau unit membawa `clinic_id` dan `unit_id` bila prosesnya relevan.
- Traceability: perubahan penting dicatat melalui `audit_trails`, `activity_logs`, status history, dan metadata workflow.
- Soft delete first: record bisnis tidak dihapus fisik kecuali data teknis yang aman dipurge sesuai retensi.
- Workflow configurable: rantai approval tidak di-hardcode, tetapi didefinisikan oleh tabel workflow.
- JSONB terkendali: JSONB dipakai untuk konfigurasi atau payload fleksibel, bukan untuk menggantikan kolom yang sering difilter.
- Referential integrity: FK dipasang pada relasi kuat, sedangkan polymorphic reference didukung audit dan validasi aplikasi.

## 3. Naming Convention
- Nama tabel memakai `snake_case` plural, misalnya `audit_findings` dan `document_versions`.
- Primary key selalu `id UUID` dengan default `gen_random_uuid()`.
- Foreign key mengikuti pola `<entity>_id`, misalnya `clinic_id`, `document_id`, `workflow_instance_id`.
- Timestamp audit memakai `created_at`, `updated_at`, `deleted_at` dengan tipe `TIMESTAMPTZ`.
- Kolom aktor memakai `created_by`, `updated_by`, `deleted_by`, atau nama aksi spesifik seperti `approved_by`.
- Unique key tenant-aware memakai pola indeks `ux_<table>_<scope>_<business_key>`.
- Index normal memakai pola `ix_<table>_<columns>`; index partial soft delete memakai suffix `_active`.
- Check constraint memakai pola `ck_<table>_<rule>`.

## 4. Tipe Data PostgreSQL 17
- `UUID`: Identifier utama dan foreign key agar aman untuk integrasi lintas sistem.
- `VARCHAR(n)`: Kode, status, slug, nomor dokumen, dan atribut pendek yang perlu dibatasi panjangnya.
- `TEXT`: Deskripsi panjang, ringkasan, catatan, dan konten dokumen.
- `JSONB`: Konfigurasi, metadata, payload event, response provider, dan schema variabel template.
- `TIMESTAMPTZ`: Semua timestamp agar konsisten lintas zona waktu dan audit.
- `DATE`: Tanggal jadwal, target, efektif, kedaluwarsa, dan periode bisnis.
- `NUMERIC(12,2)`: Skor, bobot, nilai target, pengukuran, dan estimasi biaya.
- `BOOLEAN`: Flag status sederhana seperti `is_active`, `is_default`, dan `is_enabled`.

## 5. Deskripsi ERD
ERD pada `../diagrams/erd.mmd` menggambarkan dependensi utama antar domain. Organisasi menjadi root bagi klinik, user, role, program audit, program akreditasi, program mutu, risiko, dokumen, workflow, dan notifikasi. Klinik menjadi scope utama untuk transaksi operasional seperti audit plan, CAPA, risk register, self assessment, evidence, indikator mutu, dan notifikasi.

Relasi besar yang perlu diperhatikan:
- `ORGANIZATIONS ||--o{ CLINICS : "operates"`
- `ORGANIZATIONS ||--o{ DEPARTMENTS : "has"`
- `ORGANIZATIONS ||--o{ EMPLOYEES : "employs"`
- `ORGANIZATIONS ||--o{ REFERENCE_DATA : "defines"`
- `ORGANIZATIONS ||--o{ FISCAL_YEARS : "plans"`
- `ORGANIZATIONS ||--o{ ORGANIZATION_SETTINGS : "configures"`
- `CLINICS ||--o{ UNITS : "contains"`
- `CLINICS ||--o{ CLINIC_SETTINGS : "configures"`
- `CLINICS ||--o{ EMPLOYEES : "assigns"`
- `DEPARTMENTS ||--o{ UNITS : "groups"`
- `DEPARTMENTS ||--o{ DEPARTMENTS : "parent_of"`
- `REFERENCE_DATA ||--o{ REFERENCE_DATA_VALUES : "has_values"`
- `EMPLOYEES ||--o| USERS : "linked_account"`
- `ORGANIZATIONS ||--o{ USERS : "has_users"`
- `ORGANIZATIONS ||--o{ ROLES : "defines_roles"`
- `ORGANIZATIONS ||--o{ LDAP_CONFIGURATIONS : "integrates"`
- `USERS ||--o{ USER_ROLES : "assigned"`
- `USERS ||--o{ USER_CLINIC_SCOPES : "scoped_to"`
- `USERS ||--o{ SESSIONS : "maintains"`
- `USERS ||--o{ LOGIN_ATTEMPTS : "attempts"`
- `USERS ||--o{ PERSONAL_ACCESS_TOKENS : "api_access"`
- `ROLES ||--o{ ROLE_PERMISSIONS : "grants"`
- `PERMISSIONS ||--o{ ROLE_PERMISSIONS : "granted_via"`
- `ROLES ||--o{ USER_ROLES : "applied"`
- `CLINICS ||--o{ USER_CLINIC_SCOPES : "accessible"`
- `CLINICS ||--o{ USER_ROLES : "scoped"`
- `LDAP_CONFIGURATIONS ||--o{ LDAP_SYNC_LOGS : "logs"`
- `USERS ||--o{ AUDIT_TRAILS : "performs"`
- `USERS ||--o{ ACTIVITY_LOGS : "generates"`
- `ORGANIZATIONS ||--o{ DOCUMENT_CATEGORIES : "classifies"`
- `ORGANIZATIONS ||--o{ DOCUMENT_TYPES : "defines"`
- `DOCUMENT_CATEGORIES ||--o{ DOCUMENT_TYPES : "categorizes"`
- `DOCUMENT_TYPES ||--o{ DOCUMENTS : "templates"`
- `CLINICS ||--o{ DOCUMENTS : "owns"`
- `DOCUMENTS ||--o{ DOCUMENT_VERSIONS : "versions"`
- `DOCUMENTS ||--o| DOCUMENT_VERSIONS : "current_version"`
- `DOCUMENT_VERSIONS ||--o{ DOCUMENT_REVIEWS : "reviewed"`
- `DOCUMENT_VERSIONS ||--o{ DOCUMENT_APPROVALS : "approved"`
- `DOCUMENTS ||--o{ DOCUMENT_PUBLICATIONS : "published"`
- `DOCUMENTS ||--o{ DOCUMENT_DISTRIBUTIONS : "distributed"`
- `DOCUMENTS ||--o{ DOCUMENT_ACKNOWLEDGMENTS : "acknowledged"`
- `DOCUMENTS ||--o| DOCUMENT_QR_CODES : "verified"`
- `DOCUMENTS ||--o{ DOCUMENT_EXPIRY_ALERTS : "monitors"`
- `DOCUMENTS ||--o{ DOCUMENT_ACCESS_LOGS : "tracked"`
- `USERS ||--o{ DOCUMENT_REVIEWS : "reviews"`
- `USERS ||--o{ DOCUMENT_APPROVALS : "approves"`
- `WORKFLOW_INSTANCES ||--o| DOCUMENTS : "governs"`
- `ORGANIZATIONS ||--o{ AUDIT_PROGRAMS : "plans"`
- `FISCAL_YEARS ||--o{ AUDIT_PROGRAMS : "within"`
- `AUDIT_PROGRAMS ||--o{ AUDIT_PLANS : "contains"`
- `CLINICS ||--o{ AUDIT_PLANS : "audited"`
- `AUDIT_PLANS ||--o{ AUDIT_SCHEDULES : "scheduled"`
- `AUDIT_SCHEDULES ||--o| AUDIT_TEAMS : "staffed"`
- `AUDIT_TEAMS ||--o{ AUDIT_TEAM_MEMBERS : "includes"`
- `AUDIT_PLANS ||--o{ AUDIT_CHECKLIST_ITEMS : "checklists"`
- `AUDIT_CHECKLIST_ITEMS ||--o{ AUDIT_CHECKLIST_RESPONSES : "answered"`
- `AUDIT_SCHEDULES ||--o{ AUDIT_CHECKLIST_RESPONSES : "during"`
- `AUDIT_SCHEDULES ||--o{ AUDIT_FINDINGS : "identifies"`
- `AUDIT_SCHEDULES ||--o| AUDIT_REPORTS : "reports"`
- `AUDIT_FINDINGS ||--o{ AUDIT_EVIDENCE : "supports"`
- `AUDIT_FINDINGS ||--o{ AUDIT_FOLLOW_UPS : "tracks"`
- `ATTACHMENTS ||--o{ AUDIT_EVIDENCE : "stores"`
- `ORGANIZATIONS ||--o{ CAPA_CASES : "manages"`
- `CLINICS ||--o{ CAPA_CASES : "owns"`
- `AUDIT_FINDINGS ||--o{ CAPA_CASES : "triggers"`
- `CAPA_CASES ||--o| CAPA_ROOT_CAUSE_ANALYSES : "analyzed"`
- `CAPA_ROOT_CAUSE_ANALYSES ||--o{ CAPA_FIVE_WHYS : "5why_chain"`
- `CAPA_ROOT_CAUSE_ANALYSES ||--o{ CAPA_FISHBONE_DIAGRAMS : "fishbone"`
- `CAPA_CASES ||--o{ CAPA_CORRECTIVE_ACTIONS : "corrects"`
- `CAPA_CASES ||--o{ CAPA_PREVENTIVE_ACTIONS : "prevents"`
- `CAPA_CASES ||--o{ CAPA_VERIFICATIONS : "verifies"`
- `CAPA_CASES ||--o{ CAPA_EFFECTIVENESS_REVIEWS : "reviews"`
- `CAPA_CASES ||--o{ CAPA_STATUS_HISTORIES : "audited"`
- `ORGANIZATIONS ||--o{ RISK_CATEGORIES : "classifies"`
- `ORGANIZATIONS ||--o{ RISK_REGISTERS : "maintains"`
- `ORGANIZATIONS ||--o{ RISK_MATRICES : "configures"`
- `CLINICS ||--o{ RISK_REGISTERS : "scoped"`
- `FISCAL_YEARS ||--o{ RISK_REGISTERS : "annual"`
- `RISK_REGISTERS ||--o{ RISK_ITEMS : "contains"`
- `RISK_CATEGORIES ||--o{ RISK_ITEMS : "categorizes"`

Relasi pada Mermaid menggunakan notasi `||--o{` untuk one-to-many, `||--o|` untuk one-to-zero-or-one, dan label setelah titik dua untuk menjelaskan makna hubungan. Detail FK fisik mengikuti data dictionary dan strategi FK pada dokumen ini.

## 6. Ringkasan Domain dan Tabel

### 6.1. Core Platform
Master organisasi, klinik, unit, pegawai, lampiran, referensi, dan konfigurasi tenant.

Jumlah tabel: 14
- `organizations`
- `clinics`
- `departments`
- `units`
- `employees`
- `reference_data`
- `reference_data_values`
- `fiscal_years`
- `system_settings`
- `organization_settings`
- `clinic_settings`
- `audit_trails`
- `activity_logs`
- `attachments`

### 6.2. Authentication & RBAC
Identitas pengguna, peran, izin, integrasi LDAP, sesi, token, dan cakupan akses klinik.

Jumlah tabel: 11
- `users`
- `roles`
- `permissions`
- `role_permissions`
- `user_roles`
- `user_clinic_scopes`
- `ldap_configurations`
- `ldap_sync_logs`
- `login_attempts`
- `sessions`
- `personal_access_tokens`

### 6.3. Document Management System
Dokumen mutu, versi, kategori, workflow, QR code, distribusi, acknowledgment, dan pemantauan kedaluwarsa.

Jumlah tabel: 12
- `document_categories`
- `document_types`
- `documents`
- `document_versions`
- `document_reviews`
- `document_approvals`
- `document_publications`
- `document_distributions`
- `document_acknowledgments`
- `document_qr_codes`
- `document_expiry_alerts`
- `document_access_logs`

### 6.4. Audit Internal
Program audit, rencana, jadwal, tim, checklist, temuan, bukti, laporan, dan tindak lanjut.

Jumlah tabel: 11
- `audit_programs`
- `audit_plans`
- `audit_schedules`
- `audit_teams`
- `audit_team_members`
- `audit_checklist_items`
- `audit_checklist_responses`
- `audit_findings`
- `audit_reports`
- `audit_evidence`
- `audit_follow_ups`

### 6.5. Corrective and Preventive Action
Kasus CAPA, RCA, 5 Why, fishbone, tindakan korektif, preventif, verifikasi, dan efektivitas.

Jumlah tabel: 9
- `capa_cases`
- `capa_root_cause_analyses`
- `capa_five_whys`
- `capa_fishbone_diagrams`
- `capa_corrective_actions`
- `capa_preventive_actions`
- `capa_verifications`
- `capa_effectiveness_reviews`
- `capa_status_histories`

### 6.6. Risk Management
Register risiko, item risiko, assessment, matriks, mitigasi, monitoring, dan review berkala.

Jumlah tabel: 8
- `risk_categories`
- `risk_registers`
- `risk_items`
- `risk_assessments`
- `risk_matrices`
- `risk_mitigation_plans`
- `risk_monitoring_logs`
- `risk_reviews`

### 6.7. Akreditasi
Tipe standar, bab, standar, kriteria, elemen penilaian, self assessment, gap, mock survey, dan evidence.

Jumlah tabel: 12
- `accreditation_types`
- `accreditation_chapters`
- `accreditation_standards`
- `accreditation_criteria`
- `accreditation_elements`
- `accreditation_programs`
- `accreditation_self_assessments`
- `accreditation_self_assessment_items`
- `accreditation_gap_analyses`
- `accreditation_gap_items`
- `accreditation_mock_surveys`
- `accreditation_evidence`

### 6.8. Manajemen Mutu
Program mutu, indikator, target, pengukuran, monitoring, evaluasi, dan pelaporan mutu.

Jumlah tabel: 7
- `quality_programs`
- `quality_indicators`
- `quality_indicator_targets`
- `quality_measurements`
- `quality_monitoring_sessions`
- `quality_evaluations`
- `quality_reports`

### 6.9. Workflow Engine
Definisi workflow, step, transisi, instance, assignment, approval, dan histori eksekusi.

Jumlah tabel: 8
- `workflow_definitions`
- `workflow_steps`
- `workflow_transitions`
- `workflow_instances`
- `workflow_instance_steps`
- `workflow_assignments`
- `workflow_approvals`
- `workflow_histories`

### 6.10. Notification
Channel, template, notifikasi, penerima, preferensi, dan log pengiriman.

Jumlah tabel: 6
- `notification_channels`
- `notification_templates`
- `notifications`
- `notification_recipients`
- `notification_preferences`
- `notification_logs`

### 6.11. System Operations
Audit trail, activity log, job queue, outbox, migration metadata, dan operasi platform.

Jumlah tabel: 6
- `jobs`
- `job_batches`
- `failed_jobs`
- `migrations_meta`
- `outbox_events`
- `integration_logs`

## 7. Data Dictionary Lengkap

### 7.1. Domain Core Platform
Master organisasi, klinik, unit, pegawai, lampiran, referensi, dan konfigurasi tenant.

#### 7.1.1. `organizations`
Master tenant organisasi PPKP sebagai akar kepemilikan seluruh data SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Core Platform. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel organizations berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: ORG code.
  - Constraint: Unique sesuai scope tenant
- Kolom 4: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: PPKP DKI Jakarta.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `org_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Central|clinic.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `address`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan address untuk entitas organizations pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `phone`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan phone untuk entitas organizations pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `email`
  - Tipe: `VARCHAR(320)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan email untuk entitas organizations pada domain Core Platform.
  - Constraint: Unique sesuai scope tenant
- Kolom 9: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas organizations pada domain Core Platform.
  - Constraint: Default true
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_organizations_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_organizations_code` unique pada `code` dengan scope tenant bila ada.
- `ux_organizations_email` unique pada `email` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.1.2. `clinics`
Master 17 klinik/layanan dalam organisasi yang menjadi scope utama operasional mutu.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Core Platform. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel clinics berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Klinik code.
  - Constraint: Unique sesuai scope tenant
- Kolom 4: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas clinics pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `clinic_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Utama|pratama.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `is_main_clinic`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is main clinic untuk entitas clinics pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `accreditation_status`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan accreditation status untuk entitas clinics pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas clinics pada domain Core Platform.
  - Constraint: Default true
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_clinics_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_clinics_code` unique pada `code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.1.3. `departments`
Tabel departments pada domain Core Platform untuk mendukung proses core platform SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Core Platform. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel departments berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `parent_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Self-ref hierarchy.
  - Constraint: Foreign key
  - Referensi: `parents(id)`
- Kolom 4: `code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan code untuk entitas departments pada domain Core Platform.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas departments pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `dept_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan dept type untuk entitas departments pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas departments pada domain Core Platform.
  - Constraint: Default true
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_departments_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_departments_code` unique pada `code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.1.4. `units`
Tabel units pada domain Core Platform untuk mendukung proses core platform SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Core Platform. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel units berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 4: `department_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan department id untuk entitas units pada domain Core Platform.
  - Constraint: Foreign key
  - Referensi: `departments(id)`
- Kolom 5: `code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan code untuk entitas units pada domain Core Platform.
  - Constraint: Unique sesuai scope tenant
- Kolom 6: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas units pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `unit_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Poli|lab|farmasi|etc.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas units pada domain Core Platform.
  - Constraint: Default true
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_units_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_units_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.
- `ux_units_code` unique pada `code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.1.5. `employees`
Tabel employees pada domain Core Platform untuk mendukung proses core platform SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Core Platform. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.
- `unit_id` membatasi data pada unit layanan ketika proses membutuhkan detail unit.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel employees berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 4: `unit_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Unit layanan terkait; digunakan bila proses memiliki cakupan unit operasional.
  - Constraint: Foreign key
  - Referensi: `units(id)`
- Kolom 5: `nip`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan nip untuk entitas employees pada domain Core Platform.
  - Constraint: Unique sesuai scope tenant
- Kolom 6: `full_name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan full name untuk entitas employees pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `email`
  - Tipe: `VARCHAR(320)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan email untuk entitas employees pada domain Core Platform.
  - Constraint: Unique sesuai scope tenant
- Kolom 8: `phone`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan phone untuk entitas employees pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `position_title`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan position title untuk entitas employees pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `hire_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan hire date untuk entitas employees pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 11: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas employees pada domain Core Platform.
  - Constraint: Default true
- Kolom 12: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 13: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 14: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 15: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 18: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 19: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_employees_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_employees_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.
- `ix_employees_unit_active` pada (`unit_id`, `deleted_at`) untuk query detail unit.
- `ux_employees_nip` unique pada `nip` dengan scope tenant bila ada.
- `ux_employees_email` unique pada `email` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.1.6. `reference_data`
Tabel reference_data pada domain Core Platform untuk mendukung proses core platform SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Core Platform. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel reference_data berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `category`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Finding_severity|risk_level|etc.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 4: `code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan code untuk entitas reference_data pada domain Core Platform.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `label`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan label untuk entitas reference_data pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 7: `sort_order`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan sort order untuk entitas reference_data pada domain Core Platform.
  - Constraint: Default 0
- Kolom 8: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas reference_data pada domain Core Platform.
  - Constraint: Default true
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update

**Indeks yang direkomendasikan:**
- `ix_reference_data_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_reference_data_code` unique pada `code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.1.7. `reference_data_values`
Tabel reference_data_values pada domain Core Platform untuk mendukung proses core platform SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Core Platform. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel reference_data_values berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `reference_data_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan reference data id untuk entitas reference_data_values pada domain Core Platform.
  - Constraint: Foreign key
  - Referensi: `reference_datas(id)`
- Kolom 4: `code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan code untuk entitas reference_data_values pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `label`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan label untuk entitas reference_data_values pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `color_hex`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan color hex untuk entitas reference_data_values pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `sort_order`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan sort order untuk entitas reference_data_values pada domain Core Platform.
  - Constraint: Default 0
- Kolom 8: `is_default`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is default untuk entitas reference_data_values pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_reference_data_values_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_reference_data_values_code` unique pada `code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.1.8. `fiscal_years`
Tabel fiscal_years pada domain Core Platform untuk mendukung proses core platform SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Core Platform. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel fiscal_years berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `year`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan year untuk entitas fiscal_years pada domain Core Platform.
  - Constraint: Unique sesuai scope tenant
- Kolom 4: `start_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan start date untuk entitas fiscal_years pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `end_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan end date untuk entitas fiscal_years pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `is_current`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is current untuk entitas fiscal_years pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Open|closed.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_fiscal_years_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_fiscal_years_year` unique pada `year` dengan scope tenant bila ada.
- `ix_fiscal_years_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.1.9. `system_settings`
Tabel system_settings pada domain Core Platform untuk mendukung proses core platform SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Core Platform. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel system_settings berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `group`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: App|mail|storage|ldap.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 4: `key`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan key untuk entitas system_settings pada domain Core Platform.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `value`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan value untuk entitas system_settings pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `value_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: String|int|bool|json.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `is_encrypted`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is encrypted untuk entitas system_settings pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `description`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan description untuk entitas system_settings pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_system_settings_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_system_settings_key` unique pada `key` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.1.10. `organization_settings`
Tabel organization_settings pada domain Core Platform untuk mendukung proses core platform SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Core Platform. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel organization_settings berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `key`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan key untuk entitas organization_settings pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 4: `value`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan value untuk entitas organization_settings pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `value_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan value type untuk entitas organization_settings pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 7: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 8: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 9: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 10: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 13: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_organization_settings_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.1.11. `clinic_settings`
Tabel clinic_settings pada domain Core Platform untuk mendukung proses core platform SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Core Platform. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel clinic_settings berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 4: `key`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan key untuk entitas clinic_settings pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `value`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan value untuk entitas clinic_settings pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `value_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan value type untuk entitas clinic_settings pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 8: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 9: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 10: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 14: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_clinic_settings_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_clinic_settings_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.1.12. `audit_trails`
Jejak perubahan data granular yang menyimpan nilai lama dan baru untuk kebutuhan kepatuhan.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Core Platform. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel audit_trails berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `user_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan user id untuk entitas audit_trails pada domain Core Platform.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 4: `auditable_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan auditable type untuk entitas audit_trails pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `auditable_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan auditable id untuk entitas audit_trails pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
  - Referensi: `auditables(id)`
- Kolom 6: `event`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Created|updated|deleted|restored.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `old_values`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan old values untuk entitas audit_trails pada domain Core Platform.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 8: `new_values`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan new values untuk entitas audit_trails pada domain Core Platform.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 9: `ip_address`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan ip address untuk entitas audit_trails pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_audit_trails_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.1.13. `activity_logs`
Tabel activity_logs pada domain Core Platform untuk mendukung proses core platform SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Core Platform. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel activity_logs berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `user_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan user id untuk entitas activity_logs pada domain Core Platform.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 4: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 5: `log_name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan log name untuk entitas activity_logs pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `subject_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan subject type untuk entitas activity_logs pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `subject_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan subject id untuk entitas activity_logs pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
  - Referensi: `subjects(id)`
- Kolom 8: `description`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan description untuk entitas activity_logs pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `properties`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan properties untuk entitas activity_logs pada domain Core Platform.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 10: `ip_address`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan ip address untuk entitas activity_logs pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 11: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 12: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 13: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 14: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 18: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_activity_logs_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_activity_logs_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.1.14. `attachments`
Tabel attachments pada domain Core Platform untuk mendukung proses core platform SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Core Platform. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel attachments berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `attachable_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Polymorphic.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 4: `attachable_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan attachable id untuk entitas attachments pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
  - Referensi: `attachables(id)`
- Kolom 5: `file_name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan file name untuk entitas attachments pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `storage_disk`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Supabase|local.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `storage_path`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan storage path untuk entitas attachments pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `mime_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan mime type untuk entitas attachments pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `file_size`
  - Tipe: `BIGINT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan file size untuk entitas attachments pada domain Core Platform.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `uploaded_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan uploaded by untuk entitas attachments pada domain Core Platform.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 12: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 13: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 14: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 18: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_attachments_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

### 7.2. Domain Authentication & RBAC
Identitas pengguna, peran, izin, integrasi LDAP, sesi, token, dan cakupan akses klinik.

#### 7.2.1. `users`
Akun pengguna aplikasi, baik autentikasi lokal, LDAP, maupun hybrid.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Authentication & RBAC. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel users berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `employee_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Menyimpan employee id untuk entitas users pada domain Authentication & RBAC.
  - Constraint: Foreign key
  - Referensi: `employees(id)`
- Kolom 4: `email`
  - Tipe: `VARCHAR(320)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan email untuk entitas users pada domain Authentication & RBAC.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `password`
  - Tipe: `VARCHAR(255)`
  - Nullable: Ya
  - Deskripsi: Nullable for LDAP-only.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `ldap_guid`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan ldap guid untuk entitas users pada domain Authentication & RBAC.
  - Constraint: Unique sesuai scope tenant
- Kolom 7: `auth_provider`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Local|ldap|hybrid.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas users pada domain Authentication & RBAC.
  - Constraint: Default true
- Kolom 9: `force_password_change`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan force password change untuk entitas users pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `email_verified_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan email verified at untuk entitas users pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 11: `last_login_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan last login at untuk entitas users pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 12: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 13: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 14: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 15: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 18: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 19: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_users_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_users_email` unique pada `email` dengan scope tenant bila ada.
- `ux_users_ldap_guid` unique pada `ldap_guid` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.2.2. `roles`
Tabel roles pada domain Authentication & RBAC untuk mendukung proses authentication & rbac SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Authentication & RBAC. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel roles berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas roles pada domain Authentication & RBAC.
  - Constraint: Unique sesuai scope tenant
- Kolom 4: `slug`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan slug untuk entitas roles pada domain Authentication & RBAC.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `description`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan description untuk entitas roles pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `scope`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Global|clinic|unit.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `is_system`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is system untuk entitas roles pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas roles pada domain Authentication & RBAC.
  - Constraint: Default true
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_roles_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_roles_name` unique pada `name` dengan scope tenant bila ada.
- `ux_roles_slug` unique pada `slug` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.2.3. `permissions`
Tabel permissions pada domain Authentication & RBAC untuk mendukung proses authentication & rbac SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Authentication & RBAC. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- Tabel bersifat referensi global atau operasional sistem; bila dipakai untuk tenant tertentu, relasi tenant berada pada tabel turunan/transaksi.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel permissions berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `module`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Dms|audit|capa|risk|accreditation|quality.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 3: `action`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: View|create|update|delete|approve|publish.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 4: `slug`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan slug untuk entitas permissions pada domain Authentication & RBAC.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas permissions pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `description`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan description untuk entitas permissions pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 8: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 9: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 10: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 14: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ux_permissions_slug` unique pada `slug` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.2.4. `role_permissions`
Tabel role_permissions pada domain Authentication & RBAC untuk mendukung proses authentication & rbac SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Authentication & RBAC. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `role_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan role id untuk entitas role_permissions pada domain Authentication & RBAC.
  - Constraint: Primary key; Foreign key
  - Referensi: `roles(id)`
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `permission_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan permission id untuk entitas role_permissions pada domain Authentication & RBAC.
  - Constraint: Primary key; Foreign key
  - Referensi: `permissions(id)`
- Kolom 4: `granted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan granted at untuk entitas role_permissions pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain

**Indeks yang direkomendasikan:**
- `ix_role_permissions_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.2.5. `user_roles`
Tabel user_roles pada domain Authentication & RBAC untuk mendukung proses authentication & rbac SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Authentication & RBAC. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.
- `unit_id` membatasi data pada unit layanan ketika proses membutuhkan detail unit.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel user_roles berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `user_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan user id untuk entitas user_roles pada domain Authentication & RBAC.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 4: `role_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan role id untuk entitas user_roles pada domain Authentication & RBAC.
  - Constraint: Foreign key
  - Referensi: `roles(id)`
- Kolom 5: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 6: `unit_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Unit layanan terkait; digunakan bila proses memiliki cakupan unit operasional.
  - Constraint: Foreign key
  - Referensi: `units(id)`
- Kolom 7: `assigned_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan assigned at untuk entitas user_roles pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `assigned_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan assigned by untuk entitas user_roles pada domain Authentication & RBAC.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_user_roles_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_user_roles_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.
- `ix_user_roles_unit_active` pada (`unit_id`, `deleted_at`) untuk query detail unit.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.2.6. `user_clinic_scopes`
Tabel user_clinic_scopes pada domain Authentication & RBAC untuk mendukung proses authentication & rbac SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Authentication & RBAC. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel user_clinic_scopes berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `user_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan user id untuk entitas user_clinic_scopes pada domain Authentication & RBAC.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 4: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 5: `is_default`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is default untuk entitas user_clinic_scopes pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `can_view`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan can view untuk entitas user_clinic_scopes pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `can_edit`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan can edit untuk entitas user_clinic_scopes pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_user_clinic_scopes_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_user_clinic_scopes_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.2.7. `ldap_configurations`
Tabel ldap_configurations pada domain Authentication & RBAC untuk mendukung proses authentication & rbac SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Authentication & RBAC. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel ldap_configurations berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `host`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan host untuk entitas ldap_configurations pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 4: `port`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan port untuk entitas ldap_configurations pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `base_dn`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan base dn untuk entitas ldap_configurations pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `bind_dn`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan bind dn untuk entitas ldap_configurations pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `bind_password_enc`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan bind password enc untuk entitas ldap_configurations pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `user_filter`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan user filter untuk entitas ldap_configurations pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `group_filter`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan group filter untuk entitas ldap_configurations pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `use_tls`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan use tls untuk entitas ldap_configurations pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 11: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas ldap_configurations pada domain Authentication & RBAC.
  - Constraint: Default true
- Kolom 12: `last_sync_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan last sync at untuk entitas ldap_configurations pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 13: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 14: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 15: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 16: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 18: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 19: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 20: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_ldap_configurations_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.2.8. `ldap_sync_logs`
Tabel ldap_sync_logs pada domain Authentication & RBAC untuk mendukung proses authentication & rbac SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Authentication & RBAC. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel ldap_sync_logs berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `ldap_configuration_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan ldap configuration id untuk entitas ldap_sync_logs pada domain Authentication & RBAC.
  - Constraint: Foreign key
  - Referensi: `ldap_configurations(id)`
- Kolom 4: `sync_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Full|incremental.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Running|success|failed.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `users_created`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan users created untuk entitas ldap_sync_logs pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `users_updated`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan users updated untuk entitas ldap_sync_logs pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `users_deactivated`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan users deactivated untuk entitas ldap_sync_logs pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `error_message`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan error message untuk entitas ldap_sync_logs pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `started_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan started at untuk entitas ldap_sync_logs pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 11: `finished_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan finished at untuk entitas ldap_sync_logs pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 12: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 13: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 14: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 15: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 18: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 19: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_ldap_sync_logs_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_ldap_sync_logs_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.2.9. `login_attempts`
Tabel login_attempts pada domain Authentication & RBAC untuk mendukung proses authentication & rbac SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Authentication & RBAC. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel login_attempts berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `email`
  - Tipe: `VARCHAR(320)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan email untuk entitas login_attempts pada domain Authentication & RBAC.
  - Constraint: Unique sesuai scope tenant
- Kolom 4: `ip_address`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan ip address untuk entitas login_attempts pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `user_agent`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan user agent untuk entitas login_attempts pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `successful`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan successful untuk entitas login_attempts pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `failure_reason`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan failure reason untuk entitas login_attempts pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `attempted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan attempted at untuk entitas login_attempts pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain

**Indeks yang direkomendasikan:**
- `ix_login_attempts_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_login_attempts_email` unique pada `email` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.2.10. `sessions`
Tabel sessions pada domain Authentication & RBAC untuk mendukung proses authentication & rbac SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Authentication & RBAC. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel sessions berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `user_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan user id untuk entitas sessions pada domain Authentication & RBAC.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 4: `ip_address`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan ip address untuk entitas sessions pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `user_agent`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan user agent untuk entitas sessions pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `payload`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan payload untuk entitas sessions pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `last_activity`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan last activity untuk entitas sessions pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain

**Indeks yang direkomendasikan:**
- `ix_sessions_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.2.11. `personal_access_tokens`
Tabel personal_access_tokens pada domain Authentication & RBAC untuk mendukung proses authentication & rbac SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Authentication & RBAC. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel personal_access_tokens berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `user_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan user id untuk entitas personal_access_tokens pada domain Authentication & RBAC.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 4: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas personal_access_tokens pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `token`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan token untuk entitas personal_access_tokens pada domain Authentication & RBAC.
  - Constraint: Unique sesuai scope tenant
- Kolom 6: `abilities`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan abilities untuk entitas personal_access_tokens pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `last_used_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan last used at untuk entitas personal_access_tokens pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `expires_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan expires at untuk entitas personal_access_tokens pada domain Authentication & RBAC.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_personal_access_tokens_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_personal_access_tokens_token` unique pada `token` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

### 7.3. Domain Document Management System
Dokumen mutu, versi, kategori, workflow, QR code, distribusi, acknowledgment, dan pemantauan kedaluwarsa.

#### 7.3.1. `document_categories`
Tabel document_categories pada domain Document Management System untuk mendukung proses document management system SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Document Management System. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel document_categories berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `parent_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Menyimpan parent id untuk entitas document_categories pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `parents(id)`
- Kolom 4: `code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan code untuk entitas document_categories pada domain Document Management System.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas document_categories pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `doc_class`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Kebijakan|sop|pedoman|formulir|ik.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `retention_days`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan retention days untuk entitas document_categories pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas document_categories pada domain Document Management System.
  - Constraint: Default true
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_document_categories_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_document_categories_code` unique pada `code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.3.2. `document_types`
Tabel document_types pada domain Document Management System untuk mendukung proses document management system SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Document Management System. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel document_types berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `category_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan category id untuk entitas document_types pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `categories(id)`
- Kolom 4: `code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan code untuk entitas document_types pada domain Document Management System.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas document_types pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `workflow_definition_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan workflow definition id untuk entitas document_types pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `workflow_definitions(id)`
- Kolom 7: `review_cycle_days`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan review cycle days untuk entitas document_types pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `requires_acknowledgment`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan requires acknowledgment untuk entitas document_types pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas document_types pada domain Document Management System.
  - Constraint: Default true
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_document_types_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_document_types_code` unique pada `code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.3.3. `documents`
Header dokumen mutu yang mengikat nomor, status, owner, versi aktif, workflow, dan masa berlaku.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Document Management System. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel documents berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 4: `document_type_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan document type id untuk entitas documents pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `document_types(id)`
- Kolom 5: `workflow_instance_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Menyimpan workflow instance id untuk entitas documents pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `workflow_instances(id)`
- Kolom 6: `document_number`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan document number untuk entitas documents pada domain Document Management System.
  - Constraint: Unique sesuai scope tenant
- Kolom 7: `title`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan title untuk entitas documents pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Draft|review|approved|published|expired|archived.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `effective_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan effective date untuk entitas documents pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `expiry_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan expiry date untuk entitas documents pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 11: `owner_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan owner id untuk entitas documents pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `current_version_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Menyimpan current version id untuk entitas documents pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `current_versions(id)`
- Kolom 13: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 14: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 15: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 16: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 18: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 19: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 20: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_documents_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_documents_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.
- `ux_documents_document_number` unique pada `document_number` dengan scope tenant bila ada.
- `ix_documents_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.3.4. `document_versions`
Tabel document_versions pada domain Document Management System untuk mendukung proses document management system SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Document Management System. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel document_versions berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `document_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan document id untuk entitas document_versions pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `documents(id)`
- Kolom 4: `version_label`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: V1.0|v1.1.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `version_number`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan version number untuk entitas document_versions pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `change_summary`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan change summary untuk entitas document_versions pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `content_html`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan content html untuk entitas document_versions pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `file_path`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Supabase Storage.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `checksum_sha256`
  - Tipe: `CHAR(64)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan checksum sha256 untuk entitas document_versions pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 12: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 13: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_document_versions_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.3.5. `document_reviews`
Tabel document_reviews pada domain Document Management System untuk mendukung proses document management system SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Document Management System. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel document_reviews berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `document_version_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan document version id untuk entitas document_reviews pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `document_versions(id)`
- Kolom 4: `reviewer_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan reviewer id untuk entitas document_reviews pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 5: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Pending|approved|rejected|revision.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `comments`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan comments untuk entitas document_reviews pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `reviewed_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan reviewed at untuk entitas document_reviews pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_document_reviews_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_document_reviews_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.3.6. `document_approvals`
Tabel document_approvals pada domain Document Management System untuk mendukung proses document management system SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Document Management System. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel document_approvals berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `document_version_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan document version id untuk entitas document_approvals pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `document_versions(id)`
- Kolom 4: `approver_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan approver id untuk entitas document_approvals pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 5: `approval_level`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Reviewer|pj_mutu|kepala_klinik.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Pending|approved|rejected.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `comments`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan comments untuk entitas document_approvals pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `approved_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan approved at untuk entitas document_approvals pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_document_approvals_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_document_approvals_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.3.7. `document_publications`
Tabel document_publications pada domain Document Management System untuk mendukung proses document management system SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Document Management System. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel document_publications berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `document_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan document id untuk entitas document_publications pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `documents(id)`
- Kolom 4: `document_version_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan document version id untuk entitas document_publications pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `document_versions(id)`
- Kolom 5: `published_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan published by untuk entitas document_publications pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 6: `published_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan published at untuk entitas document_publications pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `effective_from`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan effective from untuk entitas document_publications pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `publication_note`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan publication note untuk entitas document_publications pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_document_publications_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.3.8. `document_distributions`
Tabel document_distributions pada domain Document Management System untuk mendukung proses document management system SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Document Management System. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel document_distributions berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `document_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan document id untuk entitas document_distributions pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `documents(id)`
- Kolom 4: `target_clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 5: `distributed_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan distributed by untuk entitas document_distributions pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 6: `distributed_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan distributed at untuk entitas document_distributions pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `distribution_method`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: System|email|print.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_document_distributions_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.3.9. `document_acknowledgments`
Tabel document_acknowledgments pada domain Document Management System untuk mendukung proses document management system SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Document Management System. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel document_acknowledgments berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `document_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan document id untuk entitas document_acknowledgments pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `documents(id)`
- Kolom 4: `user_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan user id untuk entitas document_acknowledgments pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 5: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 6: `acknowledged_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan acknowledged at untuk entitas document_acknowledgments pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `ip_address`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan ip address untuk entitas document_acknowledgments pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_document_acknowledgments_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_document_acknowledgments_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.3.10. `document_qr_codes`
Tabel document_qr_codes pada domain Document Management System untuk mendukung proses document management system SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Document Management System. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel document_qr_codes berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `document_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan document id untuk entitas document_qr_codes pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `documents(id)`
- Kolom 4: `qr_token`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan qr token untuk entitas document_qr_codes pada domain Document Management System.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `verification_url`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan verification url untuk entitas document_qr_codes pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `qr_image_path`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan qr image path untuk entitas document_qr_codes pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas document_qr_codes pada domain Document Management System.
  - Constraint: Default true
- Kolom 8: `generated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan generated at untuk entitas document_qr_codes pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_document_qr_codes_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_document_qr_codes_qr_token` unique pada `qr_token` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.3.11. `document_expiry_alerts`
Tabel document_expiry_alerts pada domain Document Management System untuk mendukung proses document management system SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Document Management System. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel document_expiry_alerts berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `document_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan document id untuk entitas document_expiry_alerts pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `documents(id)`
- Kolom 4: `days_before_expiry`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan days before expiry untuk entitas document_expiry_alerts pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `alert_status`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Pending|sent|acknowledged.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `alert_sent_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan alert sent at untuk entitas document_expiry_alerts pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `notified_user_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan notified user id untuk entitas document_expiry_alerts pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `notified_users(id)`
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_document_expiry_alerts_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.3.12. `document_access_logs`
Tabel document_access_logs pada domain Document Management System untuk mendukung proses document management system SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Document Management System. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel document_access_logs berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `document_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan document id untuk entitas document_access_logs pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `documents(id)`
- Kolom 4: `user_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan user id untuk entitas document_access_logs pada domain Document Management System.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 5: `action`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: View|download|print.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `ip_address`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan ip address untuk entitas document_access_logs pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `accessed_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan accessed at untuk entitas document_access_logs pada domain Document Management System.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_document_access_logs_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

### 7.4. Domain Audit Internal
Program audit, rencana, jadwal, tim, checklist, temuan, bukti, laporan, dan tindak lanjut.

#### 7.4.1. `audit_programs`
Tabel audit_programs pada domain Audit Internal untuk mendukung proses audit internal SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Audit Internal. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel audit_programs berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `fiscal_year_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan fiscal year id untuk entitas audit_programs pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `fiscal_years(id)`
- Kolom 4: `program_code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan program code untuk entitas audit_programs pada domain Audit Internal.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas audit_programs pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `objective`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan objective untuk entitas audit_programs pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Draft|active|completed|archived.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_audit_programs_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_audit_programs_program_code` unique pada `program_code` dengan scope tenant bila ada.
- `ix_audit_programs_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.4.2. `audit_plans`
Tabel audit_plans pada domain Audit Internal untuk mendukung proses audit internal SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Audit Internal. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.
- `unit_id` membatasi data pada unit layanan ketika proses membutuhkan detail unit.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel audit_plans berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `audit_program_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan audit program id untuk entitas audit_plans pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `audit_programs(id)`
- Kolom 4: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 5: `unit_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Unit layanan terkait; digunakan bila proses memiliki cakupan unit operasional.
  - Constraint: Foreign key
  - Referensi: `units(id)`
- Kolom 6: `plan_code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan plan code untuk entitas audit_plans pada domain Audit Internal.
  - Constraint: Unique sesuai scope tenant
- Kolom 7: `scope`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan scope untuk entitas audit_plans pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `criteria_reference`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan criteria reference untuk entitas audit_plans pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Planned|in_progress|completed.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `lead_auditor_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan lead auditor id untuk entitas audit_plans pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 12: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 13: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 14: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 18: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_audit_plans_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_audit_plans_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.
- `ix_audit_plans_unit_active` pada (`unit_id`, `deleted_at`) untuk query detail unit.
- `ux_audit_plans_plan_code` unique pada `plan_code` dengan scope tenant bila ada.
- `ix_audit_plans_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.4.3. `audit_schedules`
Tabel audit_schedules pada domain Audit Internal untuk mendukung proses audit internal SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Audit Internal. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel audit_schedules berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `audit_plan_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan audit plan id untuk entitas audit_schedules pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `audit_plans(id)`
- Kolom 4: `scheduled_start`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan scheduled start untuk entitas audit_schedules pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `scheduled_end`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan scheduled end untuk entitas audit_schedules pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `actual_start`
  - Tipe: `DATE`
  - Nullable: Ya
  - Deskripsi: Menyimpan actual start untuk entitas audit_schedules pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `actual_end`
  - Tipe: `DATE`
  - Nullable: Ya
  - Deskripsi: Menyimpan actual end untuk entitas audit_schedules pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Scheduled|ongoing|completed|cancelled.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `location`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan location untuk entitas audit_schedules pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_audit_schedules_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_audit_schedules_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.4.4. `audit_teams`
Tabel audit_teams pada domain Audit Internal untuk mendukung proses audit internal SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Audit Internal. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel audit_teams berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `audit_schedule_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan audit schedule id untuk entitas audit_teams pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `audit_schedules(id)`
- Kolom 4: `lead_auditor_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan lead auditor id untuk entitas audit_teams pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 5: `team_notes`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan team notes untuk entitas audit_teams pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 7: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 8: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 9: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 10: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 13: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_audit_teams_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.4.5. `audit_team_members`
Tabel audit_team_members pada domain Audit Internal untuk mendukung proses audit internal SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Audit Internal. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel audit_team_members berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `audit_team_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan audit team id untuk entitas audit_team_members pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `audit_teams(id)`
- Kolom 4: `user_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan user id untuk entitas audit_team_members pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 5: `member_role`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Auditor|observer|technical.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 7: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 8: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 9: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 10: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 13: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_audit_team_members_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.4.6. `audit_checklist_items`
Tabel audit_checklist_items pada domain Audit Internal untuk mendukung proses audit internal SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Audit Internal. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel audit_checklist_items berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `audit_plan_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan audit plan id untuk entitas audit_checklist_items pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `audit_plans(id)`
- Kolom 4: `standard_reference`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan standard reference untuk entitas audit_checklist_items pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `question`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan question untuk entitas audit_checklist_items pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `response_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Yes_no|score|text.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `weight`
  - Tipe: `NUMERIC(12,2)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan weight untuk entitas audit_checklist_items pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `sort_order`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan sort order untuk entitas audit_checklist_items pada domain Audit Internal.
  - Constraint: Default 0
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_audit_checklist_items_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.4.7. `audit_checklist_responses`
Tabel audit_checklist_responses pada domain Audit Internal untuk mendukung proses audit internal SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Audit Internal. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel audit_checklist_responses berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `checklist_item_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan checklist item id untuk entitas audit_checklist_responses pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `checklist_items(id)`
- Kolom 4: `audit_schedule_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan audit schedule id untuk entitas audit_checklist_responses pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `audit_schedules(id)`
- Kolom 5: `response_value`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan response value untuk entitas audit_checklist_responses pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `score`
  - Tipe: `NUMERIC(12,2)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan score untuk entitas audit_checklist_responses pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `notes`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan notes untuk entitas audit_checklist_responses pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `responded_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan responded by untuk entitas audit_checklist_responses pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 9: `responded_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan responded at untuk entitas audit_checklist_responses pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_audit_checklist_responses_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.4.8. `audit_findings`
Temuan audit internal yang menjadi sumber CAPA, follow-up, dan pelaporan kinerja audit.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Audit Internal. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel audit_findings berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `audit_schedule_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan audit schedule id untuk entitas audit_findings pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `audit_schedules(id)`
- Kolom 4: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 5: `finding_code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan finding code untuk entitas audit_findings pada domain Audit Internal.
  - Constraint: Unique sesuai scope tenant
- Kolom 6: `severity`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Minor|major|critical|observation.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `description`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan description untuk entitas audit_findings pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `requirement_reference`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan requirement reference untuk entitas audit_findings pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Open|in_capa|closed|verified.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `assigned_to`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan assigned to untuk entitas audit_findings pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `identified_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan identified at untuk entitas audit_findings pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 12: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 13: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 14: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 15: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 18: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 19: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_audit_findings_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_audit_findings_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.
- `ux_audit_findings_finding_code` unique pada `finding_code` dengan scope tenant bila ada.
- `ix_audit_findings_severity` pada (`severity`) untuk antrian kerja, filter laporan, dan monitoring SLA.
- `ix_audit_findings_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.4.9. `audit_reports`
Tabel audit_reports pada domain Audit Internal untuk mendukung proses audit internal SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Audit Internal. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Catatan skema aplikasi:**
Tabel lama tetap dipertahankan dan diperluas secara aditif oleh migrasi `2026_10_19_160001_extend_audit_reports_for_reporting`. Kolom laporan yang dibutuhkan aplikasi saat ini mencakup `title`, `report_type`, `audit_execution_id`, `clinic_id`, `generated_by`, `generated_at`, metadata ringkasan, dan metadata file. Untuk laporan lama, judul diisi dari `report_number`, tipe diisi `summary`, dan `generated_at` diisi dari `created_at`. Kolom `audit_schedule_id` dan `report_date` lama tetap dipertahankan dan dibuat nullable agar pembuatan laporan baru tidak bergantung pada skema lama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel audit_reports berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `audit_schedule_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan audit schedule id untuk entitas audit_reports pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `audit_schedules(id)`
- Kolom 4: `report_number`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan report number untuk entitas audit_reports pada domain Audit Internal.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `executive_summary`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan executive summary untuk entitas audit_reports pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `conclusion`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan conclusion untuk entitas audit_reports pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Draft|review|approved|published.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `approved_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan approved by untuk entitas audit_reports pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 9: `approved_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan approved at untuk entitas audit_reports pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_audit_reports_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_audit_reports_report_number` unique pada `report_number` dengan scope tenant bila ada.
- `ix_audit_reports_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.4.10. `audit_evidence`
Tabel audit_evidence pada domain Audit Internal untuk mendukung proses audit internal SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Audit Internal. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel audit_evidence berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `audit_finding_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan audit finding id untuk entitas audit_evidence pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `audit_findings(id)`
- Kolom 4: `attachment_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Menyimpan attachment id untuk entitas audit_evidence pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `attachments(id)`
- Kolom 5: `description`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan description untuk entitas audit_evidence pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `evidence_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Photo|document|interview.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `collected_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan collected by untuk entitas audit_evidence pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 8: `collected_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan collected at untuk entitas audit_evidence pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_audit_evidence_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.4.11. `audit_follow_ups`
Tabel audit_follow_ups pada domain Audit Internal untuk mendukung proses audit internal SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Audit Internal. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel audit_follow_ups berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `audit_finding_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan audit finding id untuk entitas audit_follow_ups pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `audit_findings(id)`
- Kolom 4: `due_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan due date untuk entitas audit_follow_ups pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Pending|overdue|completed.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `assigned_to`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan assigned to untuk entitas audit_follow_ups pada domain Audit Internal.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 7: `follow_up_notes`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan follow up notes untuk entitas audit_follow_ups pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `completed_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan completed at untuk entitas audit_follow_ups pada domain Audit Internal.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_audit_follow_ups_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_audit_follow_ups_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

### 7.5. Domain Corrective and Preventive Action
Kasus CAPA, RCA, 5 Why, fishbone, tindakan korektif, preventif, verifikasi, dan efektivitas.

#### 7.5.1. `capa_cases`
Kasus CAPA sebagai wadah analisis akar masalah, tindakan, verifikasi, dan review efektivitas.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Corrective and Preventive Action. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel capa_cases berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 4: `case_number`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan case number untuk entitas capa_cases pada domain Corrective and Preventive Action.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `source_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Audit_finding|risk|complaint|incident.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `source_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Polymorphic.
  - Constraint: Validasi aplikasi dan constraint domain
  - Referensi: `sources(id)`
- Kolom 7: `priority`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Low|medium|high|critical.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `problem_statement`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan problem statement untuk entitas capa_cases pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Open|analysis|action|verification|closed.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `owner_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan owner id untuk entitas capa_cases pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `target_close_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan target close date untuk entitas capa_cases pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 12: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 13: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 14: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 15: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 18: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 19: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_capa_cases_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_capa_cases_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.
- `ux_capa_cases_case_number` unique pada `case_number` dengan scope tenant bila ada.
- `ix_capa_cases_priority` pada (`priority`) untuk antrian kerja, filter laporan, dan monitoring SLA.
- `ix_capa_cases_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.5.2. `capa_root_cause_analyses`
Tabel capa_root_cause_analyses pada domain Corrective and Preventive Action untuk mendukung proses corrective and preventive action SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Corrective and Preventive Action. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel capa_root_cause_analyses berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `capa_case_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan capa case id untuk entitas capa_root_cause_analyses pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `capa_cases(id)`
- Kolom 4: `method`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: 5why|fishbone|combined.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `summary`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan summary untuk entitas capa_root_cause_analyses pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `analyzed_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan analyzed by untuk entitas capa_root_cause_analyses pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 7: `analyzed_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan analyzed at untuk entitas capa_root_cause_analyses pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_capa_root_cause_analyses_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.5.3. `capa_five_whys`
Tabel capa_five_whys pada domain Corrective and Preventive Action untuk mendukung proses corrective and preventive action SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Corrective and Preventive Action. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel capa_five_whys berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `rca_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan rca id untuk entitas capa_five_whys pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `rcas(id)`
- Kolom 4: `why_level`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: 1-5.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `question`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan question untuk entitas capa_five_whys pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `answer`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan answer untuk entitas capa_five_whys pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 8: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 9: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 10: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 14: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_capa_five_whys_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.5.4. `capa_fishbone_diagrams`
Tabel capa_fishbone_diagrams pada domain Corrective and Preventive Action untuk mendukung proses corrective and preventive action SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Corrective and Preventive Action. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel capa_fishbone_diagrams berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `rca_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan rca id untuk entitas capa_fishbone_diagrams pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `rcas(id)`
- Kolom 4: `category`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Man|machine|method|material|environment|measurement.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `cause_description`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan cause description untuk entitas capa_fishbone_diagrams pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `is_root_cause`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is root cause untuk entitas capa_fishbone_diagrams pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 8: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 9: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 10: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 14: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_capa_fishbone_diagrams_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.5.5. `capa_corrective_actions`
Tabel capa_corrective_actions pada domain Corrective and Preventive Action untuk mendukung proses corrective and preventive action SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Corrective and Preventive Action. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel capa_corrective_actions berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `capa_case_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan capa case id untuk entitas capa_corrective_actions pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `capa_cases(id)`
- Kolom 4: `action_description`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan action description untuk entitas capa_corrective_actions pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `responsible_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan responsible id untuk entitas capa_corrective_actions pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 6: `planned_start`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan planned start untuk entitas capa_corrective_actions pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `due_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan due date untuk entitas capa_corrective_actions pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Planned|in_progress|completed|overdue.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `completed_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan completed at untuk entitas capa_corrective_actions pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_capa_corrective_actions_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_capa_corrective_actions_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.5.6. `capa_preventive_actions`
Tabel capa_preventive_actions pada domain Corrective and Preventive Action untuk mendukung proses corrective and preventive action SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Corrective and Preventive Action. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel capa_preventive_actions berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `capa_case_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan capa case id untuk entitas capa_preventive_actions pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `capa_cases(id)`
- Kolom 4: `action_description`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan action description untuk entitas capa_preventive_actions pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `responsible_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan responsible id untuk entitas capa_preventive_actions pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 6: `due_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan due date untuk entitas capa_preventive_actions pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Planned|in_progress|completed.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `completed_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan completed at untuk entitas capa_preventive_actions pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_capa_preventive_actions_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_capa_preventive_actions_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.5.7. `capa_verifications`
Tabel capa_verifications pada domain Corrective and Preventive Action untuk mendukung proses corrective and preventive action SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Corrective and Preventive Action. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel capa_verifications berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `capa_case_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan capa case id untuk entitas capa_verifications pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `capa_cases(id)`
- Kolom 4: `verified_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan verified by untuk entitas capa_verifications pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 5: `verification_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan verification date untuk entitas capa_verifications pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `result`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Effective|partially|not_effective.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `verification_notes`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan verification notes untuk entitas capa_verifications pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `evidence_attachment_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Menyimpan evidence attachment id untuk entitas capa_verifications pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `evidence_attachments(id)`
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_capa_verifications_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.5.8. `capa_effectiveness_reviews`
Tabel capa_effectiveness_reviews pada domain Corrective and Preventive Action untuk mendukung proses corrective and preventive action SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Corrective and Preventive Action. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel capa_effectiveness_reviews berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `capa_case_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan capa case id untuk entitas capa_effectiveness_reviews pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `capa_cases(id)`
- Kolom 4: `reviewed_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan reviewed by untuk entitas capa_effectiveness_reviews pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 5: `review_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan review date untuk entitas capa_effectiveness_reviews pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `is_effective`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is effective untuk entitas capa_effectiveness_reviews pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `review_notes`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan review notes untuk entitas capa_effectiveness_reviews pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `next_review_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan next review date untuk entitas capa_effectiveness_reviews pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_capa_effectiveness_reviews_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.5.9. `capa_status_histories`
Tabel capa_status_histories pada domain Corrective and Preventive Action untuk mendukung proses corrective and preventive action SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Corrective and Preventive Action. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel capa_status_histories berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `capa_case_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan capa case id untuk entitas capa_status_histories pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `capa_cases(id)`
- Kolom 4: `from_status`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan from status untuk entitas capa_status_histories pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `to_status`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan to status untuk entitas capa_status_histories pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `changed_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan changed by untuk entitas capa_status_histories pada domain Corrective and Preventive Action.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 7: `change_reason`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan change reason untuk entitas capa_status_histories pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `changed_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan changed at untuk entitas capa_status_histories pada domain Corrective and Preventive Action.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_capa_status_histories_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

### 7.6. Domain Risk Management
Register risiko, item risiko, assessment, matriks, mitigasi, monitoring, dan review berkala.

#### 7.6.1. `risk_categories`
Tabel risk_categories pada domain Risk Management untuk mendukung proses risk management SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Risk Management. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel risk_categories berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan code untuk entitas risk_categories pada domain Risk Management.
  - Constraint: Unique sesuai scope tenant
- Kolom 4: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas risk_categories pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `color_hex`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan color hex untuk entitas risk_categories pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `description`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan description untuk entitas risk_categories pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas risk_categories pada domain Risk Management.
  - Constraint: Default true
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_risk_categories_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_risk_categories_code` unique pada `code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.6.2. `risk_registers`
Tabel risk_registers pada domain Risk Management untuk mendukung proses risk management SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Risk Management. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel risk_registers berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 4: `fiscal_year_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan fiscal year id untuk entitas risk_registers pada domain Risk Management.
  - Constraint: Foreign key
  - Referensi: `fiscal_years(id)`
- Kolom 5: `register_code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan register code untuk entitas risk_registers pada domain Risk Management.
  - Constraint: Unique sesuai scope tenant
- Kolom 6: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas risk_registers pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Draft|active|review|archived.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `owner_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan owner id untuk entitas risk_registers pada domain Risk Management.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_risk_registers_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_risk_registers_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.
- `ux_risk_registers_register_code` unique pada `register_code` dengan scope tenant bila ada.
- `ix_risk_registers_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.6.3. `risk_items`
Item risiko teridentifikasi lengkap dengan skor inheren, residual, status, dan kategori risiko.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Risk Management. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel risk_items berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `risk_register_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan risk register id untuk entitas risk_items pada domain Risk Management.
  - Constraint: Foreign key
  - Referensi: `risk_registers(id)`
- Kolom 4: `risk_category_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan risk category id untuk entitas risk_items pada domain Risk Management.
  - Constraint: Foreign key
  - Referensi: `risk_categories(id)`
- Kolom 5: `risk_code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan risk code untuk entitas risk_items pada domain Risk Management.
  - Constraint: Unique sesuai scope tenant
- Kolom 6: `risk_description`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan risk description untuk entitas risk_items pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `risk_source`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan risk source untuk entitas risk_items pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `inherent_likelihood`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan inherent likelihood untuk entitas risk_items pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `inherent_impact`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan inherent impact untuk entitas risk_items pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `inherent_score`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan inherent score untuk entitas risk_items pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 11: `residual_likelihood`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan residual likelihood untuk entitas risk_items pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 12: `residual_impact`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan residual impact untuk entitas risk_items pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 13: `residual_score`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan residual score untuk entitas risk_items pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 14: `risk_level`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Low|medium|high|extreme.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 15: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Identified|assessed|mitigated|accepted|closed.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 16: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 17: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 18: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 19: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 20: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 21: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 22: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 23: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_risk_items_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_risk_items_risk_code` unique pada `risk_code` dengan scope tenant bila ada.
- `ix_risk_items_risk_level` pada (`risk_level`) untuk antrian kerja, filter laporan, dan monitoring SLA.
- `ix_risk_items_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.6.4. `risk_assessments`
Tabel risk_assessments pada domain Risk Management untuk mendukung proses risk management SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Risk Management. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel risk_assessments berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `risk_item_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan risk item id untuk entitas risk_assessments pada domain Risk Management.
  - Constraint: Foreign key
  - Referensi: `risk_items(id)`
- Kolom 4: `assessed_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan assessed by untuk entitas risk_assessments pada domain Risk Management.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 5: `assessment_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan assessment date untuk entitas risk_assessments pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `likelihood`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan likelihood untuk entitas risk_assessments pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `impact`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan impact untuk entitas risk_assessments pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `risk_score`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan risk score untuk entitas risk_assessments pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `assessment_notes`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan assessment notes untuk entitas risk_assessments pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `assessment_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Inherent|residual.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 11: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 12: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 13: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 14: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 18: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_risk_assessments_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.6.5. `risk_matrices`
Tabel risk_matrices pada domain Risk Management untuk mendukung proses risk management SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Risk Management. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel risk_matrices berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas risk_matrices pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 4: `likelihood_levels`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan likelihood levels untuk entitas risk_matrices pada domain Risk Management.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 5: `impact_levels`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan impact levels untuk entitas risk_matrices pada domain Risk Management.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 6: `risk_level_mapping`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan risk level mapping untuk entitas risk_matrices pada domain Risk Management.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 7: `is_default`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is default untuk entitas risk_matrices pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_risk_matrices_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.6.6. `risk_mitigation_plans`
Tabel risk_mitigation_plans pada domain Risk Management untuk mendukung proses risk management SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Risk Management. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel risk_mitigation_plans berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `risk_item_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan risk item id untuk entitas risk_mitigation_plans pada domain Risk Management.
  - Constraint: Foreign key
  - Referensi: `risk_items(id)`
- Kolom 4: `strategy`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Avoid|reduce|transfer|accept.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `plan_description`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan plan description untuk entitas risk_mitigation_plans pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `responsible_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan responsible id untuk entitas risk_mitigation_plans pada domain Risk Management.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 7: `target_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan target date untuk entitas risk_mitigation_plans pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Planned|in_progress|completed.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `estimated_cost`
  - Tipe: `NUMERIC(12,2)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan estimated cost untuk entitas risk_mitigation_plans pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_risk_mitigation_plans_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_risk_mitigation_plans_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.6.7. `risk_monitoring_logs`
Tabel risk_monitoring_logs pada domain Risk Management untuk mendukung proses risk management SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Risk Management. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel risk_monitoring_logs berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `risk_item_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan risk item id untuk entitas risk_monitoring_logs pada domain Risk Management.
  - Constraint: Foreign key
  - Referensi: `risk_items(id)`
- Kolom 4: `monitored_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan monitored by untuk entitas risk_monitoring_logs pada domain Risk Management.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 5: `monitoring_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan monitoring date untuk entitas risk_monitoring_logs pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `current_likelihood`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan current likelihood untuk entitas risk_monitoring_logs pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `current_impact`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan current impact untuk entitas risk_monitoring_logs pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `monitoring_notes`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan monitoring notes untuk entitas risk_monitoring_logs pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `trend`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Increasing|stable|decreasing.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_risk_monitoring_logs_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.6.8. `risk_reviews`
Tabel risk_reviews pada domain Risk Management untuk mendukung proses risk management SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Risk Management. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel risk_reviews berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `risk_register_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan risk register id untuk entitas risk_reviews pada domain Risk Management.
  - Constraint: Foreign key
  - Referensi: `risk_registers(id)`
- Kolom 4: `review_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan review date untuk entitas risk_reviews pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `reviewed_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan reviewed by untuk entitas risk_reviews pada domain Risk Management.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 6: `conclusion`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan conclusion untuk entitas risk_reviews pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `review_outcome`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Accept|revise|escalate.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `next_review_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan next review date untuk entitas risk_reviews pada domain Risk Management.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_risk_reviews_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

### 7.7. Domain Akreditasi
Tipe standar, bab, standar, kriteria, elemen penilaian, self assessment, gap, mock survey, dan evidence.

#### 7.7.1. `accreditation_types`
Tabel accreditation_types pada domain Akreditasi untuk mendukung proses akreditasi SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Akreditasi. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- Tabel bersifat referensi global atau operasional sistem; bila dipakai untuk tenant tertentu, relasi tenant berada pada tabel turunan/transaksi.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel accreditation_types berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: FKTP|FKTL.
  - Constraint: Unique sesuai scope tenant
- Kolom 3: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas accreditation_types pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 4: `description`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan description untuk entitas accreditation_types pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `issuing_body`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: KARS|etc.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas accreditation_types pada domain Akreditasi.
  - Constraint: Default true
- Kolom 7: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 8: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 9: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 10: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 14: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ux_accreditation_types_code` unique pada `code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.7.2. `accreditation_chapters`
Tabel accreditation_chapters pada domain Akreditasi untuk mendukung proses akreditasi SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Akreditasi. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- Tabel bersifat referensi global atau operasional sistem; bila dipakai untuk tenant tertentu, relasi tenant berada pada tabel turunan/transaksi.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel accreditation_chapters berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `accreditation_type_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan accreditation type id untuk entitas accreditation_chapters pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `accreditation_types(id)`
- Kolom 3: `chapter_number`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan chapter number untuk entitas accreditation_chapters pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 4: `title`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan title untuk entitas accreditation_chapters pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `description`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan description untuk entitas accreditation_chapters pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `sort_order`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan sort order untuk entitas accreditation_chapters pada domain Akreditasi.
  - Constraint: Default 0
- Kolom 7: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas accreditation_chapters pada domain Akreditasi.
  - Constraint: Default true
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.7.3. `accreditation_standards`
Tabel accreditation_standards pada domain Akreditasi untuk mendukung proses akreditasi SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Akreditasi. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- Tabel bersifat referensi global atau operasional sistem; bila dipakai untuk tenant tertentu, relasi tenant berada pada tabel turunan/transaksi.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel accreditation_standards berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `chapter_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan chapter id untuk entitas accreditation_standards pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `chapters(id)`
- Kolom 3: `standard_code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan standard code untuk entitas accreditation_standards pada domain Akreditasi.
  - Constraint: Unique sesuai scope tenant
- Kolom 4: `title`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan title untuk entitas accreditation_standards pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `description`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan description untuk entitas accreditation_standards pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `sort_order`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan sort order untuk entitas accreditation_standards pada domain Akreditasi.
  - Constraint: Default 0
- Kolom 7: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas accreditation_standards pada domain Akreditasi.
  - Constraint: Default true
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ux_accreditation_standards_standard_code` unique pada `standard_code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.7.4. `accreditation_criteria`
Tabel accreditation_criteria pada domain Akreditasi untuk mendukung proses akreditasi SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Akreditasi. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- Tabel bersifat referensi global atau operasional sistem; bila dipakai untuk tenant tertentu, relasi tenant berada pada tabel turunan/transaksi.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel accreditation_criteria berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `standard_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan standard id untuk entitas accreditation_criteria pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `standards(id)`
- Kolom 3: `criteria_code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan criteria code untuk entitas accreditation_criteria pada domain Akreditasi.
  - Constraint: Unique sesuai scope tenant
- Kolom 4: `description`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan description untuk entitas accreditation_criteria pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `weight`
  - Tipe: `NUMERIC(12,2)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan weight untuk entitas accreditation_criteria pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `sort_order`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan sort order untuk entitas accreditation_criteria pada domain Akreditasi.
  - Constraint: Default 0
- Kolom 7: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas accreditation_criteria pada domain Akreditasi.
  - Constraint: Default true
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ux_accreditation_criteria_criteria_code` unique pada `criteria_code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.7.5. `accreditation_elements`
Elemen penilaian akreditasi yang menjadi unit terkecil untuk self assessment dan evidence.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Akreditasi. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- Tabel bersifat referensi global atau operasional sistem; bila dipakai untuk tenant tertentu, relasi tenant berada pada tabel turunan/transaksi.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel accreditation_elements berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `criteria_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan criteria id untuk entitas accreditation_elements pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `criterias(id)`
- Kolom 3: `element_code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan element code untuk entitas accreditation_elements pada domain Akreditasi.
  - Constraint: Unique sesuai scope tenant
- Kolom 4: `description`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan description untuk entitas accreditation_elements pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `evidence_required`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan evidence required untuk entitas accreditation_elements pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `sort_order`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan sort order untuk entitas accreditation_elements pada domain Akreditasi.
  - Constraint: Default 0
- Kolom 7: `is_mandatory`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is mandatory untuk entitas accreditation_elements pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas accreditation_elements pada domain Akreditasi.
  - Constraint: Default true
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ux_accreditation_elements_element_code` unique pada `element_code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.7.6. `accreditation_programs`
Tabel accreditation_programs pada domain Akreditasi untuk mendukung proses akreditasi SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Akreditasi. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel accreditation_programs berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `accreditation_type_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan accreditation type id untuk entitas accreditation_programs pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `accreditation_types(id)`
- Kolom 4: `program_name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan program name untuk entitas accreditation_programs pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `cycle`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: 2024-2027.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `start_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan start date untuk entitas accreditation_programs pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `target_survey_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan target survey date untuk entitas accreditation_programs pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Planning|self_assessment|mock|survey|accredited.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_accreditation_programs_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_accreditation_programs_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.7.7. `accreditation_self_assessments`
Tabel accreditation_self_assessments pada domain Akreditasi untuk mendukung proses akreditasi SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Akreditasi. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel accreditation_self_assessments berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `program_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan program id untuk entitas accreditation_self_assessments pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `programs(id)`
- Kolom 4: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 5: `assessment_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan assessment date untuk entitas accreditation_self_assessments pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `assessor_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan assessor id untuk entitas accreditation_self_assessments pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 7: `overall_score`
  - Tipe: `NUMERIC(12,2)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan overall score untuk entitas accreditation_self_assessments pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Draft|submitted|reviewed|approved.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `summary`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan summary untuk entitas accreditation_self_assessments pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_accreditation_self_assessments_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_accreditation_self_assessments_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.
- `ix_accreditation_self_assessments_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.7.8. `accreditation_self_assessment_items`
Tabel accreditation_self_assessment_items pada domain Akreditasi untuk mendukung proses akreditasi SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Akreditasi. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel accreditation_self_assessment_items berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `self_assessment_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan self assessment id untuk entitas accreditation_self_assessment_items pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `self_assessments(id)`
- Kolom 4: `element_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan element id untuk entitas accreditation_self_assessment_items pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `elements(id)`
- Kolom 5: `score`
  - Tipe: `NUMERIC(12,2)`
  - Nullable: Tidak
  - Deskripsi: 0-100.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `compliance`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Compliant|partial|non_compliant|na.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `notes`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan notes untuk entitas accreditation_self_assessment_items pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `improvement_plan`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan improvement plan untuk entitas accreditation_self_assessment_items pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_accreditation_self_assessment_items_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.7.9. `accreditation_gap_analyses`
Tabel accreditation_gap_analyses pada domain Akreditasi untuk mendukung proses akreditasi SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Akreditasi. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel accreditation_gap_analyses berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `self_assessment_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan self assessment id untuk entitas accreditation_gap_analyses pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `self_assessments(id)`
- Kolom 4: `analyzed_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan analyzed by untuk entitas accreditation_gap_analyses pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 5: `analysis_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan analysis date untuk entitas accreditation_gap_analyses pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `executive_summary`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan executive summary untuk entitas accreditation_gap_analyses pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Draft|final.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_accreditation_gap_analyses_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_accreditation_gap_analyses_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.7.10. `accreditation_gap_items`
Tabel accreditation_gap_items pada domain Akreditasi untuk mendukung proses akreditasi SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Akreditasi. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel accreditation_gap_items berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `gap_analysis_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan gap analysis id untuk entitas accreditation_gap_items pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `gap_analysiss(id)`
- Kolom 4: `element_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan element id untuk entitas accreditation_gap_items pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `elements(id)`
- Kolom 5: `gap_description`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan gap description untuk entitas accreditation_gap_items pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `priority`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Low|medium|high|critical.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `recommended_action`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan recommended action untuk entitas accreditation_gap_items pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `assigned_to`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan assigned to untuk entitas accreditation_gap_items pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 9: `target_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan target date untuk entitas accreditation_gap_items pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_accreditation_gap_items_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_accreditation_gap_items_priority` pada (`priority`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.7.11. `accreditation_mock_surveys`
Tabel accreditation_mock_surveys pada domain Akreditasi untuk mendukung proses akreditasi SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Akreditasi. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel accreditation_mock_surveys berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `program_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan program id untuk entitas accreditation_mock_surveys pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `programs(id)`
- Kolom 4: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 5: `survey_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan survey date untuk entitas accreditation_mock_surveys pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `lead_surveyor_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan lead surveyor id untuk entitas accreditation_mock_surveys pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 7: `readiness_score`
  - Tipe: `NUMERIC(12,2)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan readiness score untuk entitas accreditation_mock_surveys pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `findings_summary`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan findings summary untuk entitas accreditation_mock_surveys pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Scheduled|completed|reported.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_accreditation_mock_surveys_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_accreditation_mock_surveys_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.
- `ix_accreditation_mock_surveys_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.7.12. `accreditation_evidence`
Tabel accreditation_evidence pada domain Akreditasi untuk mendukung proses akreditasi SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Akreditasi. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel accreditation_evidence berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 4: `element_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan element id untuk entitas accreditation_evidence pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `elements(id)`
- Kolom 5: `attachment_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Menyimpan attachment id untuk entitas accreditation_evidence pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `attachments(id)`
- Kolom 6: `title`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan title untuk entitas accreditation_evidence pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `description`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan description untuk entitas accreditation_evidence pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `valid_from`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan valid from untuk entitas accreditation_evidence pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `valid_until`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan valid until untuk entitas accreditation_evidence pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `uploaded_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan uploaded by untuk entitas accreditation_evidence pada domain Akreditasi.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `uploaded_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan uploaded at untuk entitas accreditation_evidence pada domain Akreditasi.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 12: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 13: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 14: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 15: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 18: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 19: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_accreditation_evidence_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_accreditation_evidence_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

### 7.8. Domain Manajemen Mutu
Program mutu, indikator, target, pengukuran, monitoring, evaluasi, dan pelaporan mutu.

#### 7.8.1. `quality_programs`
Tabel quality_programs pada domain Manajemen Mutu untuk mendukung proses manajemen mutu SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Manajemen Mutu. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel quality_programs berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `fiscal_year_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan fiscal year id untuk entitas quality_programs pada domain Manajemen Mutu.
  - Constraint: Foreign key
  - Referensi: `fiscal_years(id)`
- Kolom 4: `program_code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan program code untuk entitas quality_programs pada domain Manajemen Mutu.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas quality_programs pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `objective`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan objective untuk entitas quality_programs pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Draft|active|evaluation|closed.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `program_owner_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan program owner id untuk entitas quality_programs pada domain Manajemen Mutu.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_quality_programs_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_quality_programs_program_code` unique pada `program_code` dengan scope tenant bila ada.
- `ix_quality_programs_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.8.2. `quality_indicators`
Tabel quality_indicators pada domain Manajemen Mutu untuk mendukung proses manajemen mutu SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Manajemen Mutu. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel quality_indicators berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `program_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan program id untuk entitas quality_indicators pada domain Manajemen Mutu.
  - Constraint: Foreign key
  - Referensi: `programs(id)`
- Kolom 4: `indicator_code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan indicator code untuk entitas quality_indicators pada domain Manajemen Mutu.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas quality_indicators pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `definition`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan definition untuk entitas quality_indicators pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `unit`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Percent|count|ratio.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `direction`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Higher_better|lower_better.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `baseline_value`
  - Tipe: `NUMERIC(12,2)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan baseline value untuk entitas quality_indicators pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `data_source`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan data source untuk entitas quality_indicators pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 11: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 12: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 13: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 14: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 18: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_quality_indicators_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_quality_indicators_indicator_code` unique pada `indicator_code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.8.3. `quality_indicator_targets`
Tabel quality_indicator_targets pada domain Manajemen Mutu untuk mendukung proses manajemen mutu SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Manajemen Mutu. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel quality_indicator_targets berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `indicator_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan indicator id untuk entitas quality_indicator_targets pada domain Manajemen Mutu.
  - Constraint: Foreign key
  - Referensi: `indicators(id)`
- Kolom 4: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 5: `period`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Monthly|quarterly|annual.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `target_value`
  - Tipe: `NUMERIC(12,2)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan target value untuk entitas quality_indicator_targets pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `threshold_warning`
  - Tipe: `NUMERIC(12,2)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan threshold warning untuk entitas quality_indicator_targets pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `threshold_critical`
  - Tipe: `NUMERIC(12,2)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan threshold critical untuk entitas quality_indicator_targets pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_quality_indicator_targets_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_quality_indicator_targets_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.8.4. `quality_measurements`
Pengukuran aktual indikator mutu per klinik dan periode pelaporan.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Manajemen Mutu. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel quality_measurements berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `indicator_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan indicator id untuk entitas quality_measurements pada domain Manajemen Mutu.
  - Constraint: Foreign key
  - Referensi: `indicators(id)`
- Kolom 4: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 5: `measurement_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan measurement date untuk entitas quality_measurements pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `measured_value`
  - Tipe: `NUMERIC(12,2)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan measured value untuk entitas quality_measurements pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `period_label`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: 2026-Q1.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `recorded_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan recorded by untuk entitas quality_measurements pada domain Manajemen Mutu.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 9: `notes`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan notes untuk entitas quality_measurements pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_quality_measurements_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_quality_measurements_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.8.5. `quality_monitoring_sessions`
Tabel quality_monitoring_sessions pada domain Manajemen Mutu untuk mendukung proses manajemen mutu SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Manajemen Mutu. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel quality_monitoring_sessions berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `program_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan program id untuk entitas quality_monitoring_sessions pada domain Manajemen Mutu.
  - Constraint: Foreign key
  - Referensi: `programs(id)`
- Kolom 4: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 5: `session_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan session date untuk entitas quality_monitoring_sessions pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `facilitator_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan facilitator id untuk entitas quality_monitoring_sessions pada domain Manajemen Mutu.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 7: `findings`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan findings untuk entitas quality_monitoring_sessions pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Scheduled|completed.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_quality_monitoring_sessions_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_quality_monitoring_sessions_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.
- `ix_quality_monitoring_sessions_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.8.6. `quality_evaluations`
Tabel quality_evaluations pada domain Manajemen Mutu untuk mendukung proses manajemen mutu SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Manajemen Mutu. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel quality_evaluations berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `program_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan program id untuk entitas quality_evaluations pada domain Manajemen Mutu.
  - Constraint: Foreign key
  - Referensi: `programs(id)`
- Kolom 4: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 5: `evaluation_date`
  - Tipe: `DATE`
  - Nullable: Tidak
  - Deskripsi: Menyimpan evaluation date untuk entitas quality_evaluations pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `overall_score`
  - Tipe: `NUMERIC(12,2)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan overall score untuk entitas quality_evaluations pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `strengths`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan strengths untuk entitas quality_evaluations pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `weaknesses`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan weaknesses untuk entitas quality_evaluations pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `evaluator_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan evaluator id untuk entitas quality_evaluations pada domain Manajemen Mutu.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_quality_evaluations_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_quality_evaluations_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.8.7. `quality_reports`
Tabel quality_reports pada domain Manajemen Mutu untuk mendukung proses manajemen mutu SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Manajemen Mutu. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel quality_reports berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `program_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan program id untuk entitas quality_reports pada domain Manajemen Mutu.
  - Constraint: Foreign key
  - Referensi: `programs(id)`
- Kolom 4: `report_period`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan report period untuk entitas quality_reports pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `generated_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan generated by untuk entitas quality_reports pada domain Manajemen Mutu.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 6: `file_path`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan file path untuk entitas quality_reports pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `generated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan generated at untuk entitas quality_reports pada domain Manajemen Mutu.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_quality_reports_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

### 7.9. Domain Workflow Engine
Definisi workflow, step, transisi, instance, assignment, approval, dan histori eksekusi.

#### 7.9.1. `workflow_definitions`
Tabel workflow_definitions pada domain Workflow Engine untuk mendukung proses workflow engine SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Workflow Engine. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel workflow_definitions berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `module`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Dms|audit|capa|accreditation.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 4: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas workflow_definitions pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan code untuk entitas workflow_definitions pada domain Workflow Engine.
  - Constraint: Unique sesuai scope tenant
- Kolom 6: `version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan version untuk entitas workflow_definitions pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas workflow_definitions pada domain Workflow Engine.
  - Constraint: Default true
- Kolom 8: `configuration`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan configuration untuk entitas workflow_definitions pada domain Workflow Engine.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 9: `created_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_workflow_definitions_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_workflow_definitions_code` unique pada `code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.9.2. `workflow_steps`
Tabel workflow_steps pada domain Workflow Engine untuk mendukung proses workflow engine SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Workflow Engine. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel workflow_steps berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `workflow_definition_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan workflow definition id untuk entitas workflow_steps pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `workflow_definitions(id)`
- Kolom 4: `step_order`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan step order untuk entitas workflow_steps pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `step_code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan step code untuk entitas workflow_steps pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas workflow_steps pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `step_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Review|approve|publish|notify.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `required_role_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan required role id untuk entitas workflow_steps pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `required_roles(id)`
- Kolom 9: `sla_hours`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan sla hours untuk entitas workflow_steps pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `is_parallel`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is parallel untuk entitas workflow_steps pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 11: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 12: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 13: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 14: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 18: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_workflow_steps_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.9.3. `workflow_transitions`
Tabel workflow_transitions pada domain Workflow Engine untuk mendukung proses workflow engine SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Workflow Engine. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel workflow_transitions berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `workflow_definition_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan workflow definition id untuk entitas workflow_transitions pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `workflow_definitions(id)`
- Kolom 4: `from_step_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan from step id untuk entitas workflow_transitions pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `from_steps(id)`
- Kolom 5: `to_step_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan to step id untuk entitas workflow_transitions pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `to_steps(id)`
- Kolom 6: `action`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Approve|reject|revise|skip.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `conditions`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan conditions untuk entitas workflow_transitions pada domain Workflow Engine.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_workflow_transitions_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.9.4. `workflow_instances`
Eksekusi nyata dari definisi workflow untuk subjek bisnis tertentu.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Workflow Engine. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel workflow_instances berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `workflow_definition_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan workflow definition id untuk entitas workflow_instances pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `workflow_definitions(id)`
- Kolom 4: `subject_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Document|CapaCase|etc.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `subject_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan subject id untuk entitas workflow_instances pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
  - Referensi: `subjects(id)`
- Kolom 6: `current_step_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Menyimpan current step id untuk entitas workflow_instances pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `current_steps(id)`
- Kolom 7: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Running|completed|cancelled|escalated.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `initiated_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan initiated by untuk entitas workflow_instances pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 9: `started_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan started at untuk entitas workflow_instances pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `completed_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan completed at untuk entitas workflow_instances pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 11: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 12: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 13: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 14: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 18: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_workflow_instances_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_workflow_instances_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.9.5. `workflow_instance_steps`
Tabel workflow_instance_steps pada domain Workflow Engine untuk mendukung proses workflow engine SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Workflow Engine. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel workflow_instance_steps berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `workflow_instance_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Menyimpan workflow instance id untuk entitas workflow_instance_steps pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `workflow_instances(id)`
- Kolom 4: `workflow_step_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan workflow step id untuk entitas workflow_instance_steps pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `workflow_steps(id)`
- Kolom 5: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Pending|in_progress|completed|skipped.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `assignee_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan assignee id untuk entitas workflow_instance_steps pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 7: `started_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan started at untuk entitas workflow_instance_steps pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `completed_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan completed at untuk entitas workflow_instance_steps pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `sla_breached_hours`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan sla breached hours untuk entitas workflow_instance_steps pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_workflow_instance_steps_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_workflow_instance_steps_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.9.6. `workflow_assignments`
Tabel workflow_assignments pada domain Workflow Engine untuk mendukung proses workflow engine SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Workflow Engine. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel workflow_assignments berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `instance_step_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan instance step id untuk entitas workflow_assignments pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `instance_steps(id)`
- Kolom 4: `assignee_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan assignee id untuk entitas workflow_assignments pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 5: `assigned_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan assigned by untuk entitas workflow_assignments pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 6: `assigned_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan assigned at untuk entitas workflow_assignments pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `due_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan due at untuk entitas workflow_assignments pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_workflow_assignments_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.9.7. `workflow_approvals`
Tabel workflow_approvals pada domain Workflow Engine untuk mendukung proses workflow engine SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Workflow Engine. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel workflow_approvals berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `instance_step_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan instance step id untuk entitas workflow_approvals pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `instance_steps(id)`
- Kolom 4: `approver_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan approver id untuk entitas workflow_approvals pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 5: `decision`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Approved|rejected|revision_requested.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `comments`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan comments untuk entitas workflow_approvals pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `decided_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan decided at untuk entitas workflow_approvals pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_workflow_approvals_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.9.8. `workflow_histories`
Tabel workflow_histories pada domain Workflow Engine untuk mendukung proses workflow engine SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Workflow Engine. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel workflow_histories berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `workflow_instance_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Menyimpan workflow instance id untuk entitas workflow_histories pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `workflow_instances(id)`
- Kolom 4: `from_step_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan from step id untuk entitas workflow_histories pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `from_steps(id)`
- Kolom 5: `to_step_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan to step id untuk entitas workflow_histories pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `to_steps(id)`
- Kolom 6: `action`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan action untuk entitas workflow_histories pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `action_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan action by untuk entitas workflow_histories pada domain Workflow Engine.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 8: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 9: `action_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan action at untuk entitas workflow_histories pada domain Workflow Engine.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update

**Indeks yang direkomendasikan:**
- `ix_workflow_histories_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

### 7.10. Domain Notification
Channel, template, notifikasi, penerima, preferensi, dan log pengiriman.

#### 7.10.1. `notification_channels`
Tabel notification_channels pada domain Notification untuk mendukung proses notification SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Notification. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- Tabel bersifat referensi global atau operasional sistem; bila dipakai untuk tenant tertentu, relasi tenant berada pada tabel turunan/transaksi.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel notification_channels berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `code`
  - Tipe: `VARCHAR(80)`
  - Nullable: Tidak
  - Deskripsi: In_app|email|sms.
  - Constraint: Unique sesuai scope tenant
- Kolom 3: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas notification_channels pada domain Notification.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 4: `configuration`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan configuration untuk entitas notification_channels pada domain Notification.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 5: `is_enabled`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is enabled untuk entitas notification_channels pada domain Notification.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 7: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 8: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 9: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 10: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 13: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ux_notification_channels_code` unique pada `code` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.10.2. `notification_templates`
Tabel notification_templates pada domain Notification untuk mendukung proses notification SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Notification. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel notification_templates berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `channel_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan channel id untuk entitas notification_templates pada domain Notification.
  - Constraint: Foreign key
  - Referensi: `channels(id)`
- Kolom 4: `event_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Document_expiry|capa_due|audit_scheduled.
  - Constraint: Unique sesuai scope tenant
- Kolom 5: `subject_template`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan subject template untuk entitas notification_templates pada domain Notification.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `body_template`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan body template untuk entitas notification_templates pada domain Notification.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `variables_schema`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan variables schema untuk entitas notification_templates pada domain Notification.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 8: `is_active`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is active untuk entitas notification_templates pada domain Notification.
  - Constraint: Default true
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_notification_templates_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ux_notification_templates_event_type` unique pada `event_type` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.10.3. `notifications`
Pesan notifikasi lintas kanal yang dibuat oleh event sistem atau pengguna.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Notification. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel notifications berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 4: `type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan type untuk entitas notifications pada domain Notification.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `title`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan title untuk entitas notifications pada domain Notification.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `body`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan body untuk entitas notifications pada domain Notification.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `data`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan data untuk entitas notifications pada domain Notification.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 8: `priority`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Low|normal|high|urgent.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `sent_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan sent at untuk entitas notifications pada domain Notification.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `triggered_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan triggered by untuk entitas notifications pada domain Notification.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 11: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 12: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 13: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 14: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 17: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 18: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_notifications_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_notifications_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.
- `ix_notifications_priority` pada (`priority`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.10.4. `notification_recipients`
Tabel notification_recipients pada domain Notification untuk mendukung proses notification SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Notification. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel notification_recipients berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `notification_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan notification id untuk entitas notification_recipients pada domain Notification.
  - Constraint: Foreign key
  - Referensi: `notifications(id)`
- Kolom 4: `user_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan user id untuk entitas notification_recipients pada domain Notification.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 5: `delivery_status`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Pending|sent|delivered|failed.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `read_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan read at untuk entitas notification_recipients pada domain Notification.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `delivered_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan delivered at untuk entitas notification_recipients pada domain Notification.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_notification_recipients_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.10.5. `notification_preferences`
Tabel notification_preferences pada domain Notification untuk mendukung proses notification SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Notification. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel notification_preferences berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `user_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan user id untuk entitas notification_preferences pada domain Notification.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 4: `event_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan event type untuk entitas notification_preferences pada domain Notification.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `channel_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan channel id untuk entitas notification_preferences pada domain Notification.
  - Constraint: Foreign key
  - Referensi: `channels(id)`
- Kolom 6: `is_enabled`
  - Tipe: `BOOLEAN`
  - Nullable: Tidak
  - Deskripsi: Menyimpan is enabled untuk entitas notification_preferences pada domain Notification.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `digest_frequency`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Instant|daily|weekly.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 9: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 10: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 11: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 12: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 15: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_notification_preferences_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.10.6. `notification_logs`
Tabel notification_logs pada domain Notification untuk mendukung proses notification SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain Notification. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel notification_logs berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `notification_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan notification id untuk entitas notification_logs pada domain Notification.
  - Constraint: Foreign key
  - Referensi: `notifications(id)`
- Kolom 4: `channel_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan channel id untuk entitas notification_logs pada domain Notification.
  - Constraint: Foreign key
  - Referensi: `channels(id)`
- Kolom 5: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Queued|sent|failed|bounced.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `error_message`
  - Tipe: `TEXT`
  - Nullable: Ya
  - Deskripsi: Menyimpan error message untuk entitas notification_logs pada domain Notification.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `provider_response`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan provider response untuk entitas notification_logs pada domain Notification.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 8: `attempted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan attempted at untuk entitas notification_logs pada domain Notification.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_notification_logs_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_notification_logs_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

### 7.11. Domain System Operations
Audit trail, activity log, job queue, outbox, migration metadata, dan operasi platform.

#### 7.11.1. `jobs`
Tabel jobs pada domain System Operations untuk mendukung proses system operations SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain System Operations. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel jobs berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 4: `queue`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Nama queue.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `payload`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Payload job.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 6: `attempts`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Jumlah percobaan.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `reserved_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan reserved at untuk entitas jobs pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `available_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan available at untuk entitas jobs pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_jobs_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_jobs_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.11.2. `job_batches`
Tabel job_batches pada domain System Operations untuk mendukung proses system operations SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain System Operations. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel job_batches berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan name untuk entitas job_batches pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 4: `total_jobs`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan total jobs untuk entitas job_batches pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `pending_jobs`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan pending jobs untuk entitas job_batches pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `failed_jobs`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan failed jobs untuk entitas job_batches pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `failed_job_ids`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan failed job ids untuk entitas job_batches pada domain System Operations.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 8: `options`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan options untuk entitas job_batches pada domain System Operations.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 9: `cancelled_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan cancelled at untuk entitas job_batches pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_job_batches_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.11.3. `failed_jobs`
Tabel failed_jobs pada domain System Operations untuk mendukung proses system operations SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain System Operations. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel failed_jobs berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 4: `connection`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan connection untuk entitas failed_jobs pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `queue`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan queue untuk entitas failed_jobs pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `payload`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan payload untuk entitas failed_jobs pada domain System Operations.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 7: `exception`
  - Tipe: `TEXT`
  - Nullable: Tidak
  - Deskripsi: Menyimpan exception untuk entitas failed_jobs pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 8: `failed_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan failed at untuk entitas failed_jobs pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 10: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 11: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 12: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 13: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 16: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_failed_jobs_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_failed_jobs_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.11.4. `migrations_meta`
Tabel migrations_meta pada domain System Operations untuk mendukung proses system operations SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain System Operations. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- Tabel bersifat referensi global atau operasional sistem; bila dipakai untuk tenant tertentu, relasi tenant berada pada tabel turunan/transaksi.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel migrations_meta berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `migration`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Nama migration.
  - Constraint: Unique sesuai scope tenant
- Kolom 3: `batch`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan batch untuk entitas migrations_meta pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 4: `checksum_sha256`
  - Tipe: `CHAR(64)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan checksum sha256 untuk entitas migrations_meta pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `executed_by`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan executed by untuk entitas migrations_meta pada domain System Operations.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 6: `executed_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Menyimpan executed at untuk entitas migrations_meta pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain

**Indeks yang direkomendasikan:**
- `ux_migrations_meta_migration` unique pada `migration` dengan scope tenant bila ada.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.11.5. `outbox_events`
Tabel outbox_events pada domain System Operations untuk mendukung proses system operations SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain System Operations. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel outbox_events berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 4: `aggregate_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan aggregate type untuk entitas outbox_events pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `aggregate_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Menyimpan aggregate id untuk entitas outbox_events pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
  - Referensi: `aggregates(id)`
- Kolom 6: `event_type`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan event type untuk entitas outbox_events pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `payload`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan payload untuk entitas outbox_events pada domain System Operations.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 8: `status`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Pending|published|failed.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 9: `published_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Menyimpan published at untuk entitas outbox_events pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_outbox_events_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_outbox_events_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.
- `ix_outbox_events_status` pada (`status`) untuk antrian kerja, filter laporan, dan monitoring SLA.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

#### 7.11.6. `integration_logs`
Tabel integration_logs pada domain System Operations untuk mendukung proses system operations SIMMAK PPKP.

**Peran bisnis:**
Tabel ini menjadi bagian dari domain System Operations. Data dipakai oleh proses aplikasi untuk pencatatan, pelaporan, kontrol akses, auditabilitas, dan integrasi lintas modul sesuai cakupan SIMMAK PPKP.

**Kebijakan scope:**
- `organization_id` wajib menjadi filter tenant pada query aplikasi dan indeks utama.
- `clinic_id`/`target_clinic_id` membatasi data pada klinik tertentu; NULL hanya bila data berlaku lintas klinik.

**Data dictionary kolom:**
- Kolom 1: `id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Identifier unik tabel integration_logs berbasis UUID.
  - Constraint: Primary key
- Kolom 2: `organization_id`
  - Tipe: `UUID`
  - Nullable: Tidak
  - Deskripsi: Tenant organisasi pemilik data; menjadi filter wajib untuk seluruh query bisnis.
  - Constraint: Foreign key; Wajib untuk isolasi tenant
  - Referensi: `organizations(id)`
- Kolom 3: `clinic_id`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Klinik terkait; NULL hanya untuk data yang berlaku lintas klinik.
  - Constraint: Foreign key
  - Referensi: `clinics(id)`
- Kolom 4: `system_name`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan system name untuk entitas integration_logs pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 5: `direction`
  - Tipe: `VARCHAR(50)`
  - Nullable: Tidak
  - Deskripsi: Inbound|outbound.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 6: `endpoint`
  - Tipe: `VARCHAR(255)`
  - Nullable: Tidak
  - Deskripsi: Menyimpan endpoint untuk entitas integration_logs pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 7: `request_payload`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan request payload untuk entitas integration_logs pada domain System Operations.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 8: `response_payload`
  - Tipe: `JSONB`
  - Nullable: Tidak
  - Deskripsi: Menyimpan response payload untuk entitas integration_logs pada domain System Operations.
  - Constraint: Default {}::jsonb bila relevan
- Kolom 9: `status_code`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Menyimpan status code untuk entitas integration_logs pada domain System Operations.
  - Constraint: Validasi aplikasi dan constraint domain
- Kolom 10: `created_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record pertama kali dibuat.
  - Constraint: Default now()
- Kolom 11: `updated_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Tidak
  - Deskripsi: Timestamp saat record terakhir diperbarui.
  - Constraint: Default now()
- Kolom 12: `deleted_at`
  - Tipe: `TIMESTAMPTZ`
  - Nullable: Ya
  - Deskripsi: Timestamp soft delete; record tidak dihapus fisik agar audit tetap utuh.
  - Constraint: NULL berarti record aktif
- Kolom 13: `created_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang membuat record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 14: `updated_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang terakhir mengubah record.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 15: `deleted_by`
  - Tipe: `UUID`
  - Nullable: Ya
  - Deskripsi: Pengguna yang melakukan soft delete.
  - Constraint: Foreign key
  - Referensi: `users(id)`
- Kolom 16: `row_version`
  - Tipe: `INTEGER`
  - Nullable: Tidak
  - Deskripsi: Nomor versi baris untuk optimistic locking dan deteksi konflik update.
  - Constraint: Default 1, increment saat update
- Kolom 17: `metadata`
  - Tipe: `JSONB`
  - Nullable: Ya
  - Deskripsi: Ruang ekstensi JSONB untuk atribut non-kritis yang belum layak menjadi kolom fisik.
  - Constraint: Default {}::jsonb bila relevan

**Indeks yang direkomendasikan:**
- `ix_integration_logs_organization_active` pada (`organization_id`, `deleted_at`) untuk filter tenant dan soft delete.
- `ix_integration_logs_clinic_active` pada (`clinic_id`, `deleted_at`) untuk query dashboard klinik.

**Aturan retensi dan audit:**
Perubahan create, update, delete, restore, approval, dan perubahan status penting harus menghasilkan event audit. Record bisnis mengikuti soft delete; purge fisik hanya dilakukan melalui job retensi yang disetujui dan tercatat.

## 8. Strategi Foreign Key
Foreign key dipasang untuk relasi parent-child yang kuat, terutama `organization_id`, `clinic_id`, `unit_id`, `user_id`, dan referensi master. Pola delete default adalah `ON DELETE RESTRICT` untuk data bisnis agar histori tidak hilang. `ON DELETE SET NULL` hanya dipakai untuk actor optional seperti `updated_by` bila akun teknis dinonaktifkan. `ON DELETE CASCADE` dibatasi pada tabel junction murni seperti `role_permissions` atau detail yang tidak bermakna tanpa parent.

Rekomendasi FK utama:
- `organizations.organization_id` -> `organizations(id)`.
- `organizations.created_by` -> `users(id)`.
- `organizations.updated_by` -> `users(id)`.
- `organizations.deleted_by` -> `users(id)`.
- `clinics.organization_id` -> `organizations(id)`.
- `clinics.created_by` -> `users(id)`.
- `clinics.updated_by` -> `users(id)`.
- `clinics.deleted_by` -> `users(id)`.
- `departments.organization_id` -> `organizations(id)`.
- `departments.parent_id` -> `parents(id)`.
- `departments.created_by` -> `users(id)`.
- `departments.updated_by` -> `users(id)`.
- `departments.deleted_by` -> `users(id)`.
- `units.organization_id` -> `organizations(id)`.
- `units.clinic_id` -> `clinics(id)`.
- `units.department_id` -> `departments(id)`.
- `units.created_by` -> `users(id)`.
- `units.updated_by` -> `users(id)`.
- `units.deleted_by` -> `users(id)`.
- `employees.organization_id` -> `organizations(id)`.
- `employees.clinic_id` -> `clinics(id)`.
- `employees.unit_id` -> `units(id)`.
- `employees.created_by` -> `users(id)`.
- `employees.updated_by` -> `users(id)`.
- `employees.deleted_by` -> `users(id)`.
- `reference_data.organization_id` -> `organizations(id)`.
- `reference_data.created_by` -> `users(id)`.
- `reference_data.updated_by` -> `users(id)`.
- `reference_data.deleted_by` -> `users(id)`.
- `reference_data_values.organization_id` -> `organizations(id)`.
- `reference_data_values.reference_data_id` -> `reference_datas(id)`.
- `reference_data_values.created_by` -> `users(id)`.
- `reference_data_values.updated_by` -> `users(id)`.
- `reference_data_values.deleted_by` -> `users(id)`.
- `fiscal_years.organization_id` -> `organizations(id)`.
- `fiscal_years.created_by` -> `users(id)`.
- `fiscal_years.updated_by` -> `users(id)`.
- `fiscal_years.deleted_by` -> `users(id)`.
- `system_settings.organization_id` -> `organizations(id)`.
- `system_settings.created_by` -> `users(id)`.
- `system_settings.updated_by` -> `users(id)`.
- `system_settings.deleted_by` -> `users(id)`.
- `organization_settings.organization_id` -> `organizations(id)`.
- `organization_settings.created_by` -> `users(id)`.
- `organization_settings.updated_by` -> `users(id)`.
- `organization_settings.deleted_by` -> `users(id)`.
- `clinic_settings.organization_id` -> `organizations(id)`.
- `clinic_settings.clinic_id` -> `clinics(id)`.
- `clinic_settings.created_by` -> `users(id)`.
- `clinic_settings.updated_by` -> `users(id)`.
- `clinic_settings.deleted_by` -> `users(id)`.
- `audit_trails.organization_id` -> `organizations(id)`.
- `audit_trails.user_id` -> `users(id)`.
- `audit_trails.auditable_id` -> `auditables(id)`.
- `audit_trails.created_by` -> `users(id)`.
- `audit_trails.updated_by` -> `users(id)`.
- `audit_trails.deleted_by` -> `users(id)`.
- `activity_logs.organization_id` -> `organizations(id)`.
- `activity_logs.user_id` -> `users(id)`.
- `activity_logs.clinic_id` -> `clinics(id)`.
- `activity_logs.subject_id` -> `subjects(id)`.
- `activity_logs.created_by` -> `users(id)`.
- `activity_logs.updated_by` -> `users(id)`.
- `activity_logs.deleted_by` -> `users(id)`.
- `attachments.organization_id` -> `organizations(id)`.
- `attachments.attachable_id` -> `attachables(id)`.
- `attachments.uploaded_by` -> `users(id)`.
- `attachments.created_by` -> `users(id)`.
- `attachments.updated_by` -> `users(id)`.
- `attachments.deleted_by` -> `users(id)`.
- `users.organization_id` -> `organizations(id)`.
- `users.employee_id` -> `employees(id)`.
- `users.created_by` -> `users(id)`.
- `users.updated_by` -> `users(id)`.
- `users.deleted_by` -> `users(id)`.
- `roles.organization_id` -> `organizations(id)`.
- `roles.created_by` -> `users(id)`.
- `roles.updated_by` -> `users(id)`.
- `roles.deleted_by` -> `users(id)`.
- `permissions.created_by` -> `users(id)`.
- `permissions.updated_by` -> `users(id)`.
- `permissions.deleted_by` -> `users(id)`.
- `role_permissions.role_id` -> `roles(id)`.
- `role_permissions.organization_id` -> `organizations(id)`.
- `role_permissions.permission_id` -> `permissions(id)`.
- `user_roles.organization_id` -> `organizations(id)`.
- `user_roles.user_id` -> `users(id)`.
- `user_roles.role_id` -> `roles(id)`.
- `user_roles.clinic_id` -> `clinics(id)`.
- `user_roles.unit_id` -> `units(id)`.
- `user_roles.assigned_by` -> `users(id)`.
- `user_roles.created_by` -> `users(id)`.
- `user_roles.updated_by` -> `users(id)`.
- `user_roles.deleted_by` -> `users(id)`.
- `user_clinic_scopes.organization_id` -> `organizations(id)`.
- `user_clinic_scopes.user_id` -> `users(id)`.
- `user_clinic_scopes.clinic_id` -> `clinics(id)`.
- `user_clinic_scopes.created_by` -> `users(id)`.
- `user_clinic_scopes.updated_by` -> `users(id)`.
- `user_clinic_scopes.deleted_by` -> `users(id)`.
- `ldap_configurations.organization_id` -> `organizations(id)`.
- `ldap_configurations.created_by` -> `users(id)`.
- `ldap_configurations.updated_by` -> `users(id)`.
- `ldap_configurations.deleted_by` -> `users(id)`.
- `ldap_sync_logs.organization_id` -> `organizations(id)`.
- `ldap_sync_logs.ldap_configuration_id` -> `ldap_configurations(id)`.
- `ldap_sync_logs.created_by` -> `users(id)`.
- `ldap_sync_logs.updated_by` -> `users(id)`.
- `ldap_sync_logs.deleted_by` -> `users(id)`.
- `login_attempts.organization_id` -> `organizations(id)`.
- `sessions.organization_id` -> `organizations(id)`.
- `sessions.user_id` -> `users(id)`.
- `personal_access_tokens.organization_id` -> `organizations(id)`.
- `personal_access_tokens.user_id` -> `users(id)`.
- `personal_access_tokens.created_by` -> `users(id)`.
- `personal_access_tokens.updated_by` -> `users(id)`.
- `personal_access_tokens.deleted_by` -> `users(id)`.
- `document_categories.organization_id` -> `organizations(id)`.
- `document_categories.parent_id` -> `parents(id)`.
- `document_categories.created_by` -> `users(id)`.
- `document_categories.updated_by` -> `users(id)`.
- `document_categories.deleted_by` -> `users(id)`.
- `document_types.organization_id` -> `organizations(id)`.
- `document_types.category_id` -> `categories(id)`.
- `document_types.workflow_definition_id` -> `workflow_definitions(id)`.
- `document_types.created_by` -> `users(id)`.
- `document_types.updated_by` -> `users(id)`.
- `document_types.deleted_by` -> `users(id)`.
- `documents.organization_id` -> `organizations(id)`.
- `documents.clinic_id` -> `clinics(id)`.
- `documents.document_type_id` -> `document_types(id)`.
- `documents.workflow_instance_id` -> `workflow_instances(id)`.
- `documents.owner_id` -> `users(id)`.
- `documents.current_version_id` -> `current_versions(id)`.
- `documents.created_by` -> `users(id)`.
- `documents.updated_by` -> `users(id)`.
- `documents.deleted_by` -> `users(id)`.
- `document_versions.organization_id` -> `organizations(id)`.
- `document_versions.document_id` -> `documents(id)`.
- `document_versions.created_by` -> `users(id)`.
- `document_versions.updated_by` -> `users(id)`.
- `document_versions.deleted_by` -> `users(id)`.
- `document_reviews.organization_id` -> `organizations(id)`.
- `document_reviews.document_version_id` -> `document_versions(id)`.
- `document_reviews.reviewer_id` -> `users(id)`.
- `document_reviews.created_by` -> `users(id)`.
- `document_reviews.updated_by` -> `users(id)`.
- `document_reviews.deleted_by` -> `users(id)`.
- `document_approvals.organization_id` -> `organizations(id)`.
- `document_approvals.document_version_id` -> `document_versions(id)`.
- `document_approvals.approver_id` -> `users(id)`.
- `document_approvals.created_by` -> `users(id)`.
- `document_approvals.updated_by` -> `users(id)`.
- `document_approvals.deleted_by` -> `users(id)`.
- `document_publications.organization_id` -> `organizations(id)`.
- `document_publications.document_id` -> `documents(id)`.
- `document_publications.document_version_id` -> `document_versions(id)`.
- `document_publications.published_by` -> `users(id)`.
- `document_publications.created_by` -> `users(id)`.
- `document_publications.updated_by` -> `users(id)`.
- `document_publications.deleted_by` -> `users(id)`.
- `document_distributions.organization_id` -> `organizations(id)`.
- `document_distributions.document_id` -> `documents(id)`.
- `document_distributions.target_clinic_id` -> `clinics(id)`.
- `document_distributions.distributed_by` -> `users(id)`.
- `document_distributions.created_by` -> `users(id)`.
- `document_distributions.updated_by` -> `users(id)`.
- `document_distributions.deleted_by` -> `users(id)`.
- `document_acknowledgments.organization_id` -> `organizations(id)`.
- `document_acknowledgments.document_id` -> `documents(id)`.
- `document_acknowledgments.user_id` -> `users(id)`.
- `document_acknowledgments.clinic_id` -> `clinics(id)`.
- `document_acknowledgments.created_by` -> `users(id)`.
- `document_acknowledgments.updated_by` -> `users(id)`.
- `document_acknowledgments.deleted_by` -> `users(id)`.
- `document_qr_codes.organization_id` -> `organizations(id)`.
- `document_qr_codes.document_id` -> `documents(id)`.
- `document_qr_codes.created_by` -> `users(id)`.
- `document_qr_codes.updated_by` -> `users(id)`.
- `document_qr_codes.deleted_by` -> `users(id)`.
- `document_expiry_alerts.organization_id` -> `organizations(id)`.
- `document_expiry_alerts.document_id` -> `documents(id)`.
- `document_expiry_alerts.notified_user_id` -> `notified_users(id)`.
- `document_expiry_alerts.created_by` -> `users(id)`.
- `document_expiry_alerts.updated_by` -> `users(id)`.
- `document_expiry_alerts.deleted_by` -> `users(id)`.
- `document_access_logs.organization_id` -> `organizations(id)`.
- `document_access_logs.document_id` -> `documents(id)`.
- `document_access_logs.user_id` -> `users(id)`.
- `document_access_logs.created_by` -> `users(id)`.
- `document_access_logs.updated_by` -> `users(id)`.
- `document_access_logs.deleted_by` -> `users(id)`.
- `audit_programs.organization_id` -> `organizations(id)`.
- `audit_programs.fiscal_year_id` -> `fiscal_years(id)`.
- `audit_programs.created_by` -> `users(id)`.
- `audit_programs.updated_by` -> `users(id)`.
- `audit_programs.deleted_by` -> `users(id)`.
- `audit_plans.organization_id` -> `organizations(id)`.
- `audit_plans.audit_program_id` -> `audit_programs(id)`.
- `audit_plans.clinic_id` -> `clinics(id)`.
- `audit_plans.unit_id` -> `units(id)`.
- `audit_plans.lead_auditor_id` -> `users(id)`.
- `audit_plans.created_by` -> `users(id)`.
- `audit_plans.updated_by` -> `users(id)`.
- `audit_plans.deleted_by` -> `users(id)`.
- `audit_schedules.organization_id` -> `organizations(id)`.
- `audit_schedules.audit_plan_id` -> `audit_plans(id)`.
- `audit_schedules.created_by` -> `users(id)`.
- `audit_schedules.updated_by` -> `users(id)`.
- `audit_schedules.deleted_by` -> `users(id)`.
- `audit_teams.organization_id` -> `organizations(id)`.
- `audit_teams.audit_schedule_id` -> `audit_schedules(id)`.
- `audit_teams.lead_auditor_id` -> `users(id)`.
- `audit_teams.created_by` -> `users(id)`.
- `audit_teams.updated_by` -> `users(id)`.
- `audit_teams.deleted_by` -> `users(id)`.
- `audit_team_members.organization_id` -> `organizations(id)`.
- `audit_team_members.audit_team_id` -> `audit_teams(id)`.
- `audit_team_members.user_id` -> `users(id)`.
- `audit_team_members.created_by` -> `users(id)`.
- `audit_team_members.updated_by` -> `users(id)`.
- `audit_team_members.deleted_by` -> `users(id)`.
- `audit_checklist_items.organization_id` -> `organizations(id)`.
- `audit_checklist_items.audit_plan_id` -> `audit_plans(id)`.
- `audit_checklist_items.created_by` -> `users(id)`.
- `audit_checklist_items.updated_by` -> `users(id)`.
- `audit_checklist_items.deleted_by` -> `users(id)`.
- `audit_checklist_responses.organization_id` -> `organizations(id)`.
- `audit_checklist_responses.checklist_item_id` -> `checklist_items(id)`.
- `audit_checklist_responses.audit_schedule_id` -> `audit_schedules(id)`.
- `audit_checklist_responses.responded_by` -> `users(id)`.
- `audit_checklist_responses.created_by` -> `users(id)`.
- `audit_checklist_responses.updated_by` -> `users(id)`.
- `audit_checklist_responses.deleted_by` -> `users(id)`.
- `audit_findings.organization_id` -> `organizations(id)`.
- `audit_findings.audit_schedule_id` -> `audit_schedules(id)`.
- `audit_findings.clinic_id` -> `clinics(id)`.
- `audit_findings.created_by` -> `users(id)`.
- `audit_findings.updated_by` -> `users(id)`.
- `audit_findings.deleted_by` -> `users(id)`.
- `audit_reports.organization_id` -> `organizations(id)`.
- `audit_reports.audit_schedule_id` -> `audit_schedules(id)`.
- `audit_reports.approved_by` -> `users(id)`.
- `audit_reports.created_by` -> `users(id)`.
- `audit_reports.updated_by` -> `users(id)`.
- `audit_reports.deleted_by` -> `users(id)`.
- `audit_evidence.organization_id` -> `organizations(id)`.
- `audit_evidence.audit_finding_id` -> `audit_findings(id)`.
- `audit_evidence.attachment_id` -> `attachments(id)`.
- `audit_evidence.collected_by` -> `users(id)`.
- `audit_evidence.created_by` -> `users(id)`.
- `audit_evidence.updated_by` -> `users(id)`.
- `audit_evidence.deleted_by` -> `users(id)`.
- `audit_follow_ups.organization_id` -> `organizations(id)`.
- `audit_follow_ups.audit_finding_id` -> `audit_findings(id)`.
- `audit_follow_ups.created_by` -> `users(id)`.
- `audit_follow_ups.updated_by` -> `users(id)`.
- `audit_follow_ups.deleted_by` -> `users(id)`.
- `capa_cases.organization_id` -> `organizations(id)`.
- `capa_cases.clinic_id` -> `clinics(id)`.
- `capa_cases.source_id` -> `sources(id)`.
- `capa_cases.owner_id` -> `users(id)`.
- `capa_cases.created_by` -> `users(id)`.
- `capa_cases.updated_by` -> `users(id)`.
- `capa_cases.deleted_by` -> `users(id)`.
- `capa_root_cause_analyses.organization_id` -> `organizations(id)`.
- `capa_root_cause_analyses.capa_case_id` -> `capa_cases(id)`.
- `capa_root_cause_analyses.analyzed_by` -> `users(id)`.
- `capa_root_cause_analyses.created_by` -> `users(id)`.
- `capa_root_cause_analyses.updated_by` -> `users(id)`.
- `capa_root_cause_analyses.deleted_by` -> `users(id)`.
- `capa_five_whys.organization_id` -> `organizations(id)`.
- `capa_five_whys.rca_id` -> `rcas(id)`.
- `capa_five_whys.created_by` -> `users(id)`.
- `capa_five_whys.updated_by` -> `users(id)`.
- `capa_five_whys.deleted_by` -> `users(id)`.
- `capa_fishbone_diagrams.organization_id` -> `organizations(id)`.
- `capa_fishbone_diagrams.rca_id` -> `rcas(id)`.
- `capa_fishbone_diagrams.created_by` -> `users(id)`.
- `capa_fishbone_diagrams.updated_by` -> `users(id)`.
- `capa_fishbone_diagrams.deleted_by` -> `users(id)`.
- `capa_corrective_actions.organization_id` -> `organizations(id)`.
- `capa_corrective_actions.capa_case_id` -> `capa_cases(id)`.
- `capa_corrective_actions.responsible_id` -> `users(id)`.
- `capa_corrective_actions.created_by` -> `users(id)`.
- `capa_corrective_actions.updated_by` -> `users(id)`.
- `capa_corrective_actions.deleted_by` -> `users(id)`.
- `capa_preventive_actions.organization_id` -> `organizations(id)`.
- `capa_preventive_actions.capa_case_id` -> `capa_cases(id)`.
- `capa_preventive_actions.responsible_id` -> `users(id)`.
- `capa_preventive_actions.created_by` -> `users(id)`.
- `capa_preventive_actions.updated_by` -> `users(id)`.
- `capa_preventive_actions.deleted_by` -> `users(id)`.
- `capa_verifications.organization_id` -> `organizations(id)`.
- `capa_verifications.capa_case_id` -> `capa_cases(id)`.
- `capa_verifications.verified_by` -> `users(id)`.
- `capa_verifications.evidence_attachment_id` -> `evidence_attachments(id)`.
- `capa_verifications.created_by` -> `users(id)`.
- `capa_verifications.updated_by` -> `users(id)`.
- `capa_verifications.deleted_by` -> `users(id)`.
- `capa_effectiveness_reviews.organization_id` -> `organizations(id)`.
- `capa_effectiveness_reviews.capa_case_id` -> `capa_cases(id)`.
- `capa_effectiveness_reviews.reviewed_by` -> `users(id)`.
- `capa_effectiveness_reviews.created_by` -> `users(id)`.
- `capa_effectiveness_reviews.updated_by` -> `users(id)`.
- `capa_effectiveness_reviews.deleted_by` -> `users(id)`.
- `capa_status_histories.organization_id` -> `organizations(id)`.
- `capa_status_histories.capa_case_id` -> `capa_cases(id)`.
- `capa_status_histories.changed_by` -> `users(id)`.
- `capa_status_histories.created_by` -> `users(id)`.
- `capa_status_histories.updated_by` -> `users(id)`.
- `capa_status_histories.deleted_by` -> `users(id)`.
- `risk_categories.organization_id` -> `organizations(id)`.
- `risk_categories.created_by` -> `users(id)`.
- `risk_categories.updated_by` -> `users(id)`.
- `risk_categories.deleted_by` -> `users(id)`.
- `risk_registers.organization_id` -> `organizations(id)`.
- `risk_registers.clinic_id` -> `clinics(id)`.
- `risk_registers.fiscal_year_id` -> `fiscal_years(id)`.
- `risk_registers.owner_id` -> `users(id)`.
- `risk_registers.created_by` -> `users(id)`.
- `risk_registers.updated_by` -> `users(id)`.
- `risk_registers.deleted_by` -> `users(id)`.
- `risk_items.organization_id` -> `organizations(id)`.
- `risk_items.risk_register_id` -> `risk_registers(id)`.
- `risk_items.risk_category_id` -> `risk_categories(id)`.
- `risk_items.created_by` -> `users(id)`.
- `risk_items.updated_by` -> `users(id)`.
- `risk_items.deleted_by` -> `users(id)`.
- `risk_assessments.organization_id` -> `organizations(id)`.
- `risk_assessments.risk_item_id` -> `risk_items(id)`.
- `risk_assessments.assessed_by` -> `users(id)`.
- `risk_assessments.created_by` -> `users(id)`.
- `risk_assessments.updated_by` -> `users(id)`.
- `risk_assessments.deleted_by` -> `users(id)`.
- `risk_matrices.organization_id` -> `organizations(id)`.
- `risk_matrices.created_by` -> `users(id)`.
- `risk_matrices.updated_by` -> `users(id)`.
- `risk_matrices.deleted_by` -> `users(id)`.
- `risk_mitigation_plans.organization_id` -> `organizations(id)`.
- `risk_mitigation_plans.risk_item_id` -> `risk_items(id)`.
- `risk_mitigation_plans.responsible_id` -> `users(id)`.
- `risk_mitigation_plans.created_by` -> `users(id)`.
- `risk_mitigation_plans.updated_by` -> `users(id)`.
- `risk_mitigation_plans.deleted_by` -> `users(id)`.
- `risk_monitoring_logs.organization_id` -> `organizations(id)`.
- `risk_monitoring_logs.risk_item_id` -> `risk_items(id)`.
- `risk_monitoring_logs.monitored_by` -> `users(id)`.
- `risk_monitoring_logs.created_by` -> `users(id)`.
- `risk_monitoring_logs.updated_by` -> `users(id)`.
- `risk_monitoring_logs.deleted_by` -> `users(id)`.
- `risk_reviews.organization_id` -> `organizations(id)`.
- `risk_reviews.risk_register_id` -> `risk_registers(id)`.
- `risk_reviews.reviewed_by` -> `users(id)`.
- `risk_reviews.created_by` -> `users(id)`.
- `risk_reviews.updated_by` -> `users(id)`.
- `risk_reviews.deleted_by` -> `users(id)`.
- `accreditation_types.created_by` -> `users(id)`.
- `accreditation_types.updated_by` -> `users(id)`.
- `accreditation_types.deleted_by` -> `users(id)`.
- `accreditation_chapters.accreditation_type_id` -> `accreditation_types(id)`.
- `accreditation_chapters.created_by` -> `users(id)`.
- `accreditation_chapters.updated_by` -> `users(id)`.
- `accreditation_chapters.deleted_by` -> `users(id)`.
- `accreditation_standards.chapter_id` -> `chapters(id)`.
- `accreditation_standards.created_by` -> `users(id)`.
- `accreditation_standards.updated_by` -> `users(id)`.
- `accreditation_standards.deleted_by` -> `users(id)`.
- `accreditation_criteria.standard_id` -> `standards(id)`.
- `accreditation_criteria.created_by` -> `users(id)`.
- `accreditation_criteria.updated_by` -> `users(id)`.
- `accreditation_criteria.deleted_by` -> `users(id)`.
- `accreditation_elements.criteria_id` -> `criterias(id)`.
- `accreditation_elements.created_by` -> `users(id)`.
- `accreditation_elements.updated_by` -> `users(id)`.
- `accreditation_elements.deleted_by` -> `users(id)`.
- `accreditation_programs.organization_id` -> `organizations(id)`.
- `accreditation_programs.accreditation_type_id` -> `accreditation_types(id)`.
- `accreditation_programs.created_by` -> `users(id)`.
- `accreditation_programs.updated_by` -> `users(id)`.
- `accreditation_programs.deleted_by` -> `users(id)`.
- `accreditation_self_assessments.organization_id` -> `organizations(id)`.
- `accreditation_self_assessments.program_id` -> `programs(id)`.
- `accreditation_self_assessments.clinic_id` -> `clinics(id)`.
- `accreditation_self_assessments.assessor_id` -> `users(id)`.
- `accreditation_self_assessments.created_by` -> `users(id)`.
- `accreditation_self_assessments.updated_by` -> `users(id)`.
- `accreditation_self_assessments.deleted_by` -> `users(id)`.
- `accreditation_self_assessment_items.organization_id` -> `organizations(id)`.
- `accreditation_self_assessment_items.self_assessment_id` -> `self_assessments(id)`.
- `accreditation_self_assessment_items.element_id` -> `elements(id)`.
- `accreditation_self_assessment_items.created_by` -> `users(id)`.
- `accreditation_self_assessment_items.updated_by` -> `users(id)`.
- `accreditation_self_assessment_items.deleted_by` -> `users(id)`.
- `accreditation_gap_analyses.organization_id` -> `organizations(id)`.
- `accreditation_gap_analyses.self_assessment_id` -> `self_assessments(id)`.
- `accreditation_gap_analyses.analyzed_by` -> `users(id)`.
- `accreditation_gap_analyses.created_by` -> `users(id)`.
- `accreditation_gap_analyses.updated_by` -> `users(id)`.
- `accreditation_gap_analyses.deleted_by` -> `users(id)`.
- `accreditation_gap_items.organization_id` -> `organizations(id)`.
- `accreditation_gap_items.gap_analysis_id` -> `gap_analysiss(id)`.
- `accreditation_gap_items.element_id` -> `elements(id)`.
- `accreditation_gap_items.created_by` -> `users(id)`.
- `accreditation_gap_items.updated_by` -> `users(id)`.
- `accreditation_gap_items.deleted_by` -> `users(id)`.
- `accreditation_mock_surveys.organization_id` -> `organizations(id)`.
- `accreditation_mock_surveys.program_id` -> `programs(id)`.
- `accreditation_mock_surveys.clinic_id` -> `clinics(id)`.
- `accreditation_mock_surveys.lead_surveyor_id` -> `users(id)`.
- `accreditation_mock_surveys.created_by` -> `users(id)`.
- `accreditation_mock_surveys.updated_by` -> `users(id)`.
- `accreditation_mock_surveys.deleted_by` -> `users(id)`.
- `accreditation_evidence.organization_id` -> `organizations(id)`.
- `accreditation_evidence.clinic_id` -> `clinics(id)`.
- `accreditation_evidence.element_id` -> `elements(id)`.
- `accreditation_evidence.attachment_id` -> `attachments(id)`.
- `accreditation_evidence.uploaded_by` -> `users(id)`.
- `accreditation_evidence.created_by` -> `users(id)`.
- `accreditation_evidence.updated_by` -> `users(id)`.
- `accreditation_evidence.deleted_by` -> `users(id)`.
- `quality_programs.organization_id` -> `organizations(id)`.
- `quality_programs.fiscal_year_id` -> `fiscal_years(id)`.
- `quality_programs.program_owner_id` -> `users(id)`.
- `quality_programs.created_by` -> `users(id)`.
- `quality_programs.updated_by` -> `users(id)`.
- `quality_programs.deleted_by` -> `users(id)`.
- `quality_indicators.organization_id` -> `organizations(id)`.
- `quality_indicators.program_id` -> `programs(id)`.
- `quality_indicators.created_by` -> `users(id)`.
- `quality_indicators.updated_by` -> `users(id)`.
- `quality_indicators.deleted_by` -> `users(id)`.
- `quality_indicator_targets.organization_id` -> `organizations(id)`.
- `quality_indicator_targets.indicator_id` -> `indicators(id)`.
- `quality_indicator_targets.clinic_id` -> `clinics(id)`.
- `quality_indicator_targets.created_by` -> `users(id)`.
- `quality_indicator_targets.updated_by` -> `users(id)`.
- `quality_indicator_targets.deleted_by` -> `users(id)`.
- `quality_measurements.organization_id` -> `organizations(id)`.
- `quality_measurements.indicator_id` -> `indicators(id)`.
- `quality_measurements.clinic_id` -> `clinics(id)`.
- `quality_measurements.recorded_by` -> `users(id)`.
- `quality_measurements.created_by` -> `users(id)`.
- `quality_measurements.updated_by` -> `users(id)`.
- `quality_measurements.deleted_by` -> `users(id)`.
- `quality_monitoring_sessions.organization_id` -> `organizations(id)`.
- `quality_monitoring_sessions.program_id` -> `programs(id)`.
- `quality_monitoring_sessions.clinic_id` -> `clinics(id)`.
- `quality_monitoring_sessions.facilitator_id` -> `users(id)`.
- `quality_monitoring_sessions.created_by` -> `users(id)`.
- `quality_monitoring_sessions.updated_by` -> `users(id)`.
- `quality_monitoring_sessions.deleted_by` -> `users(id)`.
- `quality_evaluations.organization_id` -> `organizations(id)`.
- `quality_evaluations.program_id` -> `programs(id)`.
- `quality_evaluations.clinic_id` -> `clinics(id)`.
- `quality_evaluations.evaluator_id` -> `users(id)`.
- `quality_evaluations.created_by` -> `users(id)`.
- `quality_evaluations.updated_by` -> `users(id)`.
- `quality_evaluations.deleted_by` -> `users(id)`.
- `quality_reports.organization_id` -> `organizations(id)`.
- `quality_reports.program_id` -> `programs(id)`.
- `quality_reports.generated_by` -> `users(id)`.
- `quality_reports.created_by` -> `users(id)`.
- `quality_reports.updated_by` -> `users(id)`.
- `quality_reports.deleted_by` -> `users(id)`.
- `workflow_definitions.organization_id` -> `organizations(id)`.
- `workflow_definitions.created_by` -> `users(id)`.
- `workflow_definitions.updated_by` -> `users(id)`.
- `workflow_definitions.deleted_by` -> `users(id)`.
- `workflow_steps.organization_id` -> `organizations(id)`.
- `workflow_steps.workflow_definition_id` -> `workflow_definitions(id)`.
- `workflow_steps.required_role_id` -> `required_roles(id)`.
- `workflow_steps.created_by` -> `users(id)`.
- `workflow_steps.updated_by` -> `users(id)`.
- `workflow_steps.deleted_by` -> `users(id)`.
- `workflow_transitions.organization_id` -> `organizations(id)`.
- `workflow_transitions.workflow_definition_id` -> `workflow_definitions(id)`.
- `workflow_transitions.from_step_id` -> `from_steps(id)`.
- `workflow_transitions.to_step_id` -> `to_steps(id)`.
- `workflow_transitions.created_by` -> `users(id)`.
- `workflow_transitions.updated_by` -> `users(id)`.
- `workflow_transitions.deleted_by` -> `users(id)`.
- `workflow_instances.organization_id` -> `organizations(id)`.
- `workflow_instances.workflow_definition_id` -> `workflow_definitions(id)`.
- `workflow_instances.subject_id` -> `subjects(id)`.
- `workflow_instances.current_step_id` -> `current_steps(id)`.
- `workflow_instances.initiated_by` -> `users(id)`.
- `workflow_instances.created_by` -> `users(id)`.
- `workflow_instances.updated_by` -> `users(id)`.
- `workflow_instances.deleted_by` -> `users(id)`.
- `workflow_instance_steps.organization_id` -> `organizations(id)`.
- `workflow_instance_steps.workflow_instance_id` -> `workflow_instances(id)`.
- `workflow_instance_steps.workflow_step_id` -> `workflow_steps(id)`.
- `workflow_instance_steps.assignee_id` -> `users(id)`.
- `workflow_instance_steps.created_by` -> `users(id)`.
- `workflow_instance_steps.updated_by` -> `users(id)`.
- `workflow_instance_steps.deleted_by` -> `users(id)`.
- `workflow_assignments.organization_id` -> `organizations(id)`.
- `workflow_assignments.instance_step_id` -> `instance_steps(id)`.
- `workflow_assignments.assignee_id` -> `users(id)`.
- `workflow_assignments.assigned_by` -> `users(id)`.
- `workflow_assignments.created_by` -> `users(id)`.
- `workflow_assignments.updated_by` -> `users(id)`.
- `workflow_assignments.deleted_by` -> `users(id)`.
- `workflow_approvals.organization_id` -> `organizations(id)`.
- `workflow_approvals.instance_step_id` -> `instance_steps(id)`.
- `workflow_approvals.approver_id` -> `users(id)`.
- `workflow_approvals.created_by` -> `users(id)`.
- `workflow_approvals.updated_by` -> `users(id)`.
- `workflow_approvals.deleted_by` -> `users(id)`.
- `workflow_histories.organization_id` -> `organizations(id)`.
- `workflow_histories.workflow_instance_id` -> `workflow_instances(id)`.
- `workflow_histories.from_step_id` -> `from_steps(id)`.
- `workflow_histories.to_step_id` -> `to_steps(id)`.
- `workflow_histories.action_by` -> `users(id)`.
- `workflow_histories.created_by` -> `users(id)`.
- `workflow_histories.updated_by` -> `users(id)`.
- `workflow_histories.deleted_by` -> `users(id)`.
- `notification_channels.created_by` -> `users(id)`.
- `notification_channels.updated_by` -> `users(id)`.
- `notification_channels.deleted_by` -> `users(id)`.
- `notification_templates.organization_id` -> `organizations(id)`.
- `notification_templates.channel_id` -> `channels(id)`.
- `notification_templates.created_by` -> `users(id)`.
- `notification_templates.updated_by` -> `users(id)`.
- `notification_templates.deleted_by` -> `users(id)`.
- `notifications.organization_id` -> `organizations(id)`.
- `notifications.clinic_id` -> `clinics(id)`.
- `notifications.triggered_by` -> `users(id)`.
- `notifications.created_by` -> `users(id)`.
- `notifications.updated_by` -> `users(id)`.
- `notifications.deleted_by` -> `users(id)`.
- `notification_recipients.organization_id` -> `organizations(id)`.
- `notification_recipients.notification_id` -> `notifications(id)`.
- `notification_recipients.user_id` -> `users(id)`.
- `notification_recipients.created_by` -> `users(id)`.
- `notification_recipients.updated_by` -> `users(id)`.
- `notification_recipients.deleted_by` -> `users(id)`.
- `notification_preferences.organization_id` -> `organizations(id)`.
- `notification_preferences.user_id` -> `users(id)`.
- `notification_preferences.channel_id` -> `channels(id)`.
- `notification_preferences.created_by` -> `users(id)`.
- `notification_preferences.updated_by` -> `users(id)`.
- `notification_preferences.deleted_by` -> `users(id)`.
- `notification_logs.organization_id` -> `organizations(id)`.
- `notification_logs.notification_id` -> `notifications(id)`.
- `notification_logs.channel_id` -> `channels(id)`.
- `notification_logs.created_by` -> `users(id)`.
- `notification_logs.updated_by` -> `users(id)`.
- `notification_logs.deleted_by` -> `users(id)`.
- `jobs.organization_id` -> `organizations(id)`.
- `jobs.clinic_id` -> `clinics(id)`.
- `jobs.created_by` -> `users(id)`.
- `jobs.updated_by` -> `users(id)`.
- `jobs.deleted_by` -> `users(id)`.
- `job_batches.organization_id` -> `organizations(id)`.
- `job_batches.created_by` -> `users(id)`.
- `job_batches.updated_by` -> `users(id)`.
- `job_batches.deleted_by` -> `users(id)`.
- `failed_jobs.organization_id` -> `organizations(id)`.
- `failed_jobs.clinic_id` -> `clinics(id)`.
- `failed_jobs.created_by` -> `users(id)`.
- `failed_jobs.updated_by` -> `users(id)`.
- `failed_jobs.deleted_by` -> `users(id)`.
- `migrations_meta.executed_by` -> `users(id)`.
- `outbox_events.organization_id` -> `organizations(id)`.
- `outbox_events.clinic_id` -> `clinics(id)`.
- `outbox_events.aggregate_id` -> `aggregates(id)`.
- `outbox_events.created_by` -> `users(id)`.
- `outbox_events.updated_by` -> `users(id)`.
- `outbox_events.deleted_by` -> `users(id)`.
- `integration_logs.organization_id` -> `organizations(id)`.
- `integration_logs.clinic_id` -> `clinics(id)`.
- `integration_logs.created_by` -> `users(id)`.
- `integration_logs.updated_by` -> `users(id)`.
- `integration_logs.deleted_by` -> `users(id)`.

## 9. Strategi Soft Delete
Semua tabel bisnis menggunakan `deleted_at`, `deleted_by`, dan partial index `WHERE deleted_at IS NULL`. Query aplikasi wajib menambahkan kondisi `deleted_at IS NULL` kecuali pada layar audit, restore, atau administrasi retensi. Unique constraint bisnis harus dibuat sebagai partial unique index agar nilai yang sudah soft deleted tidak menghambat pembuatan ulang data aktif.

Contoh pola partial unique:
```sql
CREATE UNIQUE INDEX ux_documents_org_number_active
ON documents (organization_id, document_number)
WHERE deleted_at IS NULL;
```

## 10. Strategi Indeks Enterprise
Indeks dikelompokkan menjadi indeks tenant, indeks lookup master, indeks workflow/status, indeks pencarian, dan indeks JSONB. Tujuannya menjaga performa dashboard klinik, daftar kerja, laporan audit, pelacakan dokumen, serta pencarian evidence akreditasi.

### 10.1. Indeks Domain Core Platform
- `organizations`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `organizations`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `clinics`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `clinics`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `departments`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `departments`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `units`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `units`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `units`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `employees`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `employees`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `employees`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `reference_data`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `reference_data`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `reference_data_values`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `reference_data_values`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `fiscal_years`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `fiscal_years`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `fiscal_years`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `system_settings`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `system_settings`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `organization_settings`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `organization_settings`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `clinic_settings`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `clinic_settings`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `clinic_settings`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `audit_trails`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `audit_trails`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `activity_logs`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `activity_logs`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `activity_logs`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `attachments`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `attachments`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.

### 10.2. Indeks Domain Authentication & RBAC
- `users`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `users`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `roles`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `roles`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `permissions`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `role_permissions`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `user_roles`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `user_roles`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `user_roles`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `user_clinic_scopes`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `user_clinic_scopes`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `user_clinic_scopes`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `ldap_configurations`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `ldap_configurations`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `ldap_sync_logs`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `ldap_sync_logs`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `ldap_sync_logs`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `login_attempts`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `sessions`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `personal_access_tokens`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `personal_access_tokens`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.

### 10.3. Indeks Domain Document Management System
- `document_categories`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `document_categories`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `document_types`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `document_types`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `documents`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `documents`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `documents`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `documents`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `document_versions`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `document_versions`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `document_reviews`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `document_reviews`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `document_reviews`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `document_approvals`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `document_approvals`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `document_approvals`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `document_publications`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `document_publications`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `document_distributions`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `document_distributions`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `document_acknowledgments`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `document_acknowledgments`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `document_acknowledgments`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `document_qr_codes`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `document_qr_codes`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `document_expiry_alerts`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `document_expiry_alerts`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `document_access_logs`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `document_access_logs`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.

### 10.4. Indeks Domain Audit Internal
- `audit_programs`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `audit_programs`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `audit_programs`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `audit_plans`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `audit_plans`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `audit_plans`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `audit_plans`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `audit_schedules`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `audit_schedules`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `audit_schedules`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `audit_teams`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `audit_teams`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `audit_team_members`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `audit_team_members`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `audit_checklist_items`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `audit_checklist_items`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `audit_checklist_responses`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `audit_checklist_responses`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `audit_findings`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `audit_findings`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `audit_findings`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `audit_findings`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `audit_reports`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `audit_reports`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `audit_reports`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `audit_evidence`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `audit_evidence`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `audit_follow_ups`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `audit_follow_ups`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `audit_follow_ups`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.

### 10.5. Indeks Domain Corrective and Preventive Action
- `capa_cases`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `capa_cases`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `capa_cases`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `capa_cases`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `capa_root_cause_analyses`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `capa_root_cause_analyses`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `capa_five_whys`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `capa_five_whys`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `capa_fishbone_diagrams`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `capa_fishbone_diagrams`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `capa_corrective_actions`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `capa_corrective_actions`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `capa_corrective_actions`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `capa_preventive_actions`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `capa_preventive_actions`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `capa_preventive_actions`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `capa_verifications`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `capa_verifications`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `capa_effectiveness_reviews`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `capa_effectiveness_reviews`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `capa_status_histories`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `capa_status_histories`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.

### 10.6. Indeks Domain Risk Management
- `risk_categories`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `risk_categories`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `risk_registers`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `risk_registers`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `risk_registers`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `risk_registers`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `risk_items`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `risk_items`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `risk_items`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `risk_assessments`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `risk_assessments`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `risk_matrices`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `risk_matrices`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `risk_mitigation_plans`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `risk_mitigation_plans`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `risk_mitigation_plans`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `risk_monitoring_logs`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `risk_monitoring_logs`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `risk_reviews`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `risk_reviews`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.

### 10.7. Indeks Domain Akreditasi
- `accreditation_types`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `accreditation_chapters`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `accreditation_standards`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `accreditation_criteria`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `accreditation_elements`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `accreditation_programs`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `accreditation_programs`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `accreditation_programs`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `accreditation_self_assessments`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `accreditation_self_assessments`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `accreditation_self_assessments`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `accreditation_self_assessments`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `accreditation_self_assessment_items`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `accreditation_self_assessment_items`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `accreditation_gap_analyses`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `accreditation_gap_analyses`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `accreditation_gap_analyses`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `accreditation_gap_items`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `accreditation_gap_items`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `accreditation_mock_surveys`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `accreditation_mock_surveys`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `accreditation_mock_surveys`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `accreditation_mock_surveys`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `accreditation_evidence`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `accreditation_evidence`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `accreditation_evidence`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.

### 10.8. Indeks Domain Manajemen Mutu
- `quality_programs`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `quality_programs`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `quality_programs`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `quality_indicators`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `quality_indicators`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `quality_indicator_targets`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `quality_indicator_targets`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `quality_indicator_targets`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `quality_measurements`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `quality_measurements`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `quality_measurements`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `quality_monitoring_sessions`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `quality_monitoring_sessions`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `quality_monitoring_sessions`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `quality_monitoring_sessions`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `quality_evaluations`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `quality_evaluations`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `quality_evaluations`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `quality_reports`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `quality_reports`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.

### 10.9. Indeks Domain Workflow Engine
- `workflow_definitions`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `workflow_definitions`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `workflow_steps`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `workflow_steps`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `workflow_transitions`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `workflow_transitions`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `workflow_instances`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `workflow_instances`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `workflow_instances`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `workflow_instance_steps`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `workflow_instance_steps`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `workflow_instance_steps`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `workflow_assignments`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `workflow_assignments`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `workflow_approvals`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `workflow_approvals`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `workflow_histories`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `workflow_histories`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.

### 10.10. Indeks Domain Notification
- `notification_channels`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `notification_templates`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `notification_templates`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `notifications`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `notifications`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `notifications`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `notification_recipients`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `notification_recipients`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `notification_preferences`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `notification_preferences`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `notification_logs`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `notification_logs`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `notification_logs`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.

### 10.11. Indeks Domain System Operations
- `jobs`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `jobs`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `jobs`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `job_batches`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `job_batches`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `failed_jobs`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `failed_jobs`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `failed_jobs`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `outbox_events`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `outbox_events`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `outbox_events`: btree (`status`, `updated_at`) untuk monitoring status dan backlog.
- `outbox_events`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.
- `integration_logs`: composite btree (`organization_id`, `deleted_at`) sebagai akses tenant aktif.
- `integration_logs`: composite btree (`organization_id`, `clinic_id`, `deleted_at`) untuk query klinik.
- `integration_logs`: GIN (`metadata`) hanya untuk filter JSONB yang benar-benar dipakai.

Indeks full text dapat ditambahkan pada `documents.title`, `document_versions.content_html`, `audit_findings.description`, `capa_cases.problem_statement`, dan `accreditation_evidence.title` menggunakan generated `tsvector` bahasa Indonesia bila kebutuhan pencarian meningkat.

## 11. Strategi Partisi
Partisi diterapkan selektif pada tabel volume tinggi dan time-series. Tabel master tidak dipartisi. Kunci partisi utama adalah waktu kejadian, sedangkan `organization_id` dan `clinic_id` tetap menjadi bagian indeks lokal partisi.
- `audit_trails`: RANGE (`created_at`) bulanan. Alasan: Volume tinggi, query audit historis, retensi kepatuhan.
- `activity_logs`: RANGE (`created_at`) bulanan. Alasan: Log aktivitas pengguna dan subjek aplikasi.
- `document_access_logs`: RANGE (`accessed_at`) bulanan. Alasan: Akses dokumen dapat sangat tinggi setelah publikasi dokumen.
- `login_attempts`: RANGE (`attempted_at`) bulanan. Alasan: Keamanan autentikasi dan deteksi brute force.
- `notification_logs`: RANGE (`attempted_at`) bulanan. Alasan: Log provider email/in-app/SMS.
- `quality_measurements`: RANGE (`measurement_date`) tahunan atau kuartalan. Alasan: Pelaporan indikator mutu periodik.
- `risk_monitoring_logs`: RANGE (`monitoring_date`) tahunan. Alasan: Monitoring risiko periodik.
- `jobs`: LIST (`queue`) opsional. Alasan: Isolasi queue berat seperti report generation dan notification.

Contoh partisi audit trail:
```sql
CREATE TABLE audit_trails_2026_06 PARTITION OF audit_trails
FOR VALUES FROM ('2026-06-01') TO ('2026-07-01');
CREATE INDEX ix_audit_trails_2026_06_tenant ON audit_trails_2026_06 (organization_id, user_id, created_at DESC);
```

## 12. Strategi Audit Trail
Audit trail dirancang dua lapis. Lapisan pertama adalah `audit_trails` untuk perubahan data yang menyimpan `old_values` dan `new_values` dalam JSONB. Lapisan kedua adalah `activity_logs` untuk aktivitas pengguna yang bermakna secara bisnis seperti login, melihat dokumen, menyetujui workflow, menutup CAPA, atau mengunggah evidence.

Kebijakan audit:
- Event wajib: created, updated, deleted, restored, approved, rejected, published, closed, verified, login_success, login_failed.
- Payload audit harus menyertakan primary key, tenant, actor, IP address, user agent, timestamp server, dan correlation id bila tersedia.
- Kolom sensitif seperti password, token, bind password, dan secret harus dimasking sebelum masuk `old_values` atau `new_values`.
- Audit tidak boleh ikut soft delete; retensi minimal mengikuti kebijakan arsip dan kepatuhan organisasi.
- Untuk tabel terpartisi, retention job menutup partisi lama dengan arsip sebelum detach/drop.

Contoh trigger audit konseptual:
```sql
CREATE OR REPLACE FUNCTION fn_write_audit_trail()
RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
  INSERT INTO audit_trails (id, organization_id, user_id, auditable_type, auditable_id, event, old_values, new_values, created_at)
  VALUES (gen_random_uuid(), COALESCE(NEW.organization_id, OLD.organization_id), current_setting('app.user_id', true)::uuid,
          TG_TABLE_NAME, COALESCE(NEW.id, OLD.id), lower(TG_OP), to_jsonb(OLD), to_jsonb(NEW), now());
  RETURN COALESCE(NEW, OLD);
END;
$$;
```

## 13. SQL DDL Contoh Tabel Kunci
### 13.1. `organizations`
```sql
CREATE TABLE organizations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(255) NOT NULL,
    org_type VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(255) NOT NULL,
    email VARCHAR(320) NOT NULL,
    is_active BOOLEAN NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    created_by UUID NOT NULL,
    updated_by UUID NOT NULL,
    deleted_at TIMESTAMPTZ,
    deleted_by UUID,
    row_version INTEGER NOT NULL DEFAULT 1,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb
);
```

### 13.2. `clinics`
```sql
CREATE TABLE clinics (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(255) NOT NULL,
    clinic_type VARCHAR(255) NOT NULL,
    is_main_clinic BOOLEAN NOT NULL,
    accreditation_status VARCHAR(255) NOT NULL,
    is_active BOOLEAN NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ,
    created_by UUID,
    updated_by UUID,
    deleted_by UUID,
    row_version INTEGER NOT NULL DEFAULT 1,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb
);
```

### 13.3. `users`
```sql
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL,
    employee_id UUID,
    email VARCHAR(320) NOT NULL,
    password VARCHAR(255),
    ldap_guid VARCHAR(255) NOT NULL,
    auth_provider VARCHAR(255) NOT NULL,
    is_active BOOLEAN NOT NULL,
    force_password_change BOOLEAN NOT NULL,
    email_verified_at TIMESTAMPTZ NOT NULL,
    last_login_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ,
    created_by UUID,
    updated_by UUID,
    deleted_by UUID,
    row_version INTEGER NOT NULL DEFAULT 1
);
```

### 13.4. `documents`
```sql
CREATE TABLE documents (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL,
    clinic_id UUID,
    document_type_id UUID NOT NULL,
    workflow_instance_id UUID,
    document_number VARCHAR(80) NOT NULL,
    title VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    effective_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    owner_id UUID NOT NULL,
    current_version_id UUID,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ,
    created_by UUID,
    updated_by UUID,
    deleted_by UUID
);
```

### 13.5. `document_versions`
```sql
CREATE TABLE document_versions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL,
    document_id UUID NOT NULL,
    version_label VARCHAR(255) NOT NULL,
    version_number INTEGER NOT NULL,
    change_summary TEXT,
    content_html TEXT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    checksum_sha256 CHAR(64) NOT NULL,
    created_by UUID NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ,
    updated_by UUID,
    deleted_by UUID,
    row_version INTEGER NOT NULL DEFAULT 1,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb
);
```

### 13.6. `audit_findings`
```sql
CREATE TABLE audit_findings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL,
    audit_schedule_id UUID NOT NULL,
    clinic_id UUID,
    finding_code VARCHAR(80) NOT NULL,
    severity VARCHAR(50) NOT NULL,
    description TEXT,
    requirement_reference TEXT NOT NULL,
    status VARCHAR(50) NOT NULL,
    assigned_to UUID NOT NULL,
    identified_at TIMESTAMPTZ NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ,
    created_by UUID,
    updated_by UUID,
    deleted_by UUID,
    row_version INTEGER NOT NULL DEFAULT 1
);
```

### 13.7. `capa_cases`
```sql
CREATE TABLE capa_cases (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL,
    clinic_id UUID,
    case_number VARCHAR(80) NOT NULL,
    source_type VARCHAR(255) NOT NULL,
    source_id UUID NOT NULL,
    priority VARCHAR(50) NOT NULL,
    problem_statement TEXT NOT NULL,
    status VARCHAR(50) NOT NULL,
    owner_id UUID NOT NULL,
    target_close_date DATE NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ,
    created_by UUID,
    updated_by UUID,
    deleted_by UUID,
    row_version INTEGER NOT NULL DEFAULT 1
);
```

### 13.8. `risk_items`
```sql
CREATE TABLE risk_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL,
    risk_register_id UUID NOT NULL,
    risk_category_id UUID NOT NULL,
    risk_code VARCHAR(80) NOT NULL,
    risk_description TEXT NOT NULL,
    risk_source TEXT NOT NULL,
    inherent_likelihood INTEGER NOT NULL,
    inherent_impact INTEGER NOT NULL,
    inherent_score INTEGER NOT NULL,
    residual_likelihood INTEGER NOT NULL,
    residual_impact INTEGER NOT NULL,
    residual_score INTEGER NOT NULL,
    risk_level VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ
);
```

### 13.9. `accreditation_self_assessments`
```sql
CREATE TABLE accreditation_self_assessments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL,
    program_id UUID NOT NULL,
    clinic_id UUID,
    assessment_date DATE NOT NULL,
    assessor_id UUID NOT NULL,
    overall_score NUMERIC(12,2) NOT NULL,
    status VARCHAR(50) NOT NULL,
    summary TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ,
    created_by UUID,
    updated_by UUID,
    deleted_by UUID,
    row_version INTEGER NOT NULL DEFAULT 1,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb
);
```

### 13.10. `quality_measurements`
```sql
CREATE TABLE quality_measurements (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL,
    indicator_id UUID NOT NULL,
    clinic_id UUID,
    measurement_date DATE NOT NULL,
    measured_value NUMERIC(12,2) NOT NULL,
    period_label VARCHAR(255) NOT NULL,
    recorded_by UUID NOT NULL,
    notes TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ,
    created_by UUID,
    updated_by UUID,
    deleted_by UUID,
    row_version INTEGER NOT NULL DEFAULT 1,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb
);
```

### 13.11. `workflow_instances`
```sql
CREATE TABLE workflow_instances (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL,
    workflow_definition_id UUID NOT NULL,
    subject_type VARCHAR(255) NOT NULL,
    subject_id UUID NOT NULL,
    current_step_id UUID,
    status VARCHAR(50) NOT NULL,
    initiated_by UUID NOT NULL,
    started_at TIMESTAMPTZ NOT NULL,
    completed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ,
    created_by UUID,
    updated_by UUID,
    deleted_by UUID,
    row_version INTEGER NOT NULL DEFAULT 1,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb
);
```

### 13.12. `notifications`
```sql
CREATE TABLE notifications (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL,
    clinic_id UUID,
    type VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    data JSONB NOT NULL DEFAULT '{}'::jsonb,
    priority VARCHAR(50) NOT NULL,
    sent_at TIMESTAMPTZ NOT NULL,
    triggered_by UUID NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ,
    created_by UUID,
    updated_by UUID,
    deleted_by UUID,
    row_version INTEGER NOT NULL DEFAULT 1,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb
);
```

### 13.13. `audit_trails`
```sql
CREATE TABLE audit_trails (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL,
    user_id UUID NOT NULL,
    auditable_type VARCHAR(255) NOT NULL,
    auditable_id UUID NOT NULL,
    event VARCHAR(255) NOT NULL,
    old_values JSONB NOT NULL DEFAULT '{}'::jsonb,
    new_values JSONB NOT NULL DEFAULT '{}'::jsonb,
    ip_address VARCHAR(255) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ,
    created_by UUID,
    updated_by UUID,
    deleted_by UUID,
    row_version INTEGER NOT NULL DEFAULT 1,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb
);
```

### 13.14. `jobs`
```sql
CREATE TABLE jobs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL,
    clinic_id UUID,
    queue VARCHAR(255) NOT NULL,
    payload JSONB NOT NULL DEFAULT '{}'::jsonb,
    attempts INTEGER NOT NULL,
    reserved_at TIMESTAMPTZ NOT NULL,
    available_at TIMESTAMPTZ NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ,
    created_by UUID,
    updated_by UUID,
    deleted_by UUID,
    row_version INTEGER NOT NULL DEFAULT 1,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb
);
```

### 13.15. `migrations_meta`
```sql
CREATE TABLE migrations_meta (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL,
    checksum_sha256 CHAR(64) NOT NULL,
    executed_by UUID NOT NULL,
    executed_at TIMESTAMPTZ NOT NULL
);
```

## 14. Keamanan Data
- Row Level Security dapat diaktifkan untuk tabel multi-tenant dengan policy berbasis `current_setting('app.organization_id')` dan scope klinik pengguna.
- Data kredensial seperti LDAP bind password, token, dan secret harus disimpan terenkripsi di application layer atau extension yang disetujui.
- Akses lintas klinik hanya melalui `user_clinic_scopes` dan role dengan scope `global` atau `clinic`.
- Semua query laporan harus memakai view atau stored query yang menghormati `organization_id`, `clinic_id`, dan soft delete.
- Backup database harus terenkripsi dan diuji restore secara berkala.

## 15. Migration dan Release Management
Migration disimpan secara idempotent dan dicatat di `migrations_meta` dengan checksum. Perubahan destructive harus melalui migration bertahap: tambah kolom nullable, backfill, validasi, ubah menjadi NOT NULL, lalu bersihkan kolom lama setelah masa kompatibilitas selesai. Index besar dibuat dengan `CREATE INDEX CONCURRENTLY` pada lingkungan produksi.

## 16. Standar Validasi Data
- Status enum awal disimpan sebagai `VARCHAR(50)` dengan check constraint agar tetap fleksibel saat domain berkembang.
- Nomor bisnis seperti nomor dokumen, temuan, CAPA, risiko, dan laporan harus unique dalam scope `organization_id` dan tahun/klinik bila relevan.
- Tanggal `expiry_date` tidak boleh lebih kecil dari `effective_date`; due date tidak boleh lebih kecil dari tanggal pembuatan aksi.
- Skor risiko dan akreditasi divalidasi dalam rentang yang disepakati, misalnya 1-5 untuk likelihood/impact dan 0-100 untuk skor akreditasi.
- JSONB configuration wajib memiliki schema validasi aplikasi dan migration test.

## 17. Retensi dan Arsip
Retensi mengikuti nilai `retention_days` pada kategori dokumen, kebijakan organisasi, dan kebutuhan audit. Dokumen versi, audit trail, bukti akreditasi, dan laporan audit adalah record bernilai kepatuhan tinggi sehingga penghapusan fisik harus melalui approval. Log teknis seperti notification log, login attempt, dan job failure dapat dipurge setelah diringkas sesuai kebijakan keamanan.

## 18. Catatan Implementasi
- Aktifkan extension `pgcrypto` untuk `gen_random_uuid()`.
- Gunakan transaksi eksplisit untuk perubahan dokumen, workflow, dan audit trail agar status tidak terpisah dari event audit.
- Gunakan advisory lock atau unique index untuk generator nomor dokumen, temuan audit, CAPA, dan risiko.
- Gunakan outbox pattern untuk notifikasi dan integrasi agar event tidak hilang saat provider eksternal gagal.
- Sediakan seed data awal untuk organisasi PPKP, 17 klinik, role sistem, permission, tipe dokumen, matriks risiko, dan template notifikasi.

## 19. Lampiran A - Checklist Kesiapan Implementasi
1. Seluruh tabel memiliki primary key UUID.
2. Tabel bisnis memiliki `organization_id`.
3. Tabel operasional klinik memiliki `clinic_id`.
4. Tabel operasional unit memiliki `unit_id` bila relevan.
5. Soft delete tersedia untuk record bisnis.
6. Audit trail tersedia untuk perubahan penting.
7. Index tenant aktif tersedia.
8. Partial unique index tersedia untuk business key.
9. Partisi tersedia untuk log volume tinggi.
10. Migration metadata tercatat dengan checksum.
11. RLS atau filter tenant wajib diterapkan di repository/service layer.
12. Secret tidak pernah masuk audit payload tanpa masking.

## 20. Penutup
Desain database ini menyediakan fondasi enterprise untuk SIMMAK PPKP dengan cakupan lintas mutu, audit, CAPA, risiko, akreditasi, dokumen, workflow, notifikasi, dan operasi sistem. Implementasi migration harus menjaga konsistensi tenant, integritas FK, auditability, dan performa query klinik sebagai prioritas utama.

## 21. Lampiran B - Matriks Governance Per Tabel
### Governance `organizations`
- Domain: Core.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Core dan kebijakan arsip PPKP.

### Governance `clinics`
- Domain: Core.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Core dan kebijakan arsip PPKP.

### Governance `departments`
- Domain: Core.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Core dan kebijakan arsip PPKP.

### Governance `units`
- Domain: Core.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Core dan kebijakan arsip PPKP.

### Governance `employees`
- Domain: Core.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Core dan kebijakan arsip PPKP.

### Governance `reference_data`
- Domain: Core.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Core dan kebijakan arsip PPKP.

### Governance `reference_data_values`
- Domain: Core.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Core dan kebijakan arsip PPKP.

### Governance `fiscal_years`
- Domain: Core.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Core dan kebijakan arsip PPKP.

### Governance `system_settings`
- Domain: Core.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Core dan kebijakan arsip PPKP.

### Governance `organization_settings`
- Domain: Core.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Core dan kebijakan arsip PPKP.

### Governance `clinic_settings`
- Domain: Core.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Core dan kebijakan arsip PPKP.

### Governance `audit_trails`
- Domain: Core.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Core dan kebijakan arsip PPKP.

### Governance `activity_logs`
- Domain: Core.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Core dan kebijakan arsip PPKP.

### Governance `attachments`
- Domain: Core.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Core dan kebijakan arsip PPKP.

### Governance `users`
- Domain: Auth.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Auth dan kebijakan arsip PPKP.

### Governance `roles`
- Domain: Auth.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Auth dan kebijakan arsip PPKP.

### Governance `permissions`
- Domain: Auth.
- Tenant filter: global/system reference.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Auth dan kebijakan arsip PPKP.

### Governance `role_permissions`
- Domain: Auth.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: tidak digunakan untuk tabel teknis ini.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Auth dan kebijakan arsip PPKP.

### Governance `user_roles`
- Domain: Auth.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Auth dan kebijakan arsip PPKP.

### Governance `user_clinic_scopes`
- Domain: Auth.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Auth dan kebijakan arsip PPKP.

### Governance `ldap_configurations`
- Domain: Auth.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Auth dan kebijakan arsip PPKP.

### Governance `ldap_sync_logs`
- Domain: Auth.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Auth dan kebijakan arsip PPKP.

### Governance `login_attempts`
- Domain: Auth.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: tidak digunakan untuk tabel teknis ini.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Auth dan kebijakan arsip PPKP.

### Governance `sessions`
- Domain: Auth.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: tidak digunakan untuk tabel teknis ini.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Auth dan kebijakan arsip PPKP.

### Governance `personal_access_tokens`
- Domain: Auth.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Auth dan kebijakan arsip PPKP.

### Governance `document_categories`
- Domain: DMS.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain DMS dan kebijakan arsip PPKP.

### Governance `document_types`
- Domain: DMS.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain DMS dan kebijakan arsip PPKP.

### Governance `documents`
- Domain: DMS.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain DMS dan kebijakan arsip PPKP.

### Governance `document_versions`
- Domain: DMS.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain DMS dan kebijakan arsip PPKP.

### Governance `document_reviews`
- Domain: DMS.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain DMS dan kebijakan arsip PPKP.

### Governance `document_approvals`
- Domain: DMS.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain DMS dan kebijakan arsip PPKP.

### Governance `document_publications`
- Domain: DMS.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain DMS dan kebijakan arsip PPKP.

### Governance `document_distributions`
- Domain: DMS.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain DMS dan kebijakan arsip PPKP.

### Governance `document_acknowledgments`
- Domain: DMS.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain DMS dan kebijakan arsip PPKP.

### Governance `document_qr_codes`
- Domain: DMS.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain DMS dan kebijakan arsip PPKP.

### Governance `document_expiry_alerts`
- Domain: DMS.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain DMS dan kebijakan arsip PPKP.

### Governance `document_access_logs`
- Domain: DMS.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain DMS dan kebijakan arsip PPKP.

### Governance `audit_programs`
- Domain: Audit.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Audit dan kebijakan arsip PPKP.

### Governance `audit_plans`
- Domain: Audit.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Audit dan kebijakan arsip PPKP.

### Governance `audit_schedules`
- Domain: Audit.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Audit dan kebijakan arsip PPKP.

### Governance `audit_teams`
- Domain: Audit.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Audit dan kebijakan arsip PPKP.

### Governance `audit_team_members`
- Domain: Audit.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Audit dan kebijakan arsip PPKP.

### Governance `audit_checklist_items`
- Domain: Audit.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Audit dan kebijakan arsip PPKP.

### Governance `audit_checklist_responses`
- Domain: Audit.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Audit dan kebijakan arsip PPKP.

### Governance `audit_findings`
- Domain: Audit.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Audit dan kebijakan arsip PPKP.

### Governance `audit_reports`
- Domain: Audit.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Audit dan kebijakan arsip PPKP.

### Governance `audit_evidence`
- Domain: Audit.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Audit dan kebijakan arsip PPKP.

### Governance `audit_follow_ups`
- Domain: Audit.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Audit dan kebijakan arsip PPKP.

### Governance `capa_cases`
- Domain: CAPA.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain CAPA dan kebijakan arsip PPKP.

### Governance `capa_root_cause_analyses`
- Domain: CAPA.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain CAPA dan kebijakan arsip PPKP.

### Governance `capa_five_whys`
- Domain: CAPA.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain CAPA dan kebijakan arsip PPKP.

### Governance `capa_fishbone_diagrams`
- Domain: CAPA.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain CAPA dan kebijakan arsip PPKP.

### Governance `capa_corrective_actions`
- Domain: CAPA.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain CAPA dan kebijakan arsip PPKP.

### Governance `capa_preventive_actions`
- Domain: CAPA.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain CAPA dan kebijakan arsip PPKP.

### Governance `capa_verifications`
- Domain: CAPA.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain CAPA dan kebijakan arsip PPKP.

### Governance `capa_effectiveness_reviews`
- Domain: CAPA.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain CAPA dan kebijakan arsip PPKP.

### Governance `capa_status_histories`
- Domain: CAPA.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain CAPA dan kebijakan arsip PPKP.

### Governance `risk_categories`
- Domain: Risk.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Risk dan kebijakan arsip PPKP.

### Governance `risk_registers`
- Domain: Risk.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Risk dan kebijakan arsip PPKP.

### Governance `risk_items`
- Domain: Risk.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Risk dan kebijakan arsip PPKP.

### Governance `risk_assessments`
- Domain: Risk.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Risk dan kebijakan arsip PPKP.

### Governance `risk_matrices`
- Domain: Risk.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Risk dan kebijakan arsip PPKP.

### Governance `risk_mitigation_plans`
- Domain: Risk.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Risk dan kebijakan arsip PPKP.

### Governance `risk_monitoring_logs`
- Domain: Risk.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Risk dan kebijakan arsip PPKP.

### Governance `risk_reviews`
- Domain: Risk.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Risk dan kebijakan arsip PPKP.

### Governance `accreditation_types`
- Domain: Accreditation.
- Tenant filter: global/system reference.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Accreditation dan kebijakan arsip PPKP.

### Governance `accreditation_chapters`
- Domain: Accreditation.
- Tenant filter: global/system reference.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Accreditation dan kebijakan arsip PPKP.

### Governance `accreditation_standards`
- Domain: Accreditation.
- Tenant filter: global/system reference.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Accreditation dan kebijakan arsip PPKP.

### Governance `accreditation_criteria`
- Domain: Accreditation.
- Tenant filter: global/system reference.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Accreditation dan kebijakan arsip PPKP.

### Governance `accreditation_elements`
- Domain: Accreditation.
- Tenant filter: global/system reference.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Accreditation dan kebijakan arsip PPKP.

### Governance `accreditation_programs`
- Domain: Accreditation.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Accreditation dan kebijakan arsip PPKP.

### Governance `accreditation_self_assessments`
- Domain: Accreditation.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Accreditation dan kebijakan arsip PPKP.

### Governance `accreditation_self_assessment_items`
- Domain: Accreditation.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Accreditation dan kebijakan arsip PPKP.

### Governance `accreditation_gap_analyses`
- Domain: Accreditation.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Accreditation dan kebijakan arsip PPKP.

### Governance `accreditation_gap_items`
- Domain: Accreditation.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Accreditation dan kebijakan arsip PPKP.

### Governance `accreditation_mock_surveys`
- Domain: Accreditation.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Accreditation dan kebijakan arsip PPKP.

### Governance `accreditation_evidence`
- Domain: Accreditation.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Accreditation dan kebijakan arsip PPKP.

### Governance `quality_programs`
- Domain: Quality.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Quality dan kebijakan arsip PPKP.

### Governance `quality_indicators`
- Domain: Quality.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Quality dan kebijakan arsip PPKP.

### Governance `quality_indicator_targets`
- Domain: Quality.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Quality dan kebijakan arsip PPKP.

### Governance `quality_measurements`
- Domain: Quality.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Quality dan kebijakan arsip PPKP.

### Governance `quality_monitoring_sessions`
- Domain: Quality.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Quality dan kebijakan arsip PPKP.

### Governance `quality_evaluations`
- Domain: Quality.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Quality dan kebijakan arsip PPKP.

### Governance `quality_reports`
- Domain: Quality.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Quality dan kebijakan arsip PPKP.

### Governance `workflow_definitions`
- Domain: Workflow.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Workflow dan kebijakan arsip PPKP.

### Governance `workflow_steps`
- Domain: Workflow.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Workflow dan kebijakan arsip PPKP.

### Governance `workflow_transitions`
- Domain: Workflow.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Workflow dan kebijakan arsip PPKP.

### Governance `workflow_instances`
- Domain: Workflow.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Workflow dan kebijakan arsip PPKP.

### Governance `workflow_instance_steps`
- Domain: Workflow.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Workflow dan kebijakan arsip PPKP.

### Governance `workflow_assignments`
- Domain: Workflow.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Workflow dan kebijakan arsip PPKP.

### Governance `workflow_approvals`
- Domain: Workflow.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Workflow dan kebijakan arsip PPKP.

### Governance `workflow_histories`
- Domain: Workflow.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Workflow dan kebijakan arsip PPKP.

### Governance `notification_channels`
- Domain: Notification.
- Tenant filter: global/system reference.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Notification dan kebijakan arsip PPKP.

### Governance `notification_templates`
- Domain: Notification.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Notification dan kebijakan arsip PPKP.

### Governance `notifications`
- Domain: Notification.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Notification dan kebijakan arsip PPKP.

### Governance `notification_recipients`
- Domain: Notification.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Notification dan kebijakan arsip PPKP.

### Governance `notification_preferences`
- Domain: Notification.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Notification dan kebijakan arsip PPKP.

### Governance `notification_logs`
- Domain: Notification.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain Notification dan kebijakan arsip PPKP.

### Governance `jobs`
- Domain: System.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain System dan kebijakan arsip PPKP.


### Governance `job_batches`
- Domain: System.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain System dan kebijakan arsip PPKP.

### Governance `failed_jobs`
- Domain: System.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain System dan kebijakan arsip PPKP.

### Governance `migrations_meta`
- Domain: System.
- Tenant filter: global/system reference.
- Clinic filter: tidak langsung; mengikuti parent bila ada.
- Soft delete: tidak digunakan untuk tabel teknis ini.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain System dan kebijakan arsip PPKP.

### Governance `outbox_events`
- Domain: System.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain System dan kebijakan arsip PPKP.

### Governance `integration_logs`
- Domain: System.
- Tenant filter: `organization_id` wajib.
- Clinic filter: tersedia melalui `clinic_id` atau `target_clinic_id`.
- Soft delete: aktif melalui `deleted_at`.
- Audit: perubahan penting dicatat pada `audit_trails` dan aktivitas bisnis dicatat pada `activity_logs`.
- Indeks minimum: tenant/scope, foreign key, status bila ada, dan business key unique bila ada.
- Retensi: mengikuti domain System dan kebijakan arsip PPKP.
