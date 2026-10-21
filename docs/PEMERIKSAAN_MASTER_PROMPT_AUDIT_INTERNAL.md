# Pemeriksaan Sistem — SIMMAK Audit Internal Klinik PPKP DKI Jakarta

**Status:** Pemeriksaan read-only terhadap kode, migrasi, rute, kebijakan akses, seeder, tes, dan bukti terminal  
**Tanggal:** 19 Oktober 2026  
**Acuan:** Master Prompt Pembangunan SIMMAK Audit Internal Klinik; skill `simmak-audit-internal-clinic`; referensi PRD, ERD, matriks izin, alur status, UI/UX, tes penerimaan, dan register regulasi  
**Lingkungan yang diamati:** Laravel 12, PHP 8.2+, PostgreSQL 17 (Docker), MySQL (Laragon dev), Blade + Tailwind v4  
**Pernyataan batasan:** Dokumen ini bukan sertifikasi keamanan, bukan bukti verifikasi regulasi Kemenkes, dan bukan laporan uji end-to-end di browser. Regulasi pada register tetap **UNVERIFIED** sampai sumber resmi ditinjau.

---

## 1. Kesimpulan

Sistem **belum selesai** dan **belum memenuhi definisi selesai** master prompt.

Fondasi aplikasi (Laravel, autentikasi, master klinik, RBAC Spatie, layout SIMMAK) sudah ada. Potongan modul Audit Internal sudah dikoding (standar, program, penugasan, eksekusi, checklist, bukti, temuan, CAPA, dashboard). Namun:

- alur audit belum dapat dituntaskan dari perencanaan sampai penutupan temuan dengan aturan bisnis yang wajib;
- pembatasan akses belum terbukti berfungsi;
- migrasi PostgreSQL gagal di tengah jalan;
- seeder izin granular tidak terpasang pada alur seed utama;
- laporan PDF/Excel audit tidak ada;
- pengujian otomatis tidak mencakup alur lengkap dan berisiko merusak database `.env`.

**Release 1 belum siap produksi.**

---

## 2. Definisi selesai

| Syarat selesai | Hasil | Keterangan |
|---|---|---|
| Aplikasi dapat dijalankan | PARTIAL | Stack ada; migrasi Docker gagal pada tabel seksi standar |
| Alur audit perencanaan → penutupan temuan | FAIL | Potongan CRUD dan transisi ada; rantai belum utuh |
| Pembatasan akses terbukti berfungsi | FAIL | Policy ada; seeder izin tidak terpanggil; tes rapuh |
| Data historis terjaga | PARTIAL | Eksekusi menyimpan `instrument_version_id`; jawaban checklist ditimpa |
| Laporan sesuai database | FAIL | Tidak ada ekspor PDF/Excel audit |
| UI profesional dan konsisten | PARTIAL | Layout `simmak` dipakai; masih kartu KPI, menu rusak, modul masa depan aktif |

---

## 3. Temuan lingkungan (bukti terminal)

### 3.1 Migrasi PostgreSQL gagal

Perintah: `docker compose exec app php artisan migrate`

Hasil:

- `2026_10_19_100001_create_regulatory_sources_table` — berhasil
- `2026_10_19_100002_create_audit_standards_table` — berhasil
- `2026_10_19_100003_create_instrument_versions_table` — berhasil
- `2026_10_19_100004_create_standard_sections_table` — **gagal**

Galat PostgreSQL: relasi `standard_sections` tidak ada saat `ALTER TABLE` menambah FK `parent_id` yang merujuk ke tabel itu sendiri.

Penyebab yang paling mungkin: `foreignUuid('parent_id')` di dalam `Schema::create` membuat Laravel mengeksekusi `ALTER TABLE` sebelum tabel terlihat pada sesi PostgreSQL. Pola serupa dipakai pada `departments` di migrasi inti.

Perbaikan yang aman (belum dikerjakan pada pemeriksaan ini):

1. Gunakan `$table->uuid('parent_id')->nullable()` tanpa `foreignUuid`.
2. Tambahkan FK self-reference setelah `Schema::create` benar-benar selesai, atau pada migrasi terpisah.
3. Perbaiki juga `$table->string('response_value', 40)->nullable` (tanpa `()`) pada `checklist_responses` agar kolom benar-benar nullable.

