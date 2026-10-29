# Rencana Perbaikan — Master Pegawai, Form Pengguna, dan Modul Penugasan

| Atribut | Nilai |
|---------|-------|
| Tanggal | 23 Oktober 2026 |
| Status | **Diimplementasikan** (26 Oktober 2026) — lihat §0 untuk status per langkah; ADR-0014…ADR-0018 masih *Proposed* |
| Pemilik | Tim Pengembangan SIMMAK PPKP |
| Acuan | `docs/PRD.md`, `docs/DDD.md`, `docs/ROLE_MATRIX.md`, `docs/decisions/ADR-0013-unit-klinik-dan-peran.md`, `docs/STRUKTUR_MENU_AUDIT_INTERNAL.md`, `docs/manual/01_ROLE_DAN_AKSES.md` |

Dokumen ini mencatat empat perbaikan pada modul administrasi pengguna dan penugasan audit:
master pegawai multi klinik/unit, form pengguna (multi peran, akses klinik, scope sistem),
serta tampilan indeks modul penugasan. Setiap bagian memuat kondisi implementasi saat ini
yang diverifikasi langsung pada kode, masalah yang muncul, desain target, langkah migrasi,
dan kriteria penerimaan. Semua perubahan bersifat aditif dan dapat dibalik: tidak ada tabel,
kolom, atau data lama yang dihapus.

---

## 0. Status implementasi (26 Oktober 2026)

Seluruh langkah §5 sudah **diimplementasikan** dan hijau pada suite 108 test / 337 assertion
(`php artisan test`), `view:cache`, `npm run build`, dan Pint untuk berkas yang disentuh.
Keputusan terbuka ADR-0014…ADR-0018 **masih berstatus Proposed**; implementasi memakai opsi
paling kecil yang dapat dibalik (add-only, nullable, default aman).

| # | Langkah §5 | Status | Bukti |
|---|------------|--------|-------|
| 1 | Migrasi pivot `employee_clinic_assignments` + backfill | Selesai | `database/migrations/2026_10_26_100001_*`, 7 baris backfill pada data dev |
| 2 | `Employee` multi klinik + form centang klinik/radio utama | Selesai | `Employee::syncClinicAssignments()`, `employees/form.blade.php`, `EmployeeMultiClinicTest` (8 test) |
| 3 | Katalog 8 peran (`SimmakRoles::catalog()`) + label legacy | Selesai | `SimmakRoles`, peran lama tetap ada & tidak lagi assignable |
| 4 | Multi peran pada form pengguna | Selesai | `role_ids[]`, `syncRoles()`, `UserMultiRoleAndScopeTest` |
| 5 | Sinkronisasi klinik dari pegawai | Selesai | `App\Services\UserClinicScopeSynchronizer`, dipanggil dari `EmployeeController`/`UserController` |
| 6 | Tabel + bundel Scope Sistem | Selesai | `2026_10_26_100002_*`, `App\Support\SimmakScopes`, `User::scopeNames()`, `simmak:sync-user-scopes` |
| 7 | Form Scope Sistem multi-pilih | Selesai | `users/create|edit` (checkbox `scope_names[]`), `users/show` menampilkan peran + scope |
| 8 | Indeks penugasan (kolom + detail tim) | Selesai | `audit/assignments/index|show.blade.php`, `audit_assignments.instrument_version_id`, `AssignmentIndexViewTest` (4 test) |
| 9 | Sinkronisasi dokumentasi & manual | Selesai | `AGENTS.md`, `docs/DDD.md` §6.12, `docs/ROLE_MATRIX.md` §11, `docs/PROGRESS_TIMELINE.md`, `docs/manual/01_ROLE_DAN_AKSES.md` |

Catatan implementasi yang menyimpang dari rencana awal (dan alasannya):

- Nama tes digabung menjadi dua berkas: `EmployeeMultiClinicTest` (langkah 1–2) dan
  `UserMultiRoleAndScopeTest` (langkah 3–7). Tidak ada `RoleCatalogTest`/`UserMultiRoleTest`/
  `UserClinicScopeSyncTest`/`UserScopePermissionTest` terpisah.
- `SimmakRoles::labels()` mengembalikan katalog resmi **plus** label peran legacy, sedangkan
  `assignableNames()` hanya 8 peran resmi. `sync()` tetap membuat baris peran legacy agar akun
  dan seeder demo tidak pecah.
- `kasatpel-ukp` dan `kasatpel-ukm` memakai bundel izin yang sama (`$pengawasLingkup`): perbedaan
  lingkup UKP/UKM adalah data organisasi, bukan izin. Pemetaan per akun dari `ketua-tim-*`
  menunggu ADR-0015.
