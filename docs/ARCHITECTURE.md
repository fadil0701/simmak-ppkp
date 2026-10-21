# Dokumentasi Arsitektur — SIMMAK Audit Internal Klinik PPKP DKI Jakarta

## 1. Ringkasan

SIMMAK (Sistem Informasi Manajemen Mutu Klinik) Modul Audit Internal Klinik adalah sistem enterprise untuk UPT Pusat Pelayanan Kesehatan Pegawai (PPKP) Provinsi DKI Jakarta. Sistem ini mengelola siklus audit internal dari perencanaan hingga penutupan temuan.

Stack teknologi:
- **Backend**: Laravel 12, PHP 8.3+
- **Database**: PostgreSQL 17 (produksi), MySQL 8 (pengembangan lokal via Laragon)
- **Frontend**: Blade + Tailwind CSS v4 + Vite
- **Autentikasi**: Laravel Sanctum + Session (web)
- **Otorisasi**: Spatie Laravel Permission (guard `web`)
- **Penyimpanan berkas**: Storage lokal (private), mendukung object storage S3-kompatibel

## 2. Struktur Direktori

```
app/
  Http/Controllers/     Controller tipis — delegasikan ke Service
  Http/Middleware/      Middleware clinic.context, auth, permission
  Models/               Eloquent model dengan BaseModel (UUID + soft delete)
  Models/Concerns/      BaseModel, BelongsToOrganization, ClinicScope
  Policies/             Policy untuk otorisasi berbasis model
  Providers/            Service provider (AppServiceProvider, dll.)
  Services/             Business logic (AuditProgramService, AuditExecutionService, AuditCapaService, AuditEvidenceService, AuditTrailService, AuditDashboardService, dll.)
  Support/              SimmakMenu, RoleLabel, UiLabel
database/
  migrations/           Skema database dengan UUID, FK, index
  seeders/              Data demonstrasi, role, permission, klinik
  factories/            Factory untuk pengujian
resources/
  views/                Blade template (desain institusional)
  css/app.css           Tailwind v4 + design system kustom
  js/app.js             JavaScript aplikasi
routes/
  web.php               Route web dengan middleware auth + clinic.context
  api.php               Route API v1 dengan Sanctum
tests/
  Feature/              Pengujian fitur (otorisasi, workflow)
  Unit/                 Pengujian unit (audit trail, keamanan bukti)
```

## 3. Pola Arsitektur

### 3.1 Pemisahan Tanggung Jawab

- **Controller**: Terima request, validasi input, panggil Service, kembalikan respons. Tidak berisi business logic.
- **Service**: Berisi business logic kompleks, transaksi database, koordinasi antar model.
- **Model**: Relasi Eloquent, scope query, accessor/mutator.
- **Form Request**: Validasi terpusat (dapat ditambahkan ke Form Request untuk validasi yang lebih kompleks).
- **Policy**: Otorisasi berbasis model (mis. `AuditFindingPolicy`).

### 3.2 Database Transaction

Semua operasi yang membutuhkan konsistensi (membuat temuan + CAPA, menyimpan jawaban checklist, transisi status) menggunakan `DB::transaction`.

### 3.3 Soft Delete

Hanya diterapkan pada entitas yang membutuhkan penghapusan logis tanpa bertentangan dengan kewajiban retensi (mis. standar, instrumen, program). Data transaksi (temuan, bukti, CAPA, verifikasi) menggunakan soft delete untuk melacak riwayat.

### 3.4 Audit Trail

Model `AuditTrail` mencatat semua perubahan penting. Tidak dapat diubah atau dihapus melalui antarmuka pengguna biasa (mass assignment guarded).

## 4. Skema Database (Ringkasan)

