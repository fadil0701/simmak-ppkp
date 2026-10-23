# Gap Analysis — Modul Audit Internal → Temuan Audit → CAPA

**Acuan:** `docs/manual/06_AUDIT_INTERNAL.md`, `docs/manual/07_TEMUAN_AUDIT.md`,
`docs/manual/08_CAPA.md`, `docs/STRUKTUR_MENU_AUDIT_INTERNAL.md`,
`docs/UI_PELAKSANAAN_AUDIT_TEMUAN_CAPA.md`
**Tanggal:** 2026-08-31
**Metode:** bandingkan ketentuan dokumen dengan implementasi aktual (controller, service,
policy, route, view). Status: `MATCH` / `DIVERGENT` / `MISSING`.

---

## 1. Ringkasan

| Area | MATCH | DIVERGENT | MISSING |
|---|---|---|---|
| Menu & izin (06 §2, 07 §2, 08 §2, STRUKTUR_MENU) | 6 | 2 | 0 |
| Peran & kewenangan (07 §3, 08 §3, UI §2) | 4 | 2 | 0 |
| Pelaksanaan audit (06 §4, UI §3) | 4 | 1 | 0 |
| Temuan audit (07 §4–§5) | 4 | 1 | 0 |
| CAPA (08 §4) | 5 | 1 | 0 |

Semua temuan DIVERGENT di bawah sudah diperbaiki pada iterasi ini.

---

## 2. Matriks Kesesuaian

### 2.1 Menu & Izin

| # | Acuan | Ketentuan | Implementasi awal | Status | Perbaikan |
|---|---|---|---|---|---|
| M-01 | 06 §2 | Menu Audit Internal dijaga `audit.view` | `routes/web.php:137` grup `permission:audit.view` | MATCH | — |
| M-02 | 07 §2 | Menu Temuan Audit dijaga `audit.finding.view` | route `/findings*` masih di grup `permission:audit.view`, padahal `SimmakMenu.php:40` sudah memakai `audit.finding.view` → menu tampil tetapi 403 | DIVERGENT | grup route baru `permission:audit.finding.view` di `routes/web.php:143` |
| M-03 | 08 §2 | Menu CAPA dijaga `capa.view` | `routes/web.php:268` grup `permission:capa.view` | MATCH | — |
| M-04 | 08 §2 | Aksi CAPA dijaga `capa.manage`/`audit.action.update` | `routes/web.php:273` | MATCH | — |
| M-05 | 08 §2 | Validasi CAPA dijaga `audit.capa.verify` | `routes/web.php:278` | MATCH | — |
| M-06 | STRUKTUR_MENU | Urutan & label menu: Pelaksanaan Audit, Temuan Audit, CAPA | `SimmakMenu.php:40-41` | MATCH | — |
| M-07 | STRUKTUR_MENU | Label status memakai istilah manual | `UiLabel::findingStatus()` sudah memetakan `in_capa` → "Dalam CAPA" | MATCH | — |
| M-08 | 07 §5 | Filter status daftar temuan mencakup siklus manual (Draf → Tinjauan → Dikonfirmasi → Dalam CAPA → Menunggu Verifikasi → Selesai) | `findings/index.blade.php:41` memakai `action_required`, tanpa `in_capa` | DIVERGENT | daftar filter diganti `in_capa` |


### 2.2 Peran & Kewenangan

