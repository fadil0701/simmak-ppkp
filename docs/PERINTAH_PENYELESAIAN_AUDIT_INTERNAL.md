# Perintah Pengerjaan — Penyelesaian Modul Audit Internal Klinik

**Status:** Perintah kerja Release 1  
**Tanggal:** 19 Oktober 2026  
**Prioritas:** Selesaikan **hanya** modul dan fitur Audit Internal terlebih dahulu  
**Acuan:** `docs/PEMERIKSAAN_MASTER_PROMPT_AUDIT_INTERNAL.md`, skill `.agents/skills/simmak-audit-internal-clinic/`, `references/IMPLEMENTATION_PLAN.md`, `references/ACCEPTANCE_TESTS.md`  
**Pemilik eksekusi:** tim implementasi / coding agent  
**Pemilik keputusan bisnis:** product owner PPKP

---

## 1. Perintah utama

Kerjakan sampai modul **Audit Internal Klinik** dapat dijalankan, diuji, dan dituntaskan end-to-end:

**Program → persetujuan → penugasan → pelaksanaan checklist → bukti → temuan → CAPA → verifikasi → penutupan.**

Jangan memperluas atau merapikan modul **Manajemen Mutu**, **Manajemen Akreditasi**, **Risiko enterprise**, atau operasional klinik lain sebelum perintah ini selesai dan kriteria penerimaan Release 1 terpenuhi.

Nonaktifkan atau sembunyikan menu Mutu dan Akreditasi dari sidebar Release 1 (bug B-17). Jangan menghapus kode yang sudah ada; cukup jangan tampil sebagai menu hidup.

---

## 2. Aturan wajib selama pengerjaan

1. Baca skill `simmak-audit-internal-clinic` dan hasil pemeriksaan sebelum mengubah berkas.
2. Gunakan kode yang sudah ada. Jangan membuat sistem RBAC, master klinik, atau tabel CAPA kedua.
3. Perubahan skema hanya lewat **migrasi aditif**. Jangan `migrate:fresh`, `db:wipe`, atau reset database Docker/produksi.
4. Jangan menimpa perubahan lokal yang tidak terkait.
5. Jangan mengarang nomor regulasi, elemen penilaian, bobot, atau ambang Kemenkes. Instrumen demo berstatus `UNVERIFIED` / `draft`.
6. Jangan menaruh data pasien nyata di seeder, tes, atau tangkapan layar.
7. Setiap slice wajib membawa: migrasi (jika perlu), policy, service, UI yang berfungsi, audit trail, dan tes.
8. Jangan menyatakan selesai sebelum perintah uji pada slice itu dijalankan dan hasilnya dicatat.
9. Jangan menjalankan tes dengan `RefreshDatabase` terhadap database pengembangan `.env` tanpa memisahkan koneksi tes.
10. Asumsi kerja untuk ADR yang masih Proposed tercantum di bagian 4. Jika product owner memutuskan lain, catat di `docs/decisions/` lalu sesuaikan kode.

---

## 3. Asumsi kerja (sementara, dapat dibalik)

Gunakan asumsi ini agar pengerjaan tidak berhenti menunggu seluruh ADR:

| ADR | Asumsi kerja Release 1 |
|---|---|
| ADR-0001 | Satu assignment = satu klinik (`clinic_id` wajib saat aktivasi) |
| ADR-0002 | Satu jawaban berlaku per `(execution, criterion)`; riwayat perubahan disimpan terpisah (tabel atau JSON riwayat), bukan menimpa tanpa jejak |
| ADR-0003 | Alur: draft → diajukan → disetujui → ditugaskan → berjalan → tinjauan → final → CAPA → verifikasi → tutup |
| ADR-0004 | Tambah permission granular; role baru `reviewer` dan `verifier` di-seed; mapping jabatan resmi PPKP ditunda |
| ADR-0005 | Sumber regulasi tetap `UNVERIFIED` sampai ditinjau dari JDIH/Kemenkes |
| ADR-0006 | Tidak ada skor numerik atau persentase kepatuhan di UI |
| ADR-0007 | Retensi ditunda; bukti tidak dihapus lewat UI biasa |
| ADR-0008 | Notifikasi in-app/daftar kerja dulu; email hanya jika `MAIL` sudah dikonfigurasi |
| ADR-0009 | Ekspor PDF/Excel sederhana tanpa tanda tangan elektronik |
| ADR-0010 | Autentikasi lokal tetap |
| ADR-0011 | 16 klinik seeder adalah data demonstrasi, diberi label jelas bukan master resmi |

---

## 4. Urutan pengerjaan (wajib berurutan)

Kerjakan **Wave 0 → Wave 6**. Jangan meloncat ke laporan atau percantik UI sebelum Wave 0–4 selesai.

Struktur sidebar dan urutan perbaikan halaman audit: `docs/STRUKTUR_MENU_AUDIT_INTERNAL.md`.

### Wave 0 — Pulihkan lingkungan PostgreSQL (blocker)

**Tujuan:** migrasi Docker berhasil; seed tidak pecah; izin audit terpasang.

**Perintah:**

1. Perbaiki `database/migrations/2026_10_19_100004_create_standard_sections_table.php` (B-01):
   - Kolom induk: `$table->uuid('parent_id')->nullable()` (bukan `foreignUuid`).
   - FK self-reference setelah `Schema::create` selesai, atau migrasi baru jika tabel sudah setengah terbuat di Docker.
2. Perbaiki `checklist_responses.response_value` menjadi `->nullable()` (B-12). Jika migrasi itu belum pernah sukses di Docker, edit berkas yang sama. Jika sudah terpasang di lingkungan lain, buat migrasi baru.
3. Jika Docker sudah gagal di tengah:
   - **Jangan** `migrate:fresh`.
   - Periksa tabel yang sudah ada.
   - Tambah migrasi perbaikan atau `migrate:rollback` hanya untuk batch gagal jika aman.
   - Ulangi `docker compose exec app php artisan migrate`.
4. Ubah `DatabaseSeeder` agar organisasi/klinik `firstOrCreate` berdasarkan `code` (B-02).
5. Panggil `ClinicPermissionSeeder` dan `AuditPermissionSeeder` dari `DatabaseSeeder` (B-03).
6. Tambah permission `audit.dashboard.view` dan `audit.report.export` (B-04). Petakan ke role yang relevan; **jangan** beri `super-admin` wewenang bisnis `audit.finding.close` / `audit.program.approve` secara otomatis kecuali permission teknis terpisah.
7. Setelah migrate+seed berhasil, jalankan `php artisan permission:cache-reset`.