- Scope diberikan sebagai **izin langsung** pada akun (`model_has_permissions`) sehingga
  `simmak:sync-user-scopes` akan menimpa izin langsung yang diberikan manual. Izin peran
  (`role_has_permissions`) tidak tersentuh.
- `employee_clinic_assignments` ditulis lewat model pivot ber-UUID (`EmployeeClinicAssignment
  extends Pivot`) agar `attach()/sync()` tidak menyisipkan `id` kosong.

---

## 1. Ringkasan

| Area | Masalah saat ini | Arah perbaikan | Dampak skema |
|------|------------------|----------------|--------------|
| Master pegawai | Satu pegawai hanya punya satu `clinic_id` + satu `unit_id`, sedangkan akun pengguna bisa diberi akses banyak klinik lewat `user_clinic_scopes` | Pegawai dapat memegang beberapa klinik/unit kerja; akses pengguna diturunkan dari master pegawai | Tabel pivot baru `employee_clinic_assignments`; kolom lama tetap sebagai klinik utama |
| Form pengguna — peran | Satu pengguna hanya dapat memilih satu peran (`assignRole()` / `syncRoles([$role])`) | Multi peran (checkbox) dengan katalog 8 peran resmi | Tidak ada (tabel Spatie sudah many-to-many) |
| Form pengguna — akses klinik | Checkbox `clinic_ids[]` manual di form pengguna, terpisah dari master pegawai → dua sumber kebenaran | Akses klinik mengikuti klinik/unit kerja pada master pegawai; `user_clinic_scopes` menjadi cermin yang disinkronkan | Tidak ada (tabel sudah ada) |
| Form pengguna — scope sistem | Kewenangan fungsional (audit/mutu/akreditasi/monitoring/auditor/auditi) menempel pada peran dan tidak bisa dikombinasikan | Scope Sistem multi-pilih sebagai lapisan izin tambahan di atas peran | Tabel baru `user_scope_assignments` |
| Modul penugasan — indeks | Kolom: kode tugas, judul, program, klinik, ketua tim, status, aksi | Menampilkan petugas auditor, lokasi audit, tanggal audit, judul standar/instrumen, dan aksi detail tim audit | Opsional: `audit_assignments.instrument_version_id` (lihat §4.3) |

---

## 2. Master Pegawai — multi klinik/unit kerja

### 2.1 Kondisi implementasi saat ini

- Tabel `employees` (`database/migrations/2026_07_08_000001_create_core_organization_tables.php:86`)
  menyimpan `clinic_id` dan `unit_id` sebagai foreign key tunggal dan nullable.
- Model `app/Models/Employee.php` memakai relasi `clinic(): BelongsTo` dan `unit(): BelongsTo`.
- Form `resources/views/employees/form.blade.php` hanya menyediakan satu `select`
  Klinik / Unit (baris 75–84) dan satu `select` Subunit opsional (baris 87–96).
- `EmployeeController::validated()` memvalidasi satu `clinic_id` dan satu `unit_id` per pegawai.
- Di sisi akun, akses klinik disimpan terpisah pada `user_clinic_scopes`
  (`2026_07_08_000002_create_users_and_auth_tables.php:34`) dan diisi manual lewat
  checkbox batas klinik pada form pengguna (`users/partials/form.blade.php`). Label UI
  memisahkan peran jabatan, tugas di sistem, dan batas klinik agar tidak diisi ganda
  (lihat `docs/ROLE_MATRIX.md` §6a).

### 2.2 Masalah

Pegawai hanya tercatat pada satu klinik, tetapi akun pengguna yang terhubung ke pegawai
tersebut dapat diberi akses ke klinik lain melalui `user_clinic_scopes`. Muncul dua sumber
kebenaran: master pegawai menyatakan klinik A, akun menyatakan A + B + C. Untuk peran
Kepala Klinik/Unit dan Admin Klinik/Unit kondisi ini ambigu — tidak jelas klinik mana
tanggung jawab struktural dan klinik mana sekedar akses tambahan. Selain itu, pegawai yang
memang bertugas di dua unit (mis. Kasatpel UKP yang membina beberapa klinik) tidak dapat
direpresentasikan pada master.

### 2.3 Desain target

- Tambah tabel pivot `employee_clinic_assignments`:

  ```php
  Schema::create('employee_clinic_assignments', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
      $table->foreignUuid('clinic_id')->constrained('clinics')->cascadeOnDelete();
      $table->foreignUuid('unit_id')->nullable()->constrained('units')->nullOnDelete();
      $table->boolean('is_primary')->default(false);
      $table->timestamps();

      $table->unique(['employee_id', 'clinic_id']);
      $table->index(['clinic_id', 'is_primary']);
  });
  ```