| # | Acuan | Ketentuan | Implementasi awal | Status | Perbaikan |
|---|---|---|---|---|---|
| R-01 | 07 §3 | Auditi/kepala klinik hanya melihat temuan kliniknya | `AuditFindingPolicy::view()` + `AuditWorkScope::canViewWork()` | MATCH | — |
| R-02 | 07 §3 | Auditor hanya mengisi kesesuaian + rekomendasi pada instrumen | `audit/executions/checklist.blade.php` (tabel isian tanpa input bukti/skor) | MATCH | — |
| R-03 | 08 §3 | Auditi/Kepala Klinik = tindak lanjut + unggah bukti, **bukan** validator | `SimmakRoles.php` memberi kepala-klinik `audit.capa.verify`, `audit.finding.close`, `audit.finding.review` | DIVERGENT | `SimmakRoles.php:276` — izin validasi/penutupan/review dicabut; ditambah `audit.action.update` + `audit.action.submit` agar tindak lanjut tetap jalan |
| R-04 | 08 §3, UI §2 | Validasi hanya auditor yang ditugaskan | `CapaCasePolicy::verify()` jatuh ke `hasClinicAccess()` saat temuan tanpa penugasan → kepala klinik bisa memvalidasi kasus kliniknya | DIVERGENT | `CapaCasePolicy.php:78-90` — fallback dibatasi peran pengawas audit (auditor, ketua tim audit, ketua tim mutu, kepala pusat) |
| R-05 | 08 §3 | PIC kasus tidak memvalidasi kasusnya sendiri | `CapaCasePolicy::verify()` cek `owner_id` | MATCH | — |
| R-06 | 07 §3, 08 §3 | Admin sistem tidak menutup/memvalidasi hasil final | `AuditFindingPolicy::close()`, `CapaCasePolicy::verify()` menolak `admin-sistem` | MATCH | — |
| R-07 | 07 §3 | Eskalasi temuan kritis ke Kepala Pusat | `AuditFindingPolicy::escalate()` + route `audit.finding.escalate` | MATCH | — |
| R-08 | 08 §2 | Policy CAPA sejalan dengan izin menu | `CapaCasePolicy::viewAny()` hanya `audit.action.view`, sedangkan route memakai `capa.view` | DIVERGENT | `CapaCasePolicy.php:23-29` — menerima `capa.view` **atau** `audit.action.view` |

### 2.3 Pelaksanaan Audit

| # | Acuan | Ketentuan | Implementasi awal | Status | Perbaikan |
|---|---|---|---|---|---|
| P-01 | 06 §4 | Tabel berkolom EP, Elemen Penilaian, Kelengkapan Bukti, Nilai Kesesuaian, Rekomendasi | tabel isian sudah lengkap; tabel baca (status non-`in_progress`) hanya EP/Elemen/Nilai/Rekomendasi | DIVERGENT | `checklist.blade.php:174,185-197` — kolom Kelengkapan Bukti ditambahkan pada tabel baca |
| P-02 | 06 §4 | Progres pengisian ≠ skor kepatuhan Kemenkes | teks progres di view menyatakan hal ini eksplisit | MATCH | — |
| P-03 | 06 §4 | EP tidak sesuai otomatis menjadi temuan & CAPA | `AuditExecutionService` + `AuditCapaService` | MATCH | — |
| P-04 | 06 §4 | Finalisasi hanya setelah submit tinjauan | `checklist.blade.php:145-156` (`@can('finalize')`) | MATCH | — |
| P-05 | UI §3.2 | Auditor tidak mengunggah bukti di instrumen | form tabel hanya nilai + rekomendasi | MATCH | — |

### 2.4 Temuan Audit

| # | Acuan | Ketentuan | Implementasi awal | Status | Perbaikan |
|---|---|---|---|---|---|
| T-01 | 07 §4 | Review temuan hanya saat `under_review` | `AuditFindingPolicy::review()` | MATCH | — |
| T-02 | 07 §5 | Temuan ditutup hanya setelah verifikasi efektif | `AuditFindingPolicy::close()` → `capa->hasEffectiveVerification()` | MATCH | — |
| T-03 | 07 §5 | "Risiko tinggi" = Kritikal + Mayor pada dashboard | `AuditDashboardService::highRiskFindings()` hanya `severity = critical`, padahal label kartu "Temuan kritis dan tinggi" | DIVERGENT | `AuditDashboardService.php:81-85` — `whereIn('severity', ['critical','major'])` |
| T-04 | 07 §5 | Status terbuka mencakup `in_capa` | `AuditFinding::OPEN_STATUSES` memuat `in_capa` | MATCH | — |
| T-05 | 07 §4 | Reopen hanya untuk temuan `closed` | `AuditFindingPolicy::reopen()` | MATCH | — |