**Selesai jika:**

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan route:list --name=audit
```

Migrate bersih, seed tidak error unik `PPKP-DKI`, rute `audit.*` terdaftar.

---

### Wave 1 — Perbaikan blocker kode dan isolasi akses

**Tujuan:** halaman audit tidak pecah; IDOR antar klinik dan antar penugasan ditolak.

**Perintah:**

1. Perbaiki `$orgId` tak terdefinisi di `AuditFindingController::update` (B-06).
2. Ganti menu **Pelaksanaan Audit** ke indeks eksekusi/penugasan yang sah, bukan `audit.executions.checklist` tanpa ID (B-05). Tambah rute `audit.executions.index` jika belum ada.
3. Buat view `resources/views/audit/evidence/show.blade.php` (B-10).
4. Perbaiki interpolasi judul halaman program (B-16).
5. Perbaiki `ClinicScope` agar model `Clinic` memfilter `id`, model lain memfilter `clinic_id` (B-11).
6. Samakan aturan akses:
   - `hasClinicAccess` + keanggotaan tim untuk eksekusi, checklist, bukti, temuan dari audit.
   - `AuditExecutionPolicy::view` menolak non-anggota tim kecuali role pengawas yang punya permission tinjauan (`audit.execution.view` + assignment/program scope), bukan semua user klinik.
7. Unduhan bukti wajib `$this->authorize('download', $evidence)` dan policy harus cek organisasi, klinik, dan penugasan (B-09).
8. Panggil `$this->authorize()` pada start/submit/finalize execution, simpan jawaban, unggah bukti, verifikasi CAPA — jangan hanya `abort_unless` organisasi.
9. Sembunyikan item menu Mutu dan Akreditasi dari `SimmakMenu` untuk Release 1 (B-17). Rute boleh tetap ada di belakang permission, tetapi tidak ditawarkan sebagai modul hidup.

**Selesai jika:** auditor tanpa penugasan mendapat 403 pada checklist orang lain; kepala klinik tidak melihat temuan klinik lain; unduhan bukti lintas klinik ditolak.

---

### Wave 2 — Vertical slice perencanaan sampai temuan

**Tujuan:** satu audit dapat direncanakan dan dilaksanakan sampai temuan tercatat.

**Perintah — Program dan jadwal:**

1. Lengkapi field program: tujuan, ruang lingkup, kriteria, metode, periode, jenis (`periodic`, `thematic`, `special`, `follow_up`).
2. Wajibkan klinik sasaran dari master `clinics` (bukan daftar hard-code).
3. Filter indeks: klinik, periode, jenis, status, penanggung jawab.
4. Tampilkan konflik jadwal (peringatan, bukan hard-block kecuali overlap ketua tim pada tanggal yang sama — catat sebagai peringatan).
5. Tulis `approval_events` + `AuditTrailService::log` pada submit, approve, return (alasan wajib).
6. Halaman detail: ringkasan, daftar penugasan, status, linimasa. Kalender sederhana (daftar per tanggal) cukup untuk slice ini.

**Perintah — Penugasan:**

1. `clinic_id` wajib sebelum aktivasi.
2. Ketua tim dan anggota dengan peran.
3. Deklarasi independensi wajib sebelum aktivasi.
4. Konflik yang dideklarasikan menghalangi aktivasi sampai `conflict_check_status = resolved` oleh pengguna berizin `audit.assignment.manage` yang bukan anggota tim tersebut.
5. Auditor hanya mengakses objek sesuai assignment.

**Perintah — Instrumen:**

1. Lengkapi UI seksi dan kriteria per versi.
2. Kunci versi yang sudah dipakai eksekusi: isi kriteria/seksi tidak diubah; versi baru = baris baru.
3. Status sumber tetap `UNVERIFIED` sampai diverifikasi. Tombol “aktifkan untuk produksi resmi” tidak boleh menandai VERIFIED tanpa reviewer manusia.

**Perintah — Pelaksanaan dan checklist:**

1. Buat eksekusi dari assignment, wajib `instrument_version_id`.
2. Simpan jawaban, metode, sampel, catatan, alasan N/A dan tidak dapat dinilai.
3. Jangan menimpa tanpa jejak (B-13): simpan riwayat (tabel `checklist_response_revisions` atau append pada metadata terstruktur).
4. Validasi kelengkapan sebelum submit; finalize hanya dari status tinjauan oleh pihak berwenang, bukan pembuat jawaban jika SoD berlaku.
5. Jangan tampilkan skor atau persentase kepatuhan sebagai klaim mutu (indikator kelengkapan pengisian boleh).

**Perintah — Bukti:**

1. Metadata lengkap: audit, kriteria/temuan, jenis, nama asli, MIME terverifikasi, pengunggah, waktu, deskripsi, klasifikasi akses.
2. Private storage, nama acak, otorisasi setiap unduh.
3. Jangan buat URL publik.

**Perintah — Temuan:**

1. Nomor temuan unik per organisasi (generate di server, jangan andalkan input pengguna semata).
2. Field wajib master prompt: kondisi, kriteria, bukti objektif, kesenjangan, dampak, risiko, rekomendasi, PIC, tenggat.
3. Temuan `critical` harus bisa dieskalasikan (status/antrian prioritas) tanpa menunggu laporan final.
4. Review confirm/return dengan alasan.
5. Audit trail pada create/update/review.

**Selesai jika:** satu program demo dapat disetujui, ditugaskan, dijalankan, diisi checklist, dilampiri bukti, dan menghasilkan temuan — seluruhnya tercatat di trail.

---

### Wave 3 — CAPA, verifikasi, penutupan

**Tujuan:** temuan tidak dapat ditutup hanya karena dokumen diunggah atau tombol close diklik.

**Perintah:**

1. Satu temuan dapat punya beberapa tindakan CAPA (gunakan `capa_actions` yang ada).
2. Bedakan status rencana vs pelaksanaan (bukti pelaksanaan wajib sebelum ajukan verifikasi).
3. PIC tidak memverifikasi tindakannya sendiri (`CapaCasePolicy::verify` + panggilan `authorize` di controller).
4. Pembuat temuan tidak melakukan verifikasi akhir temuan miliknya.
5. Hasil verifikasi: diterima / perlu perbaikan / tidak efektif. Tidak efektif mengembalikan ke tindak lanjut dengan alasan dan tenggat baru.
6. `AuditFinding::close` hanya jika: status memungkinkan, verifikasi efektif tercatat (atau kebijakan accepted-risk yang terdokumentasi — jangan implementasi accepted-risk tanpa alasan dan pemegang wewenang).
7. Reopen wajib alasan, aktor, waktu, nilai sebelum/sesudah; hasil final tidak ditimpa diam-diam.
8. Administrator sistem **tidak** otomatis boleh close/ubah hasil final.

Bug terkait: B-07, B-14.

**Selesai jika:** upaya menutup temuan tanpa verifikasi ditolak; PIC mendapat 403 saat verifikasi sendiri; reopen meninggalkan jejak.

---

### Wave 4 — Dashboard dan laporan Audit Internal

**Tujuan:** angka dari database; laporan dapat diekspor sesuai scope.

**Perintah — Dashboard:**

1. Utamakan antrian kerja: terlambat, kritis, menunggu tinjauan, audit berikutnya.
2. Kartu metrik wajib taut ke indeks terfilter yang membentuk angka itu.
3. Scope sesuai pengguna (bukan seluruh organisasi kecuali pengawas berizin).
4. Definisi perhitungan ditulis di view (periode, status yang dihitung).
5. Jangan grafik dekoratif tanpa sumber.

**Perintah — Laporan:**

1. Filter: periode, klinik, jenis audit, auditor, area/standar, status temuan, risiko, status tindak lanjut.
2. Isi: rencana vs realisasi, ringkasan, temuan dan bukti, rekomendasi, CAPA, verifikasi, riwayat persetujuan.
3. Ekspor PDF dan Excel (CSV terstruktur diterima sebagai Excel tahap awal) dengan sanitasi formula (`=`, `+`, `-`, `@`) dan BOM UTF-8.
4. Ekspor memakai otorisasi dan scope yang sama dengan layar.
5. Identitas dokumen: judul SIMMAK Audit Internal Klinik, periode, versi, tanggal, status. Jangan menyebut sertifikat atau hasil akreditasi resmi.

Bug terkait: B-15.

**Selesai jika:** angka dashboard sama dengan hitungan query; file ekspor berisi baris yang sama dengan filter layar untuk pengguna uji.

---

### Wave 5 — Seeder demo, tes, dan isolasi tes

**Tujuan:** data demo aman; tes tidak merusak DB pengembangan; penerimaan terotomatisasi.

**Perintah — Seeder:**

1. Seeder terpisah, misalnya `AuditDemoSeeder`, dipanggil hanya dari lingkungan non-produksi.
2. Isi: 1 program, 2 assignment (klinik berbeda), 1 instrumen UNVERIFIED, checklist, 2 temuan, 1 CAPA, 1 verifikasi.
3. Label eksplisit “data demonstrasi”.
4. Akun tetap password demo yang sudah ada; dokumentasikan bahwa akun ini wajib dimatikan di produksi.

**Perintah — Tes:**

1. Konfigurasi PHPUnit memakai database tes terpisah (sqlite in-memory **atau** MySQL/PG `simmak_ppkp_test`). Jangan `RefreshDatabase` ke DB pengembangan.
2. Feature tests wajib:
   - buat/ubah program;
   - tetapkan tim + deklarasi konflik;
   - checklist pada versi instrumen tertentu;
   - simpan jawaban dan bukti;
   - buat temuan dan CAPA;
   - verifikasi ditolak untuk PIC sendiri;
   - close ditolak tanpa verifikasi;
   - IDOR klinik dan IDOR unduhan bukti;
   - auditor tanpa assignment 403;
   - ekspor menghormati scope;
   - transisi status ilegal ditolak.
3. Perbaiki tes otorisasi yang ada (role `staff`/`pj-mutu` harus di-seed; tes assignment harus memasang anggota tim).
4. Catat hasil di bagian 7 dokumen ini atau lampiran uji terpisah. Jangan centang penerimaan yang tidak dijalankan.

---

### Wave 6 — Keamanan, regresi, kesiapan rilis Audit Internal

**Tujuan:** Gate 6 hanya untuk modul audit, bukan seluruh SIMMAK.

**Perintah:**

1. Jalankan butir relevan `references/SECURITY_TEST_PLAN.md`. Area yang tidak diuji ditulis eksplisit.
2. Pastikan CSRF, validasi server, private download, rate limit login, tidak ada secret di git.
3. Audit trail append-oriented: tidak ada UI ubah/hapus log; aksi bisnis penting tercatat (B-14).
4. Uji migrasi pada salinan DB yang sudah ada (upgrade path) dan pada DB kosong.
5. Tinjau UI sesuai `references/UI_UX_SPEC.md`: formulir, empty/error, tabel, responsif, tanpa tombol mati.
6. Perbarui `docs/PEMERIKSAAN_MASTER_PROMPT_AUDIT_INTERNAL.md` atau buat catatan rilis: apa yang selesai, sisa, ADR terbuka.
7. Critical/High yang belum ditutup adalah **release blocker** kecuali ada penerimaan risiko tertulis dari pemilik berwenang.

---

## 5. Di luar perintah ini (jangan dikerjakan sekarang)

- Modul Manajemen Mutu penuh (indikator, tren, program peningkatan).
- Modul Manajemen Akreditasi penuh (self-assessment, mock survey, kesiapan akreditasi).
- Register risiko enterprise baru.
- Integrasi LDAP/AD.
- Tanda tangan elektronik BSrE.
- Klaim instrumen Kemenkes VERIFIED tanpa sumber JDIH.
- Redesign seluruh aplikasi di luar halaman audit yang disentuh.

Ekstensi data untuk tautan kelak ke mutu/akreditasi boleh berupa kolom nullable atau morph kosong — jangan UI modul tersebut.

---

## 6. Perintah teknis yang dipakai berulang

Lingkungan Docker (PostgreSQL):

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed --force
docker compose exec app gosu www-data php artisan permission:cache-reset
docker compose exec app php artisan route:list --name=audit
```