Tabel utama:
- `organizations` — Organisasi induk (PPKP DKI Jakarta)
- `clinics` — 16 klinik (15 Pratama + 1 Utama), scoped by organization
- `users` — Pengguna dengan clinic scopes (user_clinic_s pivot)
- `roles`, `permissions`, `model_has_roles`, `model_has_permissions` — Spatie RBAC
- `regulatory_sources` — Sumber regulasi dengan status verifikasi
- `audit_standards` — Standar audit (terkait regulatory source)
- `instrument_versions` — Versi instrumen (draft/review/approved/active/archived)
- `standard_sections` — Hierarki bab/area penilaian (self-referencing parent)
- `standard_criteria` — Kriteria/indikator penilaian
- `audit_programs` — Program audit tahunan
- `audit_assignments` — Penugasan audit per klinik
- `audit_team_members` — Anggota tim auditor dengan deklarasi independensi
- `audit_executions` — Eksekusi audit (instrument version tercatat)
- `checklist_responses` — Jawaban checklist per kriteria
- `evidence` — Berkas bukti (private storage, checksum, access classification)
- `audit_findings` — Temuan audit (kode unik per organisasi per tahun)
- `audit_finding_comments` — Komentar pada temuan
- `corrective_actions` / `capa_cases` — Tindak lanjut CAPA
- `corrective_action_evidences` / `capa_verifications` — Bukti dan verifikasi CAPA
- `audit_approvals` / `approval_events` — Persetujuan workflow
- `audit_activities` — Log aktivitas
- `audit_notifications` — Notifikasi

Semua tabel transaksional menggunakan UUID primary key, `created_by`/`updated_by`, `timestamps`, dan `soft_deletes`.

## 5. Alur Kerja (Workflow)

```
Perencanaan → Review/Persetujuan Program → Penugasan Auditor →
Persiapan → Pelaksanaan (Checklist + Bukti) → Hasil (Temuan) →
Review Temuan → CAPA → Verifikasi → Penutupan
```

Setiap transisi status divalidasi:
- Pengguna yang berwenang
- Status saat ini dan tujuan
- Data wajib yang lengkap
- Bukti yang diperlukan
- Alasan penolakan/pengembalian
- Persyaratan persetujuan (segregasi tugas)

## 6. Role dan Permission

Role awal yang dapat dikonfigurasi:
1. Administrator Sistem — semua permission
2. Kepala PPKP / pimpinan — review, laporan, verifikasi akhir
3. Koordinator Audit Internal — manajemen program, penugasan
4. Ketua Tim Auditor — manajemen tim, deklarasi konflik
5. Auditor Internal — eksekusi, checklist, temuan (scope penugasan)
6. Reviewer Audit — review hasil eksekusi
7. Verifikator Tindak Lanjut — verifikasi CAPA
8. Kepala Klinik — view data klinik sendiri, review temuan
9. PIC Tindak Lanjut — eksekusi CAPA
10. Pengamat / pembaca laporan — view-only

Permission granular diterapkan pada setiap route sensitif. Hiding menu bukan cukup sebagai pengamanan.

## 7. Keamanan

- Validasi input di server (Laravel validation)
- CSRF protection pada semua form POST
- XSS protection via Blade escaping otomatis
- Query parameter binding via Eloquent/Query Builder
- Rate limiting pada endpoint login (`throttle:5,1`)
- Password hashing via bcrypt
- File upload: validasi MIME, ukuran (max 10MB), magic bytes verification, private storage, no public URL
- Akses berkas dicek setiap kali (download melalui controller dengan otorisasi)
- Session terenkripsi di database
- Error handling tanpa bocorkan informasi internal
- Environment variables untuk rahasia

## 8. Kompatibilitas Basis Data

Skema dirancang untuk kompatibel MySQL 8 dan PostgreSQL 17:
- Tidak menggunakan `FIELD()` (MySQL-specific)
- Tidak menggunakan `MAX(uuid)` (PG-specific issue)
- Tidak menggunakan double-quoted string literals
- UUID digunakan konsisten sebagai primary key
- Semua migration menggunakan `uuid()` dan `foreignUuid()`

## 9. Pengembangan Masa Depan

Arsitektur mendukung modul Manajemen Mutu dan Manajemen Akreditasi tanpa perlu merombak skema audit. Relasi data tersedia untuk tautan temuan → risiko, indikator mutu, dokumen, atau elemen akreditasi.

## 10. Deployment

- Pengembangan: `php artisan serve` + `npm run dev`
- Produksi: Docker (docker-compose), build assets dengan `docker compose --profile tools run --rm assets`
- Migration: `php artisan migrate --force`
- Seeder: `php artisan db:seed --class=DatabaseSeeder`
- Permission cache reset: `php artisan permission:cache-reset`