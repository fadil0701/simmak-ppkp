# Matriks Role dan Permission — SIMMAK Audit Internal

> **Catatan status (26 Oktober 2026).** Bagian §1 adalah katalog *rancangan awal* pada fase
> desain. Katalog peran yang **benar-benar berlaku di kode** ada pada §5 di bawah
> (`App\Support\SimmakRoles::catalog()`), ditambah lapisan Scope Sistem (`App\Support\SimmakScopes`).

## 1. Role Awal

Berikut adalah role awal yang dapat dikonfigurasi. Role bukan struktur jabatan yang otomatis berlaku, tetapi rancangan awal yang dapat disesuaikan dengan kebijakan PPKP.

| No | Role | Deskripsi |
|----|------|-----------|
| 1 | Administrator Sistem (`super-admin`) | Akses penuh semua modul dan fitur |
| 2 | Kepala PPKP (`kepala-ppkp`) | Pimpinan berwenang, review dan persetujuan akhir |
| 3 | Koordinator Audit Internal (`koordinator-audit`) | Manajemen program dan penugasan audit |
| 4 | Ketua Tim Auditor (`ketua-tim-auditor`) | Manajemen tim, deklarasi independensi dan konflik |
| 5 | Auditor Internal (`auditor`) | Eksekusi audit, checklist, temuan (scope penugasan) |
| 6 | Reviewer Audit (`reviewer-audit`) | Review hasil eksekusi sebelum finalisasi |
| 7 | Verifikator Tindak Lanjut (`verifikator-tindak-lanjut`) | Verifikasi CAPA dan efektivitas |
| 8 | Kepala Klinik (`kepala-klinik`) | View data klinik sendiri, review temuan kliniknya |
| 9 | PIC Tindak Lanjut (`pic-tindak-lanjut`) | Eksekusi rencana CAPA |
| 10 | Pengamat / Pembaca Laporan (`pengamat`) | View-only laporan dan dashboard |

## 2. Permission Granular

Permission menggunakan konvensi `modul.aksi` dengan guard `web`.

### Audit Dashboard
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.dashboard.view` | Mengakses dashboard audit internal | super-admin, koordinator-audit, kepala-ppkp, kepala-klinik |

### Audit Program
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.program.view` | Melihat daftar program audit | semua role audit |
| `audit.program.create` | Membuat program audit baru | super-admin, koordinator-audit, pj-mutu |
| `audit.program.update` | Mengubah program audit | super-admin, koordinator-audit, pj-mutu |
| `audit.program.submit` | Mengajukan program untuk review | koordinator-audit, pj-mutu |
| `audit.program.approve` | Menyetujui program audit | super-admin, kepala-ppkp (segregasi tugas: tidak boleh yang membuat) |

### Audit Assignment
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.assignment.view` | Melihat penugasan audit | super-admin, koordinator-audit, kepala-klinik, auditor |
| `audit.assignment.manage` | Mengelola penugasan dan tim | super-admin, koordinator-audit, ketua-tim-auditor |

### Audit Standard / Instrument
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.standard.view` | Melihat standar dan instrumen | semua role audit |
| `audit.standard.manage` | Mengelola standar dan versi instrumen | super-admin, admin-mutu, pj-mutu |
| `audit.standard.review` | Review versi instrumen | admin-mutu, pj-mutu |
| `audit.standard.publish` | Menerbitkan versi instrumen | super-admin, admin-mutu |

### Audit Execution
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.execution.view` | Melihat eksekusi audit | semua role audit |
| `audit.execution.execute | Menjalankan eksekusi (checklist) | auditor |
| `audit.execution.submit` | Menyerahkan eksekusi untuk review | auditor |

### Audit Evidence
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.evidence.view` | Melihat daftar bukti audit | semua role audit |
| `audit.evidence.upload` | Mengunggah bukti audit | auditor, pj-mutu |
| `audit.evidence.download` | Mengunduh bukti audit | semua role audit (dengan otorisasi objek) |

### Audit Finding
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.finding.view` | Melihat temuan audit | semua role audit |
| `audit.finding.create` | Membuat temuan baru | auditor, pj-mutu |
| `audit.finding.update` | Mengubah temuan | auditor (scope sendiri), pj-mutu |
| `audit.finding.review` | Review temuan | reviewer-audit, kepala-klinik |
| `audit.finding.close` | Menutup temuan | kepala-klinik, verifikator-tindak-lanjut |
| `audit.finding.reopen` | Membuka kembali temuan | verifikator-tindak-lanjut, super-admin |