- `employees.clinic_id` dan `employees.unit_id` **tidak dihapus**. Keduanya menjadi cermin
  klinik/unit utama (write-through) supaya query dan tampilan lama tetap berjalan:
  filter indeks pegawai, `AuditAssignmentController::auditorCandidates()` dengan
  `with(['employee.unit', 'employee.clinic'])`, serta laporan yang membaca kolom tersebut.
- Model `Employee` ditambah:
  - `clinicAssignments(): HasMany`
  - `clinics(): BelongsToMany` (pivot `unit_id`, `is_primary`)
  - `primaryClinic(): ?Clinic`
  - `clinicIds(): array`
- Form master pegawai: daftar klinik/unit menjadi baris berulang (minimal satu baris).
  Setiap baris berisi `clinic_id`, `unit_id` opsional, dan penanda "klinik utama".
  Minimal satu baris wajib diisi dan tepat satu baris bertanda utama.
- Validasi: seluruh `clinic_id`/`unit_id` harus milik organisasi pegawai
  (`Rule::exists(...)->where('organization_id', $orgId)`), dan `unit_id` harus berada pada
  klinik yang dipilih di baris yang sama.
- Penyimpanan: tulis pivot di dalam `DB::transaction()`, lalu set `employees.clinic_id`
  dan `employees.unit_id` dari baris utama.

### 2.4 Migrasi data

1. Buat tabel pivot.
2. Backfill: untuk setiap pegawai dengan `clinic_id` terisi, buat satu baris pivot
   `is_primary = true` beserta `unit_id` yang ada.
3. Pegawai tanpa klinik dibiarkan tanpa baris pivot (tidak wajib).
4. `down()` hanya menghapus tabel pivot; kolom lama dan data pegawai tidak disentuh.

### 2.5 Kriteria penerimaan

- Satu pegawai dapat menyimpan lebih dari satu klinik/unit kerja dan tepat satu klinik utama.
- Daftar pegawai tetap dapat difilter per klinik, termasuk pegawai yang kliniknya bukan
  klinik utama.
- `Employee::primaryClinic()` mengembalikan klinik utama; `Employee::clinics()` mengembalikan
  seluruh klinik yang dipegang.
- Test baru: simpan multi klinik, tolak klinik beda organisasi, tolak dua klinik utama,
  filter indeks menampilkan pegawai multi klinik.

---

## 3. Form Pengguna — multi peran, akses klinik, dan scope sistem

### 3.1 Kondisi sebelum perubahan (23 Oktober 2026)

> Snapshot historis — dipertahankan sebagai catatan asal-usul. Kondisi **berlaku sekarang**
> ada pada §3.2–§3.5 dan pada kode (`UserController`, `resources/views/users/`).
- `resources/views/users/create.blade.php:102` dan `resources/views/users/edit.blade.php`
  memakai satu `select name="role"` (wajib, satu pilihan).
- `app/Http/Controllers/UserController.php`:
  - `store()` — validasi `'role' => required|string|Rule::in(SimmakRoles::assignableNames())`
    (baris 82) lalu `$newUser->assignRole($validated['role'])` (baris 103).
  - `update()` — validasi sama (baris 149) lalu `$user->syncRoles([$validated['role']])`
    (baris 172), sehingga peran lain yang sudah dimiliki akan tercabut.
  - `create()/edit()` menyiapkan `roles` dari `SimmakRoles::assignableNames()` (baris 222–233).
- Akses klinik diisi dari checkbox `clinic_ids[]` → `resolveClinicIds()` +
  `syncClinicScopes()` (baris 267–290) ke tabel `user_clinic_scopes`.
- Katalog peran pada `app/Support/SimmakRoles.php`: `super-admin`, `admin-sistem`,
  `kepala-pusat`, `kasubbag-tu`, `ketua-tim-mutu`, `ketua-tim-audit`,
  `ketua-tim-akreditasi`, `auditor`, `kepala-klinik`, `auditi`, plus `legacyMap()` untuk
  alias lama (`admin-mutu`, `pj-mutu`, `tim-akreditasi`, `staff`).
- `User::clinicScopeIds()` (baris 195) dan `User::hasClinicAccess()` (baris 213) menjadi
  dasar `SetClinicContext`, trait `ClinicScope`, dan `AuditWorkScope`.

### 3.2 Multi peran