### 2.5 CAPA

| # | Acuan | Ketentuan | Implementasi awal | Status | Perbaikan |
|---|---|---|---|---|---|
| C-01 | 08 §4 | RCA 5 Why / Fishbone sebelum tindakan | `CapaController::storeRca()` + `AuditCapaService` | MATCH | — |
| C-02 | 08 §4 | PIC tindakan "jika perlu" (default pemilik kasus) | `CapaController::storeAction()` mewajibkan `assigned_to`; form `capa/show.blade.php` `required` | DIVERGENT | `CapaController.php:177` → `nullable`; `capa/show.blade.php:104-105` → opsi default pemilik kasus, `required` dilepas; service sudah fallback `owner_id` |
| C-03 | 08 §4 | Bukti wajib sebelum validasi | `CapaCasePolicy::verify()` → `hasSubmittedActionEvidence()` | MATCH | — |
| C-04 | 08 §4 | Verifikasi tidak efektif mengembalikan kasus ke `action` | `AuditCapaService::recordVerification()` + uji regresi | MATCH | — |
| C-05 | 08 §4 | Kasus `closed` tidak dapat diubah/ditambah tindakan | `CapaCasePolicy::update()` + `AuditCapaService::addAction()` | MATCH | — |
| C-06 | 08 §4 | Auditi tidak melihat tombol validasi | `@can('verify', $capa)` di `capa/show.blade.php` | MATCH | — |

---

## 3. Berkas yang Diubah

| Berkas | Perubahan |
|---|---|
| `routes/web.php:141-148` | grup route `/findings*` dipisah ke `permission:audit.finding.view` |
| `app/Support/SimmakRoles.php:273-287` | kepala-klinik: cabut `audit.capa.verify`, `audit.finding.close`, `audit.finding.review`; tambah `audit.action.update`, `audit.action.submit` |
| `app/Policies/CapaCasePolicy.php:23-29` | `viewAny()` menerima `capa.view` atau `audit.action.view` |
| `app/Policies/CapaCasePolicy.php:78-90` | fallback `verify()` dibatasi peran pengawas audit |
| `app/Services/AuditDashboardService.php:81-85` | risiko tinggi = `critical` + `major` |
| `app/Http/Controllers/CapaController.php:177` | `assigned_to` opsional |
| `resources/views/capa/show.blade.php:104-105` | select PIC tanpa `required`, label default pemilik kasus |
| `resources/views/audit/findings/index.blade.php:41` | filter status memuat `in_capa` |
| `resources/views/audit/executions/checklist.blade.php:174,185-197` | kolom Kelengkapan Bukti pada tabel baca |
| `tests/Feature/Audit/FindingAndCAPAWorkflowTest.php` | role auditor uji diberi `audit.capa.verify`; verifier uji memakai peran auditor; tambah uji `kepala_klinik_cannot_verify_capa` |

---

## 4. Sisa Pekerjaan / Catatan

| # | Item | Status | Alasan |
|---|---|---|---|
| O-01 | Sinkronisasi izin pada database yang sudah berjalan | TINDAK LANJUT | Setelah perubahan `SimmakRoles`, jalankan `php artisan db:seed --class=AuditPermissionSeeder` lalu `php artisan permission:cache-reset` agar role `kepala-klinik` benar-benar kehilangan izin validasi. |
| O-02 | Status legacy `action_required`, `open`, `verified` | DIBIARKAN | Masih ada di `AuditFinding::OPEN_STATUSES`/`UiLabel` untuk data lama; filter UI kini memakai siklus manual. Migrasi data historis di luar cakupan. |
| O-03 | Kompilasi seluruh Blade | TERVERIFIKASI | `php artisan view:cache` tanpa galat. |
| O-04 | Perilaku lintas organisasi/klinik pada level query | TERVERIFIKASI | `tests/Feature/Audit/AssignmentAndFollowUpScopeTest.php` lulus. |

---

## 5. Verifikasi

```bash
php artisan view:cache
php artisan test --filter='Audit|Finding|Capa'
```

Hasil: `56 passed (136 assertions)`.