Database Docker saat ini **setengah terpasang**. Jangan menjalankan `migrate:fresh` pada data yang ingin dipertahankan.

### 3.2 Seeder gagal

Perintah: `docker compose exec app php artisan db:seed --force`

Galat: pelanggaran unik `organizations_code_unique`, kunci `(code)=(PPKP-DKI)` sudah ada.

Penyebab:

- `DatabaseSeeder` memakai `Organization::create()` tanpa `firstOrCreate`.
- `AuditPermissionSeeder` dan `ClinicPermissionSeeder` **tidak dipanggil** dari `DatabaseSeeder`.
- Seed paksa pada basis data yang sudah berisi organisasi akan selalu pecah.

---

## 4. Skor output wajib master prompt

| # | Deliverable | Status | Catatan |
|---|---|---|---|
| 1 | Aplikasi Audit Internal yang berjalan | PARTIAL | Rute dan view ada; migrasi PG dan seeder izin rusak |
| 2 | Struktur database dan migrasi PostgreSQL | PARTIAL | 15 migrasi baru; self-FK dan `nullable` checklist bermasalah |
| 3 | Master standar dan checklist yang dapat dikelola | PARTIAL | CRUD standar/versi ada; tidak ada seeder instrumen; status sumber default UNVERIFIED di service |
| 4 | Autentikasi dan RBAC dengan scope klinik | PARTIAL | Spatie + policy; izin granular tidak di-seed; isolasi tidak konsisten |
| 5 | Workflow audit lengkap | PARTIAL | Program, assignment, execution punya transisi; temuan dapat ditutup tanpa verifikasi |
| 6 | Pengelolaan bukti dan temuan | PARTIAL | Unggah dan metadata ada; unduhan longgar; view `show` bukti tidak ada |
| 7 | Tindak lanjut CAPA dan verifikasi | PARTIAL | CAPA, RCA, verifikasi ada; pemisahan tugas hanya di sebagian titik |
| 8 | Dashboard berbasis data aktual | PARTIAL | Query nyata; 6 kartu KPI; tanpa navigasi ke rincian pembentuk angka |
| 9 | Laporan PDF dan Excel | FAIL | Hanya daftar `AuditReport`; tidak ada paket atau ekspor audit |
| 10 | Audit trail | PARTIAL | Model mencoba append-only di Eloquent; hampir tidak dipanggil pada aksi audit |
| 11 | Seeder demonstrasi | FAIL | Tidak ada program, instrumen, checklist, atau temuan demo modul baru |
| 12 | Automated tests | FAIL | Tiga berkas tes audit; `RefreshDatabase` menyasar DB `.env`; cakupan alur hampir nol |
| 13 | Dokumentasi arsitektur dan ERD | PARTIAL | Ada di referensi skill dan `docs/`; tidak selalu sinkron dengan kode |
| 14 | Dokumentasi instalasi dan konfigurasi | PARTIAL | `AGENTS.md`, `docs/DEPLOY_DOCKER.md` |
| 15 | Matriks role dan permission | PARTIAL | Dokumen ada; role reviewer, verifikator, PIC, pengamat belum di-seed |
| 16 | Daftar regulasi dan status verifikasi | PARTIAL | `REGULATORY_REGISTER.md` menandai UNVERIFIED — sesuai aturan, jangan diklaim resmi |
| 17 | Laporan hasil pengujian | FAIL | Tidak ada bukti suite hijau untuk alur audit |
| 18 | Daftar keterbatasan atau pekerjaan belum selesai | PARTIAL | Keputusan terbuka tercatat di `docs/decisions/ADR-0000-open-decisions.md` |

---

## 5. Pemeriksaan modul A–I

### A. Dashboard Audit Internal

**Ada:** `AuditDashboardService` menghitung dari database: jumlah program, eksekusi berjalan, temuan terbuka, CAPA terlambat, antrian tinjauan, temuan kritis, aktivitas dari `audit_trails`.

**Belum memenuhi:**

- Enam kartu statistik tanpa tautan ke data detail yang membentuk angka.
- Tidak ada tren periode, temuan berulang, persentase verifikasi, atau kalender.
- Angka di-scope organisasi, bukan penugasan atau klinik pengguna.
- Permission `audit.dashboard.view` tidak ada pada daftar `AuditPermissionSeeder`.

### B. Program dan jadwal audit

**Ada:** CRUD program; pengajuan, persetujuan, pengembalian; pemisahan tugas pembuat tidak boleh menyetujui.

**Belum memenuhi:** jenis audit (berkala, tematik, khusus, ulang); kalender; filter klinik/periode/jenis/status/penanggung jawab; peringatan konflik jadwal; riwayat revisi program; pencatatan `approval_events`.

### C. Penugasan auditor

**Ada:** assignment, anggota tim, peran, deklarasi independensi dan konflik, aktivasi hanya jika `conflict_check_status = resolved`.

**Belum memenuhi:** deteksi konflik otomatis; peringatan penugasan ganda; indeks pelaksanaan di menu; pembatasan akses auditor hanya ke objek penugasan yang sah.

`AuditExecutionPolicy::view` hanya memeriksa organisasi dan `hasClinicAccess`, bukan keanggotaan tim. Auditor dengan scope klinik dapat membuka checklist yang bukan penugasannya.

### D. Instrumen dan checklist

**Ada:** hierarki sumber regulasi → standar → versi instrumen → seksi → kriteria; status draf/tinjauan/disahkan/aktif/diarsipkan; jawaban checklist dengan alasan untuk tidak berlaku dan tidak dapat dinilai.

**Belum memenuhi:**

- Riwayat perubahan jawaban (dipakai `updateOrCreate`, data lama tertimpa).
- Autosave, validasi kelengkapan sebelum penyerahan, finalisasi yang mengunci jawaban.
- Penyalinan/snapshot isi instrumen; yang dipegang eksekusi hanya `instrument_version_id`.
- Menu sidebar **Pelaksanaan Audit** memakai rute `audit.executions.checklist` yang membutuhkan parameter `{execution}` — URL sidebar akan pecah.

### E. Pengelolaan bukti

**Ada:** validasi ukuran, ekstensi, magic bytes, SHA-256, nama simpan acak, disk privat, opsi pindai ClamAV dengan cadangan jika layanan tidak tersedia.

**Belum memenuhi:**

- Unduhan hanya memeriksa `organization_id`, tidak memanggil `authorize('download')`, tidak memeriksa klinik atau penugasan.
- `EvidencePolicy::download` sendiri juga hanya cek organisasi.
- Tidak ada `resources/views/audit/evidence/show.blade.php` padahal rute `show` ada.
- `audit_execution_id` pada unggahan tidak divalidasi `exists` dan tidak di-scope.

### F. Temuan dan rekomendasi

**Ada:** kode temuan, klinik, jenis ketidaksesuaian/observasi/peluang perbaikan, kondisi, persyaratan, ringkasan bukti, dampak, rekomendasi, tanggapan, PIC, tenggat, tinjauan konfirmasi atau pengembalian.

**Belum memenuhi:**

- Penutupan hanya membutuhkan alasan; tidak mensyaratkan CAPA atau verifikasi.
- Tidak ada eskalasi temuan kritis terkait keselamatan pasien.
- Tidak ada jejak audit pada pembuatan, perubahan, penutupan, atau pembukaan kembali.
- Bug: `AuditFindingController::update` memakai variabel `$orgId` yang tidak didefinisikan — sunting temuan akan gagal.

### G. Tindak lanjut dan CAPA

**Ada:** pembuatan CAPA dari temuan, analisis akar masalah, verifikasi efektif/tidak efektif, PIC tidak boleh memverifikasi kasus miliknya sendiri pada controller.

**Belum memenuhi:** pemisahan tindakan terencana vs terlaksana; pengingat keterlambatan dan eskalasi; pemanggilan policy `verify` (rute memakai `capa.manage`); penutupan temuan otomatis tidak terjadi dari unggahan, tetapi penutupan manual juga tidak mensyaratkan bukti.

### H. Verifikasi dan penutupan

**Ada:** hasil verifikasi efektif / tidak efektif / pending; pengembalian status CAPA ke verifikasi jika belum efektif.

**Belum memenuhi:** pemeriksa independen dari pembuat temuan; pemisahan pelaksana, verifikator, dan pemberi persetujuan sebagai role; alasan wajib pada setiap pengembalian yang tercatat di `approval_events`.

### I. Laporan audit

**Ada:** halaman daftar laporan lama (`AuditReport`) dengan ringkasan.

**Belum memenuhi:** filter periode/klinik/jenis/auditor/standar/status/risiko; ekspor PDF dan Excel; identitas dokumen final (versi, tanggal terbit, pengesahan). Fitur ini **gagal** terhadap master prompt.

---

## 6. Ketentuan teknis dan arsitektur

| Prinsip | Hasil | Keterangan |
|---|---|---|
| Modular, logic di service | PARTIAL | Service ada; controller masih mencampur validasi dan transisi status |
| Form Request | FAIL | Tidak ada `app/Http/Requests` |
| Transaksi database | PARTIAL | Dipakai pada simpan jawaban checklist |
| FK, unique, index | PARTIAL | Ada pada migrasi baru; self-FK PG gagal |
| Policy + permission middleware | PARTIAL | Policy terdaftar; banyak aksi masih `abort_unless` organisasi saja |
| Soft delete selektif | PARTIAL | Dipakai luas; kebijakan retensi belum ditetapkan |
| Factory dan tes | FAIL | Hanya `UserFactory`; tes audit tidak memadai |
| Tidak mengunci satu jenis klinik atau satu versi standar | PASS | Master klinik dan versi instrumen dapat dikonfigurasi |
| Tidak membangun Mutu/Akreditasi penuh | FAIL | Menu dan rute mutu/akreditasi tetap aktif |

Stack sesuai standar awal: Laravel 12, PHP 8.2+, PostgreSQL 17, Blade, Tailwind v4, PHPUnit, Docker. Livewire terpasang tetapi tidak dipakai pada modul audit (diterima jika konsisten). Tidak ada pustaka PDF/Excel.

---

## 7. Standar Kemenkes dan instrumen

`AuditStandardService::createRegulatorySource` menetapkan `verification_status = UNVERIFIED`. Register regulasi mencantumkan KMK HK.01.07/MENKES/1983/2022 dan Permenkes 34/2022 sebagai **lead yang belum diverifikasi**.

Tidak ada klaim di kode bahwa instrumen produksi telah disahkan dari JDIH. Tidak ada seeder isi elemen penilaian resmi. Hal ini sesuai aturan “jangan mengarang ketentuan hukum”, tetapi juga berarti **instrumen produksi belum ada**.

---

## 8. Role, permission, dan otoritas

Izin granular didefinisikan di `AuditPermissionSeeder` (`audit.program.*`, `audit.assignment.*`, `audit.execution.*`, `audit.finding.*`, dan seterusnya), tetapi seeder itu **tidak dijalankan** dari `DatabaseSeeder`. Rute web sudah memakai middleware `permission:...`. Setelah seed biasa, hampir semua menu audit baru akan mengembalikan 403.

Role master prompt yang belum ada sebagai role Spatie: Koordinator Audit Internal, Ketua Tim Auditor, Reviewer, Verifikator, PIC Tindak Lanjut, Pengamat. Role yang di-seed tetap: `super-admin`, `admin-mutu`, `pj-mutu`, `auditor`, `kepala-klinik`, `staff`, `tim-akreditasi`.

Celah otoritas:

- `hasClinicAccess` tidak mengecualikan administrator; `ClinicScope` menembus semua klinik jika pengguna punya `clinics.manage`. Daftar dan detail tidak konsisten.
- Model `Clinic` memakai trait `ClinicScope` yang memfilter kolom `clinic_id`. `Clinic::forUserClinics()` merujuk kolom yang tidak ada.
- Administrator teknis masih dapat menerima `audit.finding.close` jika seluruh permission di-sync.
- Perubahan role dan permission tidak tercatat di audit trail.
- Menyembunyikan menu bukan pengamanan; beberapa unduhan dan mutasi belum memanggil policy objek.

---

## 9. Workflow dan status

Transisi yang sudah divalidasi di service (bukan status bebas dari klien) antara lain:

- Program: `draft` → `submitted_for_review` → `approved` / `returned_for_revision`
- Assignment: `draft` → `proposed` → `active` (syarat konflik terselesaikan)
- Execution: `preparation` → `in_progress` → `submitted_for_review` → `finalized`
- Instrumen: `draft` → `review` → `approved` → `active` (versi aktif lain diarsipkan)

