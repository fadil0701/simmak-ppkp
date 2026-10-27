# Register Keputusan Terbuka — SIMMAK Audit Internal Klinik

**ID:** ADR-0000  
**Judul:** Daftar keputusan yang belum ditetapkan (open decisions)  
**Status:** Proposed  
**Tanggal:** 19 Oktober 2026  
**Pemilik keputusan:** Product owner PPKP (dikonfirmasi dengan agen implementasi)  
**Referensi:** `PRD.md` §10, `ERD.md` §"Design decisions requiring confirmation", `IMPLEMENTATION_PLAN.md` Gate 1, `PERMISSION_MATRIX.md` §"Decisions for PPKP"

---

## Format

Setiap entri berikut mengikuti `DECISION_LOG_TEMPLATE.md` (ID, judul, status, tanggal, pemilik, konteks, opsi, keputusan, rasional, dampak keamanan/privasi, dampak migrasi data, dampak workflow/permission, uji yang diperlukan, dokumentasi yang diperbarui, tindak lanjut). Status awal seluruhnya: **Proposed**. Ubah menjadi **Accepted** setelah disetujui product owner, atau **Superseded** jika diganti.

---

## Daftar keputusan terbuka

| ID | Judul | Konteks / masalah | Opsi yang dipertimbangkan | Status | Dampak |
|----|-------|-------------------|---------------------------|--------|--------|
| ADR-0001 | Satu assignment per klinik atau multi-klinik | `ERD.md`: `audit_assignments.clinic_id` nullable; belum ditetapkan apakah satu assignment mencakup satu atau beberapa klinik | (a) satu assignment = satu klinik; (b) satu assignment = banyak klinik via pivot | Proposed | Tabel `audit_assignments`, scope izin auditor, tampilan kalender |
| ADR-0002 | Assessment berulang atau satu kali | Apakah checklist perlu penilaian berulang per eksekusi atau satu kali per kriteria | (a) satu response per `(execution, criterion)`; (b) response berulang dengan timestamp | Proposed | Tabel `checklist_responses`, unique constraint |
| ADR-0003 | Tahap approval & judul otorisasi | Belum dikonfirmasi siapa yang boleh approve setiap tahap dan judul resmi | (a) koordinator → reviewer → verifier → kepala klinik; (b) variasi per klinik | Proposed | Tabel `approval_events`, role `reviewer`/`verifier`, self-approval blokir |
| ADR-0004 | Role baru `reviewer` / `verifier` | PRD menyebutkan peran ini, seeder saat ini hanya 7 role | (a) role Spatie baru; (b) tangani lewak permission scope pada role `pj-mutu`/`auditor` | Proposed | Seeder, `SimmakMenu`, policy |
| ADR-0005 | Versi standar resmi yang berlaku | KMK HK.01.07/MENKES/1983/2022 & Permenkes 34/2022 masih `UNVERIFIED` | (a) verifikasi via JDIH Kemenkes terlebih dahulu; (b) biarkan draft | Proposed | Tabel `regulatory_sources`, `instrument_versions`, status verifikasi |
| ADR-0006 | Aturan skoring / bobot / ambang | Hanya jika instrumen resmi secara eksplisit menentukannya (PRD non-goal #6) | (a) ambang dari sumber resmi; (b) kebijakan internal PPKP; (c) tanpa skoring otomatis | Proposed | Tabel `standard_criteria.scoring_rule`, UI perhitungan |
| ADR-0007 | Retention & archival evidence | Kebijakan penyimpanan & pencatatan arsip belum ditetapkan | (a) waktu tetap; (b) berdasarkan kriteria temuan; (c) ditunda | Proposed | Tabel `evidence`, `audit_trails`, skrip arsip |
| ADR-0008 | Notifikasi email vs in-app | PRD: email opsi jika tersedia/dikonfigurasi | (a) in-app saja; (b) in-app + email jika MAIL dikonfigurasi | Proposed | `routes/console.php`, jadwal notifikasi |
| ADR-0009 | Format & tanda tangan PDF/Excel | Belum ada format yang disetujui PPKP | (a) sederhana tanpa tanda tangan; (b) e-signature BSrE | Proposed | `app/Services/AuditReportService`, view laporan |
| ADR-0010 | Identitas provider & RBAC efektif | `LDAP_ENABLED=false`; identitas lokal saat ini | (a) tetap lokal; (b) aktifkan LDAP/AD setelah disetujui | Proposed | `config/sanctum.php`, `User.isLdapAuth`, login |
| ADR-0011 | Master klinik resmi & jabatan | 16 klinik adalah data contoh seeder, belum divalidasi sebagai master resmi PPKP | (a) gunakan data seeder sebagai master awal; (b) konfirmasi daftar resmi PPKP | Proposed | `database/seeders/DatabaseSeeder`, data klinik produksi |
| ADR-0012 | Komposisi dashboard utama (eksekutif vs audit task-oriented) | `UI_UX_SPEC.md` mensyaratkan dashboard task-oriented (tasks due, overdue CAPA, pending reviews, audits by status, risk priority, recent activity). Dashboard eksekutif saat ini menonjolkan kesiapan akreditasi yang merupakan fase lanjutan (out of scope Release 1) dan menggunakan 4 kartu KPI berderet. Dashboard audit (`audit/dashboard`) sudah task-oriented dan sesuai spec tetapi bukan landing default | (a) jadikan dashboard audit sebagai landing default untuk seluruh peran audit; (b) tambah elemen task-oriented ke dashboard eksekutif tanpa menghapus konten akreditasi/risk; (c) pertahankan dua dashboard terpisah, eksekutif hanya untuk kepala/manajemen | Proposed | `SimmakMenu::homeRouteFor()`, `DashboardController`, route `dashboard` vs `audit.dashboard`, `resources/views/dashboard/index.blade.php` |
| ADR-0014 | Batas jumlah klinik per pegawai | Master pegawai saat ini hanya menyimpan satu `clinic_id`/`unit_id`, sedangkan akun bisa diberi banyak klinik via `user_clinic_scopes` → dua sumber kebenaran | (a) pegawai boleh memegang banyak klinik tanpa batas, tepat satu klinik utama; (b) dibatasi menurut jenis peran | Proposed — usulan (a) | Tabel pivot `employee_clinic_assignments`, `Employee`, `EmployeeController`, form pegawai |
| ADR-0015 | Pemetaan peran legacy ke katalog 8 peran | Katalog resmi: super-admin, admin-sistem, kepala-pusat, kasubbag-tu, kasatpel-ukp, kasatpel-ukm, kepala-klinik, admin-klinik. Peran lama `ketua-tim-mutu`/`ketua-tim-audit`/`ketua-tim-akreditasi`/`pj-mutu` belum punya padanan tunggal | (a) semua ke `kasatpel-ukp`; (b) dipetakan per akun saat migrasi; (c) dipertahankan sebagai peran legacy | Proposed — usulan (b) dengan `SimmakRoles::legacyMap()` tetap ada | `SimmakRoles`, seeder RBAC, form pengguna |
| ADR-0016 | Sumber judul standar/instrumen pada penugasan | `audit_assignments` tidak punya kolom instrumen; instrumen baru tercatat pada `audit_executions.instrument_version_id` | (a) turunkan dari eksekusi (tanpa migrasi); (b) tambah `audit_assignments.instrument_version_id` | Proposed — usulan (b) dengan fallback (a) | Tabel `audit_assignments`, `AuditAssignmentService`, indeks penugasan |
| ADR-0017 | Kewenangan scope `audit_internal` | Scope sistem (audit internal, mutu, akreditasi, monitoring, auditor, auditi) menambah izin di atas peran | (a) scope `audit_internal` memberi hak penuh termasuk approve/close; (b) hanya hak kelola, approve/close tetap butuh peran pengawas | Proposed — usulan (b) menjaga segregasi tugas | `SimmakScopes`, `user_scope_assignments`, policy audit |
| ADR-0018 | Nasib `user_clinic_scopes` setelah akses diturunkan dari pegawai | Akses klinik akan dihitung dari klinik pegawai, tetapi `user_clinic_scopes` dipakai `SetClinicContext`, `ClinicScope`, dan `AuditWorkScope` | (a) tetap sebagai cermin (cache) yang disinkronkan dari pegawai; (b) dihapus dan semua query membaca `employees` | Proposed — usulan (a) agar otorisasi tidak berubah | `user_clinic_scopes`, `User::clinicScopeIds()`, `SetClinicContext` |

---

## Dampak untuk implementasi

- Tidak ada entri di atas yang diimplementasikan sebagai final sebelum status berubah ke **Accepted**.
- Selama status **Proposed**, kode akan menggunakan asumsi terkecil yang dapat dibalik (add-only, nullable, default aman).
- Tidak ada klaim bahwa regulasi Kemenkes atau instrumen audit telah terverifikasi; semua sumber regulasi tetap **UNVERIFIED** sampai dokumen resmi diperiksa melalui JDIH Kemenkes / sumber resmi (lihat `REGULATORY_REGISTER.md`).

### Status per 26 Oktober 2026 — ADR-0014…ADR-0018 sudah diimplementasikan dengan opsi usulan

Kelima entri tetap **Proposed** (belum disetujui product owner), tetapi implementasi teknisnya
sudah berjalan memakai opsi usulan yang tercatat, karena semuanya aditif dan dapat dibalik:

| ADR | Opsi yang dipakai kode | Artefak |
|-----|------------------------|---------|
| ADR-0014 | (a) banyak klinik per pegawai tanpa batas, tepat satu klinik utama | `employee_clinic_assignments`, `Employee::syncClinicAssignments()` |
| ADR-0015 | (b) `legacyMap()` dipertahankan + pemetaan per akun saat migrasi; peran lama tidak assignable | `SimmakRoles::catalog()` / `legacyRoleLabels()` |
| ADR-0016 | (b) kolom `audit_assignments.instrument_version_id` + fallback turunan eksekusi | migrasi `2026_10_26_100003_*`, `AuditAssignment::instrumentTitle()` |
| ADR-0017 | (b) scope hanya hak kelola; approve/close/reopen/verify tetap butuh peran pengawas | `SimmakScopes` (tanpa `audit.program.approve`, `audit.finding.close/reopen`) |
| ADR-0018 | (a) `user_clinic_scopes` dipertahankan sebagai cermin tersinkronisasi | `UserClinicScopeSynchronizer` |

Bila product owner memilih opsi berbeda, perubahan terbatas pada berkas di kolom "Artefak" —
tidak ada perubahan pada `SetClinicContext`, `ClinicScope`, atau `AuditWorkScope`.

---

## Tindak lanjut

1. Product owner meninjau dan menetapkan status setiap entri.
2. Untuk entri yang **Accepted**, buat ADR terpisah (`docs/decisions/ADR-0001.md`, dst.) dengan rincian penuh dan update register ini.
3. Implementasi mengikuti keputusan yang sudah ditetapkan; jangan menyelisihi tanpa update register.