Lingkungan Laragon (MySQL) — hanya jika `.env` menunjuk MySQL:

```bash
php artisan migrate
php artisan db:seed
php artisan permission:cache-reset
```

Tes (setelah Wave 5 memisahkan database tes):

```bash
php artisan test --testsuite=Feature --filter=Audit
php artisan test --testsuite=Unit --filter=Audit
vendor/bin/pint --dirty
```

Jangan jalankan `migrate:fresh --seed` terhadap Docker yang berisi data yang ingin dipertahankan.

---

## 7. Kriteria penerimaan Release 1 (Audit Internal)

Centang hanya setelah dibuktikan dengan uji, bukan karena halaman terbuka.

### Fungsional

- [ ] Program dan jadwal memakai master klinik yang dapat dikonfigurasi
- [ ] Tim auditor dan deklarasi konflik tercatat; aktivasi tertahan jika konflik belum selesai
- [ ] Checklist terikat versi instrumen; versi terpakai tidak berubah retroaktif
- [ ] Jawaban, sampel, bukti, temuan, rekomendasi tersimpan
- [ ] Beberapa tindakan CAPA per temuan, PIC, tenggat, bukti pelaksanaan
- [ ] Verifikasi efektivitas, pengembalian, penutupan, reopen terkendali
- [ ] Dashboard dan laporan dari data aktual; filter menghormati scope
- [ ] Ekspor PDF/Excel sesuai data layar dan kolom yang diizinkan