Yang belum:

- Alur temuan dapat meloncat ke `closed` tanpa CAPA/verifikasi.
- `ApprovalEvent` punya model dan policy, tetapi tidak ditulis pada aksi bisnis.
- Pembatalan/pengembalian dengan alasan dan jejak tidak lengkap (alasan return program disisipkan tersembunyi di tampilan).
- Tidak semua jenis audit dapat dikonfigurasi alurnya.

---

## 10. UI/UX

Arah visual memakai `layouts.simmak` (navy/teal, bukan SPA template ungu). Masih di bawah syarat “enterprise pemerintahan, task-first, tidak generik”:

- Dashboard audit didominasi kartu KPI berukuran sama.
- Tidak ada breadcrumb.
- Menu pelaksanaan pecah karena rute membutuhkan ID eksekusi.
- Judul halaman program di-set sebagai string literal `{{ $program->program_code }}` (tidak menampilkan kode program).
- Menu Program Mutu dan Akreditasi aktif, bertentangan dengan larangan menu hidup tanpa fungsi tahap 1.
- Tidak ada pemeriksaan desktop/mobile di browser pada sesi pemeriksaan ini.

---

## 11. Database

Tabel baru yang dirancang: `regulatory_sources`, `audit_standards`, `instrument_versions`, `standard_sections`, `standard_criteria`, `audit_assignments`, `audit_team_members`, `audit_executions`, `checklist_responses`, `evidence`, `response_evidence`, `finding_evidence`, `action_evidence`, `approval_events`, plus kolom tambahan pada `audit_findings`.

Tabel lama yang dipakai ulang: `clinics`, `users`, `audit_programs`, `audit_schedules`, `audit_findings`, `capa_cases`, `capa_actions`, `capa_verifications`, `audit_trails`, tabel Spatie.

Kesenjangan terhadap daftar entitas master prompt: tidak ada tabel riwayat jawaban checklist, komentar temuan, notifikasi audit, atau laporan ekspor khusus. `approval_events` belum dipakai.

Klinik 16 unit berasal dari seeder master (dapat dikonfigurasi), bukan hard-code di logika bisnis. Nama seeder bersifat demonstrasi, belum master resmi PPKP (ADR-0011).

---

## 12. Keamanan, privasi, dan audit trail

Sudah ada pada kode saat ini:

- CSRF pada formulir web
- Throttle login web `5,1` dan login API
- Sanctum `expiration` 480 menit
- `.env` tercantum di `.gitignore` dan `.dockerignore` (satu berkas env, tanpa `.env.docker`)
- Permission middleware pada sebagian endpoint API DMS

Masih terbuka:

- Jejak audit tidak append-only di basis data (hanya override Eloquent `update`/`delete`)
- Jejak hampir tidak dipanggil pada program, assignment, checklist, temuan, persetujuan
- Unduhan bukti longgar
- Tidak ada uji keamanan otomatis yang kredibel untuk modul audit
- Backup/restore, header keamanan Nginx, dan ClamAV runtime **tidak diuji** pada pemeriksaan ini

Data pasien nyata tidak ditemukan di seeder. Field teks temuan dan bukti tetap dapat diisi identitas pasien oleh pengguna — kebijakan minimalisasi belum ditegakkan di validasi.

---

## 13. Integrasi masa depan

Arsitektur organisasi/klinik/dokumen/CAPA dapat dipakai ulang. Relasi eksplisit temuan audit ke indikator mutu atau elemen akreditasi **belum disediakan secara terkendali**. Menu mutu dan akreditasi yang aktif berisiko dipahami sebagai modul selesai.

---

## 14. Testing vs kriteria penerimaan

Berkas tes yang ada:

- `tests/Feature/Audit/AuthorizationTest.php`
- `tests/Unit/Audit/AuditTrailTest.php`
- `tests/Unit/Audit/EvidenceSecurityTest.php`

Masalah:

- `phpunit.xml` tidak memakai sqlite; `RefreshDatabase` dapat menghapus database yang ditunjuk `.env`.
- Role `staff` dan `pj-mutu` dipakai di tes tetapi tidak selalu di-seed di `setUp`.
- Tes “auditor hanya mengakses eksekusi yang ditugaskan” tidak menambahkan anggota tim lalu mengharapkan HTTP 200 — tidak menguji aturan penugasan.
- Tidak ada tes alur lengkap, ekspor, IDOR unduhan, freeze instrumen, atau transisi status ilegal.