- Form pengguna memakai checkbox multi-pilih `role_ids[]`; minimal satu peran wajib dipilih.
- Controller: `assignRole()`/`syncRoles([$role])` diganti `syncRoles($validated['role_ids'])`
  pada `store()` dan `update()`. Validasi:
  `'role_ids' => required|array|min:1`, `'role_ids.*' => Rule::in(SimmakRoles::assignableNames())`.
- Label UI tetap memakai `RoleLabel::for($role->name)` (`app/Support/RoleLabel.php`).
- Filter peran pada daftar pengguna sudah memakai `whereHas('roles', ...)`
  (`UserController.php:45`), sehingga pengguna multi peran otomatis ikut terfilter tanpa
  perubahan query.
- Form tetap mengirim `role` tunggal sebagai fallback selama masa transisi (bila
  `role_ids` kosong), agar tautan/bookmark form lama tidak langsung gagal validasi.

### 3.3 Katalog peran resmi (8 peran)

| No | Kode peran | Label UI | Basis kewenangan |
|----|------------|----------|------------------|
| 1 | `super-admin` | Super Admin (Full Akses Sistem) | Seluruh izin (`SimmakRoles::allPermissionNames()`) + bypass alur kerja dan kepemilikan data |
| 2 | `admin-sistem` | Admin Sistem | Administrasi teknis: pengguna, pengaturan, master klinik/pegawai/jabatan, DMS, konfigurasi audit; tidak menyetujui hasil audit |
| 3 | `kepala-pusat` | Kepala Pusat | Monitoring lintas klinik + persetujuan akhir program/laporan |
| 4 | `kasubbag-tu` | Kasubbag TU | Administrasi umum, kepegawaian, dokumen, pengaturan |
| 5 | `kasatpel-ukp` | Kasatpel UKP | Pengawasan mutu/audit lingkup Upaya Kesehatan Perorangan |
| 6 | `kasatpel-ukm` | Kasatpel UKM | Pengawasan mutu/audit lingkup Upaya Kesehatan Masyarakat |
| 7 | `kepala-klinik` | Kepala Klinik/Unit | Dashboard klinik + tindak lanjut temuan/CAPA pada klinik yang dipegang |
| 8 | `admin-klinik` | Admin Klinik/Unit | Operasional klinik/unit: dokumen, bukti, tindak lanjut pada klinik yang dipegang |

Pemetaan dari katalog lama (dijalankan sekali lewat seeder/migrasi data; peran lama tidak
dihapus agar akun dan test lama tetap berjalan):

Sumber kebenaran tabel ini adalah kode, bukan dokumen: `SimmakRoles::legacyMap()` untuk alias,
`SimmakRoles::legacyRoleLabels()` untuk peran lama yang tidak assignable, dan
`SimmakRoles::permissionsFor()` untuk bundel izinnya.

| Peran lama | Peran baru | Catatan |
|------------|-----------|---------|
| `super-admin` | `super-admin` | Tetap |
| `admin-mutu` | `admin-sistem` | Alias di `legacyMap()`; izin disamakan dengan bundel `admin-sistem` |
| `pj-mutu` | `ketua-tim-mutu` (legacy, bundel `$qualityLead`) | Alias di `legacyMap()`; **bukan** `kasatpel-ukp` |
| `tim-akreditasi` | `ketua-tim-akreditasi` (legacy) | Alias di `legacyMap()` |
| `staff` | `auditi` (legacy, bundel `auditi`) | Alias di `legacyMap()` |
| `kepala-pusat` | `kepala-pusat` | Tetap |
| `kasubbag-tu` | `kasubbag-tu` | Tetap |
| `kepala-klinik` | `kepala-klinik` | Tetap |
| `ketua-tim-mutu`, `ketua-tim-audit`, `ketua-tim-akreditasi` | belum dipetakan otomatis | Tidak assignable; izin mengikuti bundel legacy masing-masing (`$qualityLead`, `$auditLead`, bundel akreditasi). Pemetaan ke `kasatpel-ukp`/`kasatpel-ukm` butuh keputusan per akun (ADR-0015) |
| `auditor` | — menjadi **Scope Sistem: Auditor** | Peran legacy tetap ada di DB dengan bundel `auditor`; fungsi audit kini ditandai lewat scope |
| `auditi` | — menjadi **Scope Sistem: Auditi** | Idem |
| — | `admin-klinik` | Peran baru |

Yang **tidak** boleh dibaca dari tabel ini: `pj-mutu` bukan alias `kasatpel-ukp`, dan `staff`
bukan alias `auditi` versi scope — keduanya tetap peran Spatie terpisah yang hanya mewarisi
bundel izin, sehingga pencabutan scope tidak otomatis mencabut izin peran legacy tersebut.