### CAPA
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.capa.view` | Melihat CAPA | semua role audit |
| `audit.capa.update` | Mengubah rencana CAPA | pic-tindak-lanjut, pj-mutu |
| `audit.capa.verify` | Memverifikasi CAPA | verifikator-tindak-lanjut |

### Reporting
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.report.publish` | Mengekspor laporan PDF/Excel | super-admin, admin-mutu, pj-mutu, kepala-klinik, kepala-ppkp |

### Audit Trail
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.log.view` | Melihat audit trail | super-admin, admin-mutu |

### Configuration
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.configuration.manage` | Manajemen konfigurasi audit | super-admin |

## 3. Prinsip Otorisasi

1. **Gabungan permission**: Hak akses adalah gabungan semua permission yang dimiliki melalui role.
2. **Scope klinik**: Pengguna hanya dapat mengakses objek klinik dalam scope yang ditetapkan (`user_clinic_scopes`).
3. **Penugasan auditor**: Auditor hanya dapat mengakses objek audit sesuai penugasan yang sah.
4. **Segregasi tugas**: Pimpinan tidak boleh mengubah bukti atau temuan yang telah difinalisasi. Admin sistem tidak otomatis memiliki kewenangan mengubah hasil audit.
5. **Verifikasi independen**: Pihak yang membuat temuan tidak boleh melakukan verifikasi akhir atas temuannya sendiri.
6. **Setiap operasi sensitif dicek di backend**: Hiding menu tidak cukup sebagai pengamanan.

## 4. Verifikasi Akses URL

Pengguna tidak dapat mengakses objek klinik lain dengan mengganti ID pada URL. Setiap controller memeriksa `organization_id` dan clinic scope sebelum mengembalikan data.

## 5. Katalog peran yang berlaku di kode (26 Oktober 2026)

Sumber tunggal: `App\Support\SimmakRoles::catalog()` — delapan peran yang dapat dipilih pada
form pengguna. `SimmakRoles::permissionsFor()` menentukan bundel izin tiap peran.

| No | Kode peran | Label UI | Basis kewenangan |
|----|------------|----------|------------------|
| 1 | `super-admin` | Super Admin (Akses Penuh Sistem) | Seluruh izin (`allPermissionNames()` ∪ `SimmakScopes::allPermissions()`) + bypass alur kerja & kepemilikan data |
| 2 | `admin-sistem` | Admin Sistem | Administrasi teknis: pengguna, pengaturan, master klinik/pegawai/jabatan, DMS, konfigurasi audit |
| 3 | `kepala-pusat` | Kepala Pusat | Monitoring lintas klinik + persetujuan akhir program/laporan dan jadwal penugasan (`audit.program.approve`) |
| 4 | `kasubbag-tu` | Kasubbag TU | Administrasi umum, kepegawaian, dokumen, pengaturan |
| 5 | `kasatpel-ukp` | Kasatpel UKP | Pengawasan mutu/audit lingkup UKP (bundel `$pengawasLingkup`) |
| 6 | `kasatpel-ukm` | Kasatpel UKM | Pengawasan mutu/audit lingkup UKM (bundel `$pengawasLingkup`) |
| 7 | `kepala-klinik` | Kepala Klinik/Unit | Dashboard klinik + tindak lanjut temuan/CAPA pada klinik yang dipegang |
| 8 | `admin-klinik` | Admin Klinik/Unit | Operasional klinik/unit: dokumen, bukti, tindak lanjut pada klinik yang dipegang |

Peran legacy yang **masih ada di database tetapi tidak assignable**
(`SimmakRoles::legacyRoleLabels()`): `ketua-tim-mutu`, `ketua-tim-audit`, `ketua-tim-akreditasi`,
`auditor`, `auditi`, plus alias `admin-mutu`, `pj-mutu`, `tim-akreditasi`, `staff` lewat
`SimmakRoles::legacyMap()`. Fungsi audit dipindahkan ke Scope Sistem (`auditor` / `auditi`).

## 6. Scope Sistem (lapisan izin tambahan)

Tabel `user_scope_assignments`; bundel izin di `App\Support\SimmakScopes::permissionsFor()`.