Daftar di `references/ACCEPTANCE_TESTS.md` seluruhnya masih belum dicentang.

---

## 15. Keputusan bisnis yang masih terbuka

Tercatat di `docs/decisions/ADR-0000-open-decisions.md` (status Proposed), antara lain:

- satu assignment satu klinik vs multi-klinik
- role reviewer/verifier
- aturan skoring
- retensi bukti
- notifikasi
- format laporan PDF/Excel
- master klinik resmi

Kode sudah memilih beberapa asumsi (satu jawaban per kriteria per eksekusi; SoD persetujuan program) tanpa ADR berstatus Accepted.

---

## 16. Daftar bug dan celah yang teridentifikasi

| ID | Severity | Uraian |
|---|---|---|
| B-01 | Critical | Migrasi PG `standard_sections` gagal; skema Docker tidak lengkap |
| B-02 | Critical | `DatabaseSeeder` tidak idempotent; seed Docker gagal |
| B-03 | High | `AuditPermissionSeeder` tidak dipanggil; rute audit baru 403 setelah seed biasa |
| B-04 | High | Permission `audit.dashboard.view` tidak didefinisikan di seeder |
| B-05 | High | Menu pelaksanaan memakai rute berparameter tanpa ID |
| B-06 | High | `AuditFindingController::update` memakai `$orgId` tak terdefinisi |
| B-07 | High | Penutupan temuan tanpa verifikasi CAPA |
| B-08 | High | View eksekusi tidak membatasi ke anggota tim |
| B-09 | High | Unduhan bukti hanya cek organisasi |
| B-10 | Medium | View `audit/evidence/show` tidak ada |
| B-11 | Medium | `ClinicScope` pada model `Clinic` memfilter `clinic_id` yang tidak ada |
| B-12 | Medium | `checklist_responses.response_value` memakai `->nullable` tanpa pemanggilan metode |
| B-13 | Medium | Jawaban checklist menimpa riwayat |
| B-14 | Medium | Audit trail tidak tercatat pada aksi audit inti |
| B-15 | Medium | Tidak ada ekspor PDF/Excel audit |
| B-16 | Low | Judul halaman program tidak terinterpolasi |
| B-17 | Low | Menu mutu dan akreditasi aktif di tahap 1 |

---

## 17. Prioritas perbaikan

1. Perbaiki migrasi PostgreSQL `standard_sections` dan `nullable()` pada `checklist_responses`; selesaikan migrate tanpa `fresh` jika data perlu dipertahankan.
2. Pasang seeder izin (`AuditPermissionSeeder`, izin klinik, `audit.dashboard.view`) dan buat seed organisasi idempotent.
3. Perbaiki bug blocker: `$orgId` pada update temuan, menu pelaksanaan, view bukti `show`, `ClinicScope` pada model `Clinic`.
4. Tutup alur: assignment → execution terikat versi instrumen → checklist berriwayat → temuan → CAPA → verifikasi independen → penutupan yang mensyaratkan verifikasi; tulis `approval_events` dan audit trail.
5. Kunci akses: baca/unduh berdasarkan penugasan dan klinik; panggil policy pada setiap aksi; administrator tidak otomatis mengubah hasil final.
6. Uji pada database terpisah: IDOR, pemisahan tugas, workflow, unggah/unduh.
7. Laporan PDF/Excel dengan scope; dashboard antrian kerja dengan tautan ke rincian.
8. Nonaktifkan atau sembunyikan menu Mutu dan Akreditasi untuk Release 1.
9. Setelah itu: kalender, notifikasi, seeder demo berstatus UNVERIFIED, dan tinjauan UI.

---

## 18. Pernyataan limitasi

Pemeriksaan ini berdasarkan pembacaan sumber, migrasi, rute, kebijakan, seeder, tes, dan keluaran terminal migrasi/seeder Docker. Tidak dilakukan uji penetrasi, uji browser end-to-end, atau verifikasi dokumen resmi Kemenkes. Keputusan penerimaan risiko Residual Critical/High ada pada pemilik proses PPKP.