`SimmakRoles::assignableNames()` menentukan daftar peran yang muncul di form, sehingga
peran legacy tetap ada di database namun tidak lagi dapat dipilih untuk akun baru.

### 3.4 Akses klinik mengikuti master pegawai

- Form pengguna **tidak lagi** menampilkan checkbox `clinic_ids[]`.
- Akses klinik dihitung dari `employee.clinics()` (§2). `default_clinic_id` diisi otomatis
  dari klinik utama pegawai dan ditampilkan read-only.
- `user_clinic_scopes` tetap dipakai sebagai cermin (cache) agar `User::clinicScopeIds()`,
  `hasClinicAccess()`, `SetClinicContext`, `ClinicScope`, dan `AuditWorkScope` tidak perlu
  diubah. Sinkronisasi dipusatkan pada service baru `UserClinicScopeSynchronizer`, dipanggil saat:
  1. pegawai dibuat/diubah/dihapus (`EmployeeController::store/update/destroy`),
  2. akun dibuat/diubah dengan `employee_id` berbeda (`UserController::store/update`),
  3. pegawai dinonaktifkan (`is_active = false`) — scope klinik akun ikut dikosongkan.
- Akun tanpa `employee_id` (mis. akun teknis Super Admin) tetap boleh memakai
  `clinic_ids[]` manual; form menampilkan keterangan
  "Akses klinik mengikuti master pegawai. Pilih data pegawai terlebih dahulu."
- Peran lintas klinik (`super-admin`, `admin-sistem`, `kepala-pusat`) tidak terpengaruh
  karena `clinicScopeIds()` sudah mengembalikan seluruh klinik organisasi untuk peran tersebut.
- Perubahan klinik aktif yang berada di luar himpunan klinik pegawai tetap ditolak
  (`SetClinicContext` mengabaikan `active_clinic_id` yang tidak valid), sehingga pemilih
  klinik di header tidak bisa dipakai untuk melewati batas akses.

### 3.5 Scope Sistem (multi-pilih)

Scope Sistem adalah lapisan kewenangan fungsional yang dapat dikombinasikan pada satu akun,
terlepas dari peran strukturalnya. Disimpan pada tabel baru:

```php
Schema::create('user_scope_assignments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('scope', 40);   // audit_internal, mutu, akreditasi, monitoring, auditor, auditi
    $table->foreignUuid('granted_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('granted_at')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'scope']);
});
```

Bundel izin per scope:

| Scope | Izin yang diberikan | Catatan |
|-------|--------------------|---------|
| `audit_internal` | `dashboard.executive`, `audit.view`, `audit.manage`, seluruh `audit.program.*`, `audit.assignment.*`, `audit.standard.*`, `audit.execution.*`, `audit.evidence.*`, `audit.finding.*`, `audit.action.*`, `audit.report.*`, `audit.dashboard.view`, `capa.view`, `capa.manage` | Hak kelola penuh modul Audit Internal |
| `mutu` | `dashboard.executive`, `quality.view`, `quality.manage`, `risk.view`, `risk.manage`, `capa.view`, `capa.manage`, `dms.view` | Hak kelola penuh modul Mutu |
| `akreditasi` | `dashboard.executive`, `accreditation.view`, `accreditation.manage`, `dms.view`, `dms.manage` | Hak kelola penuh modul Akreditasi |
| `monitoring` | `dashboard.executive`, `audit.dashboard.view`, seluruh izin baca `audit.*` (`*.view`, `audit.log.view`), `capa.view`, `risk.view`, `quality.view`, `accreditation.view`, `dms.view`, `clinics.view`, `employees.view` | Setara bundel `$monitorAudit` pada `SimmakRoles::permissionsFor()` |
| `auditor` | `dashboard.executive`, `audit.dashboard.view`, `audit.view`, `audit.manage`, `audit.program.view`, `audit.assignment.view`, `audit.standard.view`, `audit.execution.view`, `audit.execution.execute`, `audit.execution.submit`, `audit.evidence.upload`, `audit.evidence.view`, `audit.finding.view`, `audit.finding.create`, `audit.finding.update`, `audit.action.view`, `audit.action.update`, `audit.capa.verify`, `audit.report.view`, `capa.view` | Sisi pelaksanaan pemeriksaan: penugasan, pelaksanaan, temuan, CAPA, laporan |
| `auditi` | `dashboard.clinic`, `audit.view`, `audit.execution.view`, `audit.evidence.view`, `audit.evidence.download`, `audit.finding.view`, `audit.action.view`, `audit.action.update`, `audit.action.submit`, `capa.view`, `capa.manage` | Sisi klinik: tindak lanjut hasil audit |