| Scope | Ringkasan izin |
|-------|----------------|
| `audit_internal` | `dashboard.executive`, `audit.view/manage`, seluruh `audit.program.*`, `audit.assignment.*`, `audit.standard.*`, `audit.execution.*`, `audit.evidence.*`, `audit.finding.*`, `audit.action.*`, `audit.report.*`, `audit.dashboard.view`, `capa.view/manage`, `audit.log.view` |
| `mutu` | `dashboard.executive`, `quality.view/manage`, `risk.view/manage`, `capa.view/manage`, `dms.view` |
| `akreditasi` | `dashboard.executive`, `accreditation.view/manage`, `dms.view/manage` |
| `monitoring` | `dashboard.executive`, `audit.dashboard.view`, seluruh izin `audit.*` berakhiran `.view`, `audit.log.view`, `capa.view`, `risk.view`, `quality.view`, `accreditation.view`, `dms.view`, `clinics.view`, `employees.view` |
| `auditor` | `dashboard.executive`, `audit.dashboard.view`, `audit.view/manage`, `audit.program.view`, `audit.assignment.view`, `audit.standard.view`, `audit.execution.view/execute/submit`, `audit.evidence.upload/view`, `audit.finding.view/create/update`, `audit.action.view/update`, `audit.capa.verify`, `audit.report.view`, `capa.view` |
| `auditi` | `dashboard.clinic`, `audit.view`, `audit.execution.view`, `audit.evidence.view/download`, `audit.finding.view`, `audit.action.view/update/submit`, `capa.view/manage` |

Aturan penerapan:

1. Izin efektif = izin peran ∪ izin scope. Scope tidak pernah mengurangi izin peran.
2. Scope **tidak** melewati segregasi tugas: `audit.program.approve`, `audit.finding.close`,
   `audit.finding.reopen`, `audit.capa.verify` tetap tunduk pada policy dan larangan self-approval.
3. Scope tidak melewati batas klinik: `AuditWorkScope` dan `User::hasClinicAccess()` tetap berlaku.
4. Scope diberikan sebagai izin langsung (`model_has_permissions`), dicatat `granted_by`/`granted_at`.
5. Setelah bundel scope berubah: `php artisan simmak:sync-user-scopes` lalu
   `php artisan permission:cache-reset`.

## 6a. Jangan tertukar: tiga lapisan yang sering tampak ganda

Label di antarmuka memakai istilah yang berbeda agar tidak diisi dua kali:

| Di UI | Sumber data | Bukan |
|-------|-------------|-------|
| **Peran jabatan** (form pengguna) | Spatie role, katalog `SimmakRoles` | Bukan jabatan master pegawai, bukan `team_role` di tim audit |
| **Tugas di sistem** (form pengguna; kode: Scope Sistem) | `user_scope_assignments` / `SimmakScopes` | Bukan daftar klinik, bukan anggota penugasan |
| **Klinik / unit kerja yang dipegang** (master pegawai) | `employee_clinic_assignments` | Sumber kebenaran batas klinik |
| **Batas data klinik** (form pengguna) | Cermin `user_clinic_scopes` | Jangan diisi ulang bila akun terhubung ke pegawai |
| **Klinik yang tampil pertama kali** | `users.default_clinic_id` | Hanya konteks layar saat masuk |
| **Peran dalam tim audit ini** (detail penugasan) | `audit_team_members.team_role` (`lead_auditor` / `auditor` / `observer`) | Bukan peran jabatan akun |

## 7. Akses klinik diturunkan dari master pegawai

- Sumber kebenaran: `employee_clinic_assignments` (banyak klinik per pegawai, tepat satu
  `is_primary`). Kolom lama `employees.clinic_id`/`unit_id` menyimpan klinik utama.
- `user_clinic_scopes` hanya **cermin** yang ditulis `App\Services\UserClinicScopeSynchronizer`,
  sehingga `SetClinicContext`, `ClinicScope`, dan `AuditWorkScope` tidak perlu diubah.
- Sinkronisasi dipicu saat pegawai dibuat/diubah/dihapus dan saat akun dihubungkan ke pegawai.
  Pegawai nonaktif mengosongkan scope klinik akunnya.
- Akun tanpa `employee_id` (akun teknis, mis. super admin) tetap memakai pilihan klinik manual.