### Otorisasi dan integritas

- [ ] Setiap aksi terlindungi dicek permission + scope objek di server
- [ ] Akses lintas klinik dan unduhan bukti lintas klinik ditolak
- [ ] Klien tidak dapat memalsukan role, klinik, status, atau finalisasi
- [ ] Self-approval dan self-verification ditolak
- [ ] Hasil final tidak ditimpa diam-diam
- [ ] Jejak audit mencatat aksi kritis tanpa secret atau data pasien berlebih

### Operasional

- [ ] Migrasi PostgreSQL dari DB bersih berhasil
- [ ] Migrasi aditif pada DB Docker yang sudah parsial berhasil tanpa `fresh`
- [ ] Seed demo tidak menimpa organisasi secara membabi buta dan tidak memasukkan data pasien
- [ ] Menu Mutu/Akreditasi tidak ditawarkan sebagai modul hidup
- [ ] Regulasi/instrumen belum diverifikasi tetap UNVERIFIED/draft

---

## 8. Berkas yang diperkirakan terdampak

Kerjakan secara aditif pada berkas yang sudah ada. Jangan merombak modul lain.

- `database/migrations/2026_10_19_*` dan migrasi perbaikan baru
- `database/seeders/DatabaseSeeder.php`, `AuditPermissionSeeder.php`, seeder demo baru
- `app/Http/Controllers/Audit*.php`, `CapaController.php`
- `app/Services/Audit*.php`
- `app/Policies/*`
- `app/Models/Concerns/ClinicScope.php`, `User.php`, model audit
- `app/Support/SimmakMenu.php`
- `routes/web.php`
- `resources/views/audit/**`
- `tests/Feature/Audit/**`, `tests/Unit/Audit/**`, `phpunit.xml`
- `docs/PEMERIKSAAN_MASTER_PROMPT_AUDIT_INTERNAL.md` (pembaruan status setelah tiap wave)

---

## 9. Cara memakai dokumen ini

Untuk **coding agent:** kerjakan satu wave per siklus. Laporkan berkas yang diubah, perintah yang dijalankan, hasil tes, dan sisa pekerjaan. Jangan mengklaim wave selesai jika uji wave itu belum dijalankan.

Untuk **manusia/peninjau:** tolak pull request yang menambah fitur mutu/akreditasi, mereset database, atau menandai regulasi VERIFIED tanpa bukti sumber resmi.

**Perintah berikutnya yang harus dimulai sekarang:** Wave 0 (pulihkan migrasi PostgreSQL dan seeder izin).