Aturan penerapan:

1. Izin efektif = izin peran ∪ izin scope. Scope tidak pernah mengurangi izin peran.
2. Scope **tidak** melewati segregasi tugas: `audit.program.approve`, `audit.finding.close`,
   `audit.finding.reopen`, dan `audit.capa.verify` tetap tunduk pada policy serta larangan
   self-approval yang sudah berjalan (lihat `SuperAdminFullAccessTest` sebagai acuan perilaku).
3. Scope tidak melewati batas klinik: `AuditWorkScope` dan `User::hasClinicAccess()` tetap
   menjadi penyaring data.
4. Pemberian scope dicatat (`granted_by`, `granted_at`) dan diikuti
   `php artisan permission:cache-reset` setelah sinkronisasi izin.
5. `super-admin` tidak membutuhkan scope karena izinnya sudah penuh.

### 3.6 Migrasi & kompatibilitas

- Buat tabel `user_scope_assignments`; isi awal untuk akun yang ada:
  peran `auditor` → scope `auditor`, peran `auditi`/`staff` → scope `auditi`,
  `kepala-pusat` → `monitoring`, peran lain dibiarkan kosong (dapat ditambah manual).
- Bundel peran tetap di `SimmakRoles::permissionsFor()`; bundel scope diletakkan pada kelas
  baru `app/Support/SimmakScopes.php` (konstanta nama scope + `permissionsFor()` +
  `assignableNames()`) agar `SimmakRoles` tidak membengkak dan mudah diuji terpisah.
- Sinkronisasi izin: `SimmakRoles::sync()` diperluas agar ikut membuat izin dari bundel scope,
  lalu role `super-admin` tetap menerima seluruh izin.
- Sediakan command `php artisan simmak:sync-user-scopes` untuk menyelaraskan ulang izin
  seluruh akun setelah bundel scope berubah.
- Tidak ada perubahan pada guard (`web`), nama permission lama, maupun tabel Spatie.

### 3.7 Kriteria penerimaan

- Satu akun dapat memilih lebih dari satu peran; `users.show` menampilkan seluruh peran.
- Form pengguna tidak lagi menampilkan pilihan klinik manual bila `employee_id` terisi;
  daftar klinik yang tampil berasal dari master pegawai.
- Mengubah klinik pada master pegawai langsung mengubah `user_clinic_scopes` akun terkait
  tanpa langkah manual di form pengguna.
- Scope Sistem multi-pilih tersimpan dan menambah izin sesuai tabel §3.5; kombinasi
  `admin-sistem` + scope `auditor` tidak memberi hak approve/close temuan.
- Akun tanpa peran dan tanpa scope hanya dapat mengakses `dashboard.view`.
- Test baru: multi peran, gating route per scope (`403` vs `200`), sinkronisasi klinik dari
  pegawai, akun tanpa pegawai tetap dapat memakai klinik manual.

---

## 4. Modul Penugasan — tampilan indeks

### 4.1 Kondisi implementasi saat ini

`resources/views/audit/assignments/index.blade.php` menampilkan kolom: Kode Tugas, Judul,
Program, Klinik, Ketua Tim, Status (termasuk badge konflik), dan Aksi (Lihat / Ubah).
Data diambil dari `AuditAssignmentService::getAssignmentsForUser()` (baris 158) yang sudah
memuat `with(['program', 'clinic', 'leadAuditor', 'teamMembers.user'])` dan memakai
`AuditWorkScope::assignments()` untuk pembatasan klinik, tetapi:

- belum menampilkan jumlah/nama anggota tim di tabel,
- belum memuat `leadAuditor.employee` (NIP/jabatan) maupun `clinics.city`,
- belum ada sumber "judul standar/instrumen" pada penugasan (instrumen baru ditentukan pada
  `audit_executions.instrument_version_id`),
- hasil query dikembalikan tanpa paginasi (`->get()`), sehingga tabel tumbuh tanpa batas.

### 4.2 Desain target kolom

| Kolom | Sumber data | Catatan |
|-------|-------------|---------|
| Kode Tugas | `audit_assignments.assignment_code` | Tetap |
| Petugas Auditor | `lead_auditor_id` → `users.name` (+ `employees.employee_number`) dan `teamMembers.user.name` | Tampilkan ketua tim + jumlah anggota ("+3 anggota") |
| Lokasi Audit | `clinics.name` (+ `clinics.city`) | Ganti label "Klinik" menjadi "Lokasi Audit" |
| Tanggal Audit | `scheduled_start` – `scheduled_end` | Format `d M Y` (Indonesia); tandai bila rentang sudah lewat dan status belum `completed` |
| Judul Standar / Instrumen | lihat §4.3 | Ringkas: nama standar + label versi |
| Status | `status` + `conflict_check_status` | Tetap |
| Aksi | route `audit.assignments.show` | Label "Detail Tim" — membuka data lengkap tim audit untuk klinik yang diaudit |

Kolom "Judul" dan "Program" tetap dipertahankan; bila tabel terlalu lebar, judul digabung
ke baris kedua di bawah kode tugas (pola dua baris seperti kolom Petugas Auditor).

### 4.3 Sumber "judul standar instrumen"

`audit_assignments` belum memiliki kolom instrumen; instrumen ditentukan pada
`audit_executions.instrument_version_id` (`2026_10_19_110003_create_audit_executions_table.php`).
Dua opsi:

- **(a) Turunan (tanpa migrasi).** Ambil dari eksekusi yang lahir dari penugasan:
  `$assignment->executions->pluck('instrumentVersion.standard.title')`, tampilkan unik.
  Bila eksekusi belum ada, tampilkan "Belum ditentukan".
- **(b) Kolom baru (disarankan).** Tambah `audit_assignments.instrument_version_id` nullable
  + foreign key ke `instrument_versions`, diisi saat pembuatan penugasan, lalu eksekusi
  mewarisi nilai tersebut. Kolom ini membuat judul standar tersedia sebelum eksekusi dibuat
  dan menghindari query tambahan pada tabel indeks.

Implementasi disarankan memakai (b) dengan fallback (a) selama data lama belum terisi.

### 4.4 Aksi detail tim audit

- Route `audit.assignments.show` sudah tersedia di dalam grup
  `Route::middleware('permission:audit.assignment.view')` (`routes/web.php:232`).
- Halaman `audit/assignments/show.blade.php` sudah menampilkan kartu "Tim Auditor" beserta
  status independensi dan konflik setiap anggota.
- Perbaikan yang dibutuhkan:
  1. `show` menampilkan NIP, jabatan, dan unit kerja anggota dari `users.employee`,
  2. tombol pada indeks diberi label "Detail Tim" (ikon `users`) menggantikan "Lihat",
  3. anggota nonaktif ditandai dan konflik yang belum selesai ditampilkan sebagai peringatan
     di atas tabel,
  4. tambahkan ringkasan "Jumlah auditor: n (ketua + anggota)" pada kepala halaman.

### 4.5 Query & performa

`AuditAssignmentService::getAssignmentsForUser()` disesuaikan menjadi:

```php
$query = AuditWorkScope::assignments(AuditAssignment::query(), $user)
    ->with([
        'program:id,name',
        'clinic:id,name,city',
        'leadAuditor:id,name,employee_id',
        'leadAuditor.employee:id,employee_number,full_name,position',
        'teamMembers' => fn ($q) => $q->where('active', true),
        'teamMembers.user:id,name',
        'executions.instrumentVersion.standard:id,title',
    ])
    ->orderByDesc('created_at');
```

Paginasi ditambahkan (`->paginate(15)->withQueryString()`) dan view memakai
`$assignments->links()`; kolom tim diringkas (ketua + jumlah anggota) agar jumlah baris
tidak meledak pada penugasan dengan banyak anggota.

### 4.6 Kriteria penerimaan

- Indeks penugasan menampilkan nama ketua tim, jumlah anggota, lokasi audit, rentang tanggal
  audit, dan judul standar/instrumen.
- Aksi "Detail Tim" membuka halaman detail yang memuat seluruh anggota tim untuk klinik yang
  diaudit beserta status deklarasi.
- Tidak ada N+1 pada halaman indeks (diverifikasi dengan `DB::listen` atau
  `assertQueryCount`).
- Pembatasan klinik tetap berlaku: auditor non-penugasan tidak melihat data penugasan di luar
  `AuditWorkScope`-nya dan tidak melihat tombol Ubah.
- Test baru `AssignmentIndexViewTest`: kolom baru tampil, data lintas organisasi tetap
  terfilter, paginasi berfungsi.

---

## 5. Rencana implementasi bertahap

| # | Langkah | Berkas utama | Tes |
|---|---------|--------------|-----|
| 1 | Migrasi pivot `employee_clinic_assignments` + backfill | `database/migrations/*_create_employee_clinic_assignments_table.php` | migrasi idempoten (MySQL & PostgreSQL) |
| 2 | Model `Employee` multi klinik + form berulang | `app/Models/Employee.php`, `resources/views/employees/form.blade.php`, `EmployeeController` | `EmployeeMultiClinicTest` |
| 3 | Katalog 8 peran + `legacyMap` | `app/Support/SimmakRoles.php`, `DatabaseSeeder` | `RoleCatalogTest` |
| 4 | Multi peran pada form pengguna | `UserController`, `users/create|edit|show` | `UserMultiRoleTest` |
| 5 | Sinkronisasi klinik dari pegawai | service `UserClinicScopeSynchronizer`, `UserController`, `EmployeeController` | `UserClinicScopeSyncTest` |
| 6 | Tabel + bundel Scope Sistem | migrasi `user_scope_assignments`, `app/Support/SimmakScopes.php`, `User::scopeNames()` | `UserScopePermissionTest` |
| 7 | Form Scope Sistem multi-pilih | `users/create|edit`, `UserController` | idem |
| 8 | Indeks penugasan (kolom + detail tim) | `audit/assignments/index.blade.php`, `show.blade.php`, `AuditAssignmentService` | `AssignmentIndexViewTest` |
| 9 | Sinkronisasi dokumentasi & manual | `docs/manual/01_ROLE_DAN_AKSES.md`, `docs/ROLE_MATRIX.md`, `docs/STRUKTUR_MENU_AUDIT_INTERNAL.md` | tinjauan manual |

Urutan ini menjaga aplikasi tetap hijau pada setiap langkah: langkah 1–2 tidak menyentuh
otorisasi, langkah 3–5 menjaga kompatibilitas peran lama, langkah 6–7 hanya menambah izin,
dan langkah 8 murni tampilan.

---

## 6. Kriteria penerimaan global

1. `php artisan test` tetap hijau, termasuk `SuperAdminFullAccessTest` dan seluruh test audit.
2. Peran non-super-admin tetap tertolak di tempat yang seharusnya (`403`); pembukaan akses
   hanya terjadi melalui peran/scope yang diberikan.
3. Skema tetap kompatibel MySQL 8 (dev) dan PostgreSQL 17 (prod): UUID, `json`, tanpa
   `FIELD()`, tanpa `MAX(uuid)`, tanpa string literal berkutip ganda.
4. Semua migrasi bersifat aditif; `down()` tidak menghapus kolom atau data lama.
5. Dokumentasi manual pengguna (`docs/manual/`) diperbarui bersamaan dengan perubahan UI.
6. `php artisan permission:cache-reset` dijalankan setelah seeder peran/scope berubah.
7. `vendor/bin/pint`, `php artisan view:cache`, dan `npm run build` bersih sebelum commit.

---

## 7. Keputusan terbuka

| ID | Pertanyaan | Opsi | Usulan |
|----|-----------|------|--------|
| ADR-0014 | Apakah pegawai boleh memegang beberapa klinik tanpa batas? | (a) bebas; (b) dibatasi menurut jenis peran | (a) bebas, dengan tepat satu klinik utama |
| ADR-0015 | Pemetaan akun lama `ketua-tim-mutu` / `ketua-tim-audit` / `ketua-tim-akreditasi` ke katalog baru | (a) semua ke `kasatpel-ukp`; (b) dipetakan per akun saat migrasi; (c) dipertahankan sebagai peran legacy | (b) dengan `legacyMap()` tetap tersedia |
| ADR-0016 | Sumber "judul standar instrumen" pada penugasan | (a) turunan dari eksekusi; (b) kolom `instrument_version_id` pada `audit_assignments` | (b) dengan fallback (a) |
| ADR-0017 | Apakah scope `audit_internal` memberi hak approve/close? | (a) ya (penuh); (b) tidak, tetap butuh peran pengawas | (b) menjaga segregasi tugas |
| ADR-0018 | Nasib `user_clinic_scopes` bila akses diturunkan dari pegawai | (a) tetap sebagai cermin (cache) yang disinkronkan; (b) dihapus dan semua query memakai `employees` | (a) tanpa perubahan pada `SetClinicContext`, `ClinicScope`, dan `AuditWorkScope` |

---

## 8. Batas cakupan

Dokumen ini adalah **spesifikasi dan rencana**. Belum ada perubahan kode, migrasi, seeder,
atau tampilan yang dijalankan untuk keempat perbaikan di atas; seluruh referensi berkas dan
baris kode di sini menggambarkan kondisi aktual saat dokumen dibuat. Implementasi mengikuti
urutan §5 dan wajib memenuhi kriteria §6.

