# Petunjuk Teknis Penggunaan SIMMAK PPKP

| Atribut | Nilai |
|---------|-------|
| Nama sistem | SIMMAK (Sistem Informasi Manajemen Mutu Klinik) |
| Instansi | UPT Pusat Pelayanan Kesehatan Pegawai (PPKP) Provinsi DKI Jakarta |
| Versi dokumen | 2.1.0 |
| Tanggal | 29 Oktober 2026 |
| Sifat | Petunjuk teknis operasional pengguna |
| Lingkup rilis | Audit Internal Klinik (modul utama); master data; dashboard; laporan |

Dokumen ini menjelaskan **cara memakai** SIMMAK dari masuk sistem sampai penutupan temuan. Acuan teknis peran ada di `docs/ROLE_MATRIX.md`. Acuan model akses pegawai–pengguna ada di `docs/PERBAIKAN_MASTER_PEGAWAI_PENGGUNA_PENUGASAN.md`.

---

## 1. Maksud, tujuan, dan ruang lingkup

### 1.1 Maksud
Memberi tata cara yang sama bagi seluruh petugas PPKP dan klinik dalam menggunakan SIMMAK, agar data tidak ganda, wewenang tidak tertukar, dan jejak audit tetap utuh.

### 1.2 Tujuan
1. Menyeragamkan langkah kerja dari master data sampai laporan audit.
2. Memisahkan tiga pengaturan yang sering disangka sama: **peran jabatan akun**, **tugas di sistem**, dan **klinik yang dipegang pegawai**.
3. Menetapkan alur resmi audit internal: standar → program → penugasan → pelaksanaan → temuan → tindak lanjut → laporan.

### 1.3 Ruang lingkup
Berlaku untuk seluruh pengguna SIMMAK PPKP. Modul yang menjadi rantai kerja harian Release 1 adalah **Audit Internal** beserta master data dan pengaturan akun.

Menu samping mengikuti izin akun. Yang tidak muncul di menu **bukan** berarti halaman tidak ada, melainkan akun tidak berwenang.

Modul Mutu, Risiko, Akreditasi, dan DMS dapat diakses jika izin ada, tetapi **bukan** rantai kerja utama Release 1 dan tidak selalu tampil di sidebar.

### 1.4 Istilah yang dipakai di layar

| Istilah di layar | Arti |
|------------------|------|
| Peran jabatan | Jabatan organisasi di SIMMAK (menu dan persetujuan). Bukan jabatan master pegawai. Bukan peran di tim audit. |
| Tugas di sistem | Modul tambahan pada akun (audit internal, mutu, akreditasi, monitoring, auditor, auditi). Di kode disebut Scope Sistem. |
| Klinik / unit kerja yang dipegang | Daftar klinik pegawai di **Master Pegawai**. Ini sumber kebenaran batas klinik. |
| Batas data klinik | Klinik yang datanya boleh dilihat akun. Jika akun terhubung ke pegawai, ini hanya cermin master pegawai. |
| Klinik yang tampil pertama kali | Klinik konteks layar saat masuk. Bukan daftar klinik yang dipegang. |
| Peran dalam tim audit ini | Ketua tim auditor / anggota auditor / pengamat pada **satu penugasan**. |
| Elemen penilaian (EP) | Butir instrumen yang dinilai di form pelaksanaan. |
| Tindak Lanjut | Rencana dan bukti perbaikan atas temuan (di kode masih disebut CAPA; nomor kasus `TL-…`). |
| Klinik aktif | Klinik yang sedang dipilih di header; memfilter data sesuai batas akun. |

---

## 2. Persiapan dan masuk sistem

### 2.1 Alamat
- Pengembangan Docker: `http://localhost:8080`
- Pengembangan Artisan: `http://127.0.0.1:8000`
- Produksi: URL resmi yang ditetapkan PPKP

Halaman awal mengalihkan ke `/login`.

### 2.2 Masuk
1. Buka halaman login.
2. Isi **surel** (atau username, sesuai form yang tampil) dan **kata sandi**.
3. Pilih **Masuk**.
4. Sistem mencatat jejak masuk, lalu membuka halaman sesuai izin (dashboard eksekutif, dashboard klinik, program audit, pelaksanaan, atau dokumen).

Akun nonaktif tidak dapat masuk. Jika gagal, periksa surel, kata sandi, dan status akun pada pengelola.

### 2.3 Klinik aktif
Setelah masuk, perhatikan pemilih klinik di header.

1. Pilih klinik yang sedang dikerjakan, **hanya dari daftar yang diizinkan** untuk akun Anda.
2. Super Admin dapat melihat lintas klinik.
3. Ganti klinik jika akan mengerjakan unit lain yang memang dipegang pegawai Anda.

Jangan mengira ganti klinik menambah wewenang. Izin tetap mengikuti peran jabatan + tugas di sistem + batas klinik.

### 2.4 Profil dan keluar
- **Profil**: ubah data diri yang diizinkan form profil.
- **Keluar**: gunakan tombol keluar di header. Jangan hanya menutup jendela jika komputer dipakai bersama.

### 2.5 Akun uji (lingkungan nonproduksi)
Password akun uji **tidak ditulis di dokumen ini**. Di lingkungan uji, password diambil dari pengaturan server (`DEFAULT_USER_PASSWORD` / `SUPERADMIN_PASSWORD`) atau dicetak sekali saat seeding.

| Surel (pola) | Peran jabatan |
|--------------|---------------|
| surel super admin di pengaturan server | Super Admin |
| `sistem@simmak.ppkp.local` | Admin Sistem |
| `kepalapusat@simmak.ppkp.local` | Kepala Pusat |
| `tu@simmak.ppkp.local` | Kasubbag TU |
| `kepala@simmak.ppkp.local` | Kepala Klinik/Unit |
| `auditor@simmak.ppkp.local` | Auditor (peran lama; fungsi audit sebaiknya lewat tugas di sistem) |

Jangan memakai akun uji di produksi.

---

## 3. Model akses — jangan isi data dua kali

SIMMAK memisahkan **orang**, **akun**, dan **tugas di satu audit**. Mengisi salah satu tidak mengganti yang lain.

### 3.1 Urutan yang benar saat menambah petugas baru

1. **Master Jabatan** (jika jabatan belum ada).
2. **Master Pegawai**: NIP, nama, jabatan, **klinik/unit yang dipegang**, tandai **klinik utama**.
3. **Pengguna**: hubungkan ke pegawai itu, pilih **peran jabatan**, lalu **tugas di sistem** (opsional).
4. Saat ada audit: di **Detail Penugasan**, tambah anggota dan pilih **peran dalam tim audit ini**.

### 3.2 Apa yang tidak boleh dilakukan

| Kesalahan | Akibat | Yang benar |
|-----------|--------|------------|
| Mencentang ulang klinik di form pengguna padahal akun sudah terhubung ke pegawai | Data tampak ganda; daftar pengguna hanya cermin | Ubah di Master Pegawai |
| Memberi peran jabatan `auditor` (lama) padahal yang dimaksud anggota tim | Peran lama tidak untuk akun baru | Beri tugas di sistem **Auditor**, lalu masukkan orang ke tim penugasan |
| Mengisi “Peran” di detail penugasan dengan nama jabatan (Kepala Klinik, Kasatpel, …) | Peran tim hanya ketua / anggota / pengamat | Pilih dari daftar peran tim |
| Mengira tugas “Auditor” otomatis menempatkan orang ke semua audit | Tugas hanya membuka menu pelaksanaan | Masih harus ditambahkan ke tim penugasan |
| Menyetujui program atau memvalidasi tindak lanjut milik sendiri | Ditolak kebijakan segregasi tugas | Minta pejabat lain yang berwenang |

### 3.3 Delapan peran jabatan yang boleh dipilih

| Peran jabatan | Dipakai untuk |
|---------------|----------------|
| Super Admin | Akses penuh teknis; bukan pejabat penutup hasil audit atas nama pimpinan |
| Admin Sistem | Pengguna, master, pengaturan teknis |
| Kepala Pusat | Pantau lintas klinik; persetujuan akhir program/laporan sesuai izin |
| Kasubbag TU | Administrasi, kepegawaian, dokumen |
| Kasatpel UKP | Pengawasan mutu/audit lingkup UKP |
| Kasatpel UKM | Pengawasan mutu/audit lingkup UKM |
| Kepala Klinik/Unit | Pantau dan tindak lanjut di klinik yang dipegang |
| Admin Klinik/Unit | Operasional klinik: dokumen, bukti, tindak lanjut |

Fungsi **auditor lapangan** dan **auditi klinik** diberikan sebagai **tugas di sistem**, bukan sebagai peran jabatan baru.

### 3.4 Enam tugas di sistem

| Tugas | Dipakai untuk |
|-------|----------------|
| Audit Internal | Kelola penuh rantai audit |
| Mutu | Indikator, risiko, tindak lanjut mutu |
| Akreditasi | Standar dan bukti akreditasi |
| Monitoring | Baca lintas modul tanpa hak ubah |
| Auditor | Isi form pelaksanaan pada penugasan yang diikuti |
| Auditi | Tindak lanjut hasil audit di klinik |

Izin efektif = peran jabatan **ditambah** tugas yang dicentang. Tugas tidak menambah klinik dan tidak menempatkan orang ke tim audit.

---

## 4. Menu utama

Urutan kerja harian mengikuti kelompok menu:

**Ringkasan**
- Dashboard
- Dashboard Klinik

**Master Data**
- Master Unit/Klinik
- Master Pegawai
- Master Jabatan

**Audit Internal** (urut proses)
1. Standar & Instrumen
2. Sumber Regulasi
3. Program Audit
4. Penugasan Auditor
5. Kalender Audit
6. Pelaksanaan Audit
7. Temuan Audit
8. Tindak Lanjut
9. Laporan Audit
10. Arsip Bukti

**Pengaturan**
- Tahun Anggaran
- Pengguna

---

## 5. Master data

Tanpa master yang rapi, audit tidak bisa dijadwalkan ke klinik yang benar dan akun tidak mendapat batas klinik.

### 5.1 Master Unit/Klinik
**Menu:** Master Unit/Klinik (`/clinics`)  
**Izin:** `clinics.view` / `clinics.manage`

1. Tambah klinik: kode, nama, jenis (pratama / utama / manajemen), kota, status aktif, apakah dapat diaudit.
2. Klinik nonaktif tidak tampil untuk penugasan baru.
3. Jangan mengganti kode klinik yang sudah dipakai riwayat audit tanpa keputusan pimpinan.

### 5.2 Master Jabatan
**Menu:** Master Jabatan (`/positions`)  
**Izin:** `positions.view` / `positions.manage`

1. Tambah jabatan organisasi (nama, kategori).
2. Jabatan ini **kelengkapan data pegawai**, bukan izin masuk menu SIMMAK.

### 5.3 Master Pegawai
**Menu:** Master Pegawai (`/employees`)  
**Izin:** `employees.view` / `employees.manage`

1. Isi NIP, nama lengkap, jabatan, surel, telepon.
2. Centang **Klinik / unit kerja yang dipegang**. Boleh lebih dari satu.
3. Pilih tepat satu **Klinik utama** (tombol radio). Klinik utama menjadi dasar penugasan dan klinik yang tampil pertama kali pada akun.
4. Simpan. Jika pegawai sudah punya akun, batas klinik akun **mengikuti daftar ini otomatis**.
5. Pegawai nonaktif: akun terkait kehilangan batas klinik sampai diaktifkan kembali.

Jangan membuat pegawai ganda untuk orang yang sama. Satu NIP, satu data pegawai, satu atau beberapa akun hanya jika kebijakan PPKP mengizinkan.

### 5.4 Tahun anggaran
**Menu:** Tahun Anggaran (`/settings/fiscal-years`)  
**Izin:** `settings.manage`

1. Tambah tahun anggaran yang akan dipakai program audit.
2. Program audit **memakai** tahun anggaran; jangan membuat tahun di tengah form program jika master belum ada.

---

## 6. Pengguna

**Menu:** Pengguna (`/users`)  
**Izin:** `users.view` / `users.manage` (biasanya Super Admin, Admin Sistem, Kasubbag TU)

### 6.1 Membuat akun staf klinik
1. Pastikan data pegawai sudah ada dan kliniknya sudah dicentang.
2. Buka **Pengguna → Tambah**, atau dari daftar pegawai pilih **Buat Akun**.
3. **Pegawai (master):** pilih pegawai. Nama dan surel terisi. Batas klinik mengikuti pegawai dan tidak diisi ulang di sini.
4. Isi username dan kata sandi.
5. **Peran jabatan:** centang satu atau lebih dari delapan peran resmi.
6. **Tugas di sistem:** centang modul yang boleh dikerjakan (contoh: Auditor untuk petugas lapangan; Auditi untuk PIC klinik).
7. **Klinik yang tampil pertama kali:** biasanya klinik utama pegawai. Hanya konteks layar.
8. Pastikan **Akun aktif**, lalu simpan.

### 6.2 Akun teknis tanpa pegawai
Hanya untuk akun sistem (contoh Super Admin) yang tidak mewakili satu orang di master pegawai.

1. Biarkan pegawai kosong.
2. Pilih batas klinik secara manual.
3. Jangan memakai pola ini untuk staf klinik sehari-hari.

### 6.3 Ubah, nonaktif, reset kata sandi
- **Edit**: ubah peran jabatan dan tugas di sistem. Klinik pegawai tetap di master pegawai.
- **Nonaktifkan**: akun tidak dapat masuk; riwayat tetap ada.
- **Reset kata sandi**: dari halaman detail pengguna.
- **Hapus**: hanya jika kebijakan mengizinkan; lebih aman menonaktifkan.

---

## 7. Standar, instrumen, dan sumber regulasi

Rantai audit dimulai dari instrumen yang sah. Jangan mengisi form pelaksanaan tanpa versi instrumen aktif.

### 7.1 Sumber regulasi
**Menu:** Sumber Regulasi (`/audit/regulatory-sources`)

1. Daftarkan dasar hukum/acuan (nama, nomor, tahun).
2. Verifikasi sumber bila tombol verifikasi tersedia dan Anda berwenang.
3. Standar merujuk ke sumber ini agar jejak acuan tidak hilang.

### 7.2 Standar
**Menu:** Standar & Instrumen (`/audit/standards`)

1. **Tambah** standar: kode, judul, sumber regulasi, status (Draf / Aktif / Nonaktif).
2. Simpan. Buka detail standar.
3. Status dapat diubah dengan **Aktifkan / Nonaktifkan** sesuai wewenang.

### 7.3 Versi instrumen
1. Pada detail standar, buat **versi** (contoh v2026).
2. Isi seksi dan **elemen penilaian (EP)**: kode, judul, persyaratan, kelengkapan bukti, panduan penilaian.
3. Alternatif: **Impor EP** — unduh template, unggah, pratinjau, simpan hanya baris yang lolos.
4. Ajukan tinjauan → sahkan → **aktifkan** versi yang akan dipakai lapangan.
5. Perbaikan redaksi seksi/EP pada versi yang sudah dipakai pelaksanaan **boleh disunting pada versi yang sama**. Buat versi baru hanya jika substansi instrumen berubah dan pelaksanaan lama harus tetap memakai bunyi lama.

Jangan menghapus standar yang sudah punya riwayat pelaksanaan.

---

## 8. Program audit

**Menu:** Program Audit (`/audit/programs`)  
**Izin lihat:** `audit.program.view`  
**Ajukan:** `audit.program.submit`  
**Setujui / kembalikan:** `audit.program.approve`

### 8.1 Status
Draf → Diajukan tinjauan → Disetujui / Dikembalikan untuk revisi.

Hanya program **disetujui** yang dapat dipakai penugasan.

### 8.2 Membuat program
1. Pilih tahun anggaran, jenis audit (Periodik / Tematik / Khusus / Ulang), kode, nama, tujuan, ruang lingkup.
2. Simpan sebagai draf.
3. **Ajukan** jika isian lengkap.
4. Pejabat yang **bukan pembuat program** menyetujui atau mengembalikan dengan alasan.
5. Super Admin tidak menggantikan persetujuan pimpinan kecuali kebijakan internal mengizinkan akses tak terbatas untuk perbaikan sistem.

---

## 9. Penugasan auditor dan kalender

**Menu:** Penugasan Auditor (`/audit/assignments`)  
**Kalender:** Kalender Audit (`/audit/calendar`)

### 9.1 Status penugasan (ringkas)
Draf → Diajukan ke Kepala Pusat → **Menunggu Persetujuan Kepala Pusat** → **Disetujui/Dipublikasikan** (setelah deklarasi independensi/konflik selesai). Jika ditolak, jadwal tidak dipublikasikan; perbaiki lalu ajukan ulang.

Kalender hanya menampilkan jadwal yang sudah disetujui. Auditor dan auditi hanya melihat jadwal yang melibatkan dirinya.

### 9.2 Membuat penugasan
1. Pastikan program sudah **disetujui**.
2. Isi kode, judul, pilih program, pilih **klinik yang diaudit** (hanya klinik yang dapat diaudit), tanggal mulai–selesai, ketua tim, instrumen (opsional).
3. Ketua tim otomatis menjadi anggota dengan peran **Ketua tim auditor**.
4. Simpan. Buka **Detail**.

### 9.3 Tim audit
Di detail penugasan:

1. **Tambah anggota tim audit ini** — pilih akun, lalu **peran dalam tim audit ini**:
   - Ketua tim auditor
   - Anggota auditor
   - Pengamat
2. Peran ini **hanya untuk penugasan ini**.
3. Setiap anggota menyatakan independensi dan ada/tidaknya konflik kepentingan.
4. Jika ada konflik, jadwal tidak dapat disetujui sampai anggota diganti atau konflik diselesaikan.
5. Pengelola memilih **Ajukan ke Kepala Pusat**.
6. **Kepala Pusat** memberi persetujuan akhir (**Setujui & Publikasikan**) atau **Tolak** dengan alasan. Pengguna lain tidak dapat memublikasikan jadwal secara langsung.

Jangan mengetik bebas nama jabatan di kolom peran tim.

### 9.4 Kalender
Buka **Kalender Audit** untuk melihat penugasan **yang sudah disetujui** per bulan. Jadwal yang masih draf, menunggu persetujuan, atau ditolak tidak tampil di kalender auditor/auditi. Jika bulan tampil kosong, geser ke bulan yang memuat jadwal.

---

## 10. Pelaksanaan audit (form pemeriksaan)

**Menu:** Pelaksanaan Audit (`/audit/executions`)  
**Izin isi:** `audit.execution.execute`  
Auditor hanya mengerjakan penugasan yang diikutinya.

### 10.1 Memulai
1. Dari daftar pelaksanaan atau dari penugasan aktif, **buat eksekusi** (pilih penugasan dan versi instrumen jika belum tertaut).
2. **Mulai Audit**. Status: Persiapan → Berjalan.

### 10.2 Mengisi tabel EP
Untuk setiap EP:

1. Pilih **nilai kesesuaian**: Sesuai / Sesuai sebagian / Tidak sesuai / Tidak berlaku.
2. Isi **rekomendasi** jika tidak sesuai atau sesuai sebagian (wajib).
3. Catat sampling / catatan sesuai kolom yang tersedia.
4. Unggah bukti dari baris EP atau dari arsip bukti, sesuai form.

**Simpan otomatis** menjaga isian kesesuaian di tabel. **Simpan Tabel** adalah langkah yang mengunci kaitan ke temuan: EP tidak sesuai / sesuai sebagian menjadi **temuan** dan kasus **tindak lanjut** untuk klinik.

Angka progres adalah **kelengkapan pengisian**, bukan skor kepatuhan kementerian.

### 10.3 Menyerahkan dan finalisasi
1. Setelah EP terisi, **serahkan** untuk tinjauan (`Menunggu tinjauan`).
2. Finalisasi sesuai wewenang ketua/peninjau. Setelah final, jangan mengubah hasil diam-diam; temuan sudah mengalir ke modul Temuan dan Tindak Lanjut.

---

## 11. Temuan audit

**Menu:** Temuan Audit (`/findings`)

### 11.1 Asal temuan
Jalur utama: otomatis dari **Simpan Tabel** pada pelaksanaan. Pembuatan temuan manual hanya jika berwenang dan kasus tidak terduplikasi.

### 11.2 Melihat dan meninjau
1. Filter status, keparahan, klinik.
2. Buka detail: EP, rekomendasi, tautan kasus tindak lanjut, lampiran.
3. Peninjau dapat meninjau / mengembalikan sesuai izin `audit.finding.review`.
4. Eskalasi hanya jika izin `audit.finding.escalate` dan kebijakan mengharuskan.

### 11.3 Status yang sering tampil
Terbuka / Draf / Tinjauan / Dikembalikan / Dikonfirmasi / Perlu Tindakan / **Dalam Tindak Lanjut** / Menunggu Verifikasi / Selesai.

Temuan **tidak selesai** hanya karena klinik mengunggah bukti. Selesai setelah auditor yang ditugaskan memvalidasi tindak lanjut sebagai **efektif**.

Pihak yang membuat temuan **tidak** menutup atau memverifikasi temuannya sendiri.

---

## 12. Tindak lanjut (CAPA)

**Menu:** Tindak Lanjut (`/capa`)  
Nomor kasus: `TL-tahun-urut`.

### 12.1 Siapa mengerjakan apa

| Pihak | Pekerjaan |
|-------|-----------|
| Auditi / Kepala Klinik / Admin Klinik | Analisis, rencana tindakan, unggah bukti, kirim bukti |
| Auditor yang ditugaskan | Validasi kesesuaian tindak lanjut |
| PIC kasus | Tidak memvalidasi kasusnya sendiri |
| Admin sistem | Tidak memvalidasi hasil final atas nama auditor |

### 12.2 Langkah klinik
1. Buka kasus dari menu Tindak Lanjut atau tautan di temuan.
2. (Opsional) isi analisis akar masalah (5 Why / Fishbone).
3. Tambah tindakan, PIC, uraian, target.
4. Unggah bukti (PDF, gambar, atau berkas Office sesuai batas unduhan sistem, umumnya 10 MB).
5. Kirim bukti. Status menuju **Verifikasi**. Temuan masih **Menunggu Verifikasi**.

### 12.3 Langkah auditor
1. Buka kasus setelah bukti ada.
2. Isi hasil:
   - **Efektif** → kasus dan temuan **Selesai**
   - **Perlu perbaikan** atau **Tidak efektif** → kembali ke tindakan; klinik unggah bukti baru
3. Auditi tidak melihat tombol validasi.

Kasus melewati target tanpa selesai ditandai **Terlambat** di daftar/dashboard; status tidak berubah sendiri.

Ekspor daftar: `/capa/export`.

---

## 13. Arsip bukti dan laporan

### 13.1 Arsip bukti
**Menu:** Arsip Bukti (`/audit/evidence`)

1. Pencarian teks pada uraian bukti/temuan didukung.
2. Unggah utama tetap dari form pelaksanaan atau lampiran temuan/tindak lanjut.
3. Unduh hanya lewat tombol sah; berkas tersimpan privat.

Jangan mengunggah data pasien identitas penuh. Minimalkan dan samarkan.

### 13.2 Laporan audit
**Menu:** Laporan Audit (`/audit/reports`)

1. Buat laporan dari siklus/penugasan yang sudah berjalan.
2. Unduh PDF atau Excel sesuai wewenang.
3. Laporan ini **bukti audit internal PPKP**, bukan sertifikat akreditasi.

### 13.3 Pelaporan eksekutif
Jika izin `dashboard.executive` ada: `/reports` dan ekspor peringkat klinik dari dashboard.

---

## 14. Dashboard

### 14.1 Dashboard eksekutif
**URL:** `/dashboard`  
**Izin:** `dashboard.executive` atau `audit.dashboard.view`

Isi yang dipakai pimpinan:

- Kartu temuan terbuka dan prioritas
- Progres tindak lanjut (selesai / proses / belum / terlambat)
- Grafik dari data nyata (bukan angka hiasan)
- Peringkat klinik dan ekspor CSV
- Pintasan ke antrian kerja audit

Data dapat tertahan cache singkat. Jika angka terasa usang, minta pengelola membersihkan cache.

### 14.2 Dashboard klinik
**URL:** `/clinic/dashboard`  
**Izin:** `dashboard.clinic`

Ringkasan **klinik aktif**: pelaksanaan, temuan, tindak lanjut, tugas tim. Hanya baca. Pengisian tetap di modul sumber.

---

## 15. Modul pendukung (bukan rantai utama Release 1)

Halaman berikut ada di sistem jika izin diberikan, tetapi tidak selalu ada di sidebar Audit Internal.

| Modul | URL | Catatan pakai |
|-------|-----|----------------|
| Dokumen (DMS) | `/dms` | Draf → tinjauan → terbit; QR publik `/verify/document/{token}` |
| Risiko | `/risk` | Identifikasi dan mitigasi |
| Program mutu | `/quality` | Pantauan indikator |
| Akreditasi | `/accreditation` | Kesenjangan, self-assessment, bukti — fase lanjutan |
| Pengaturan organisasi | `/settings` | Identitas instansi |

Jangan mencatat hasil audit internal hanya di modul akreditasi. Jejak resmi tetap: pelaksanaan → temuan → tindak lanjut → laporan.

---

## 16. Alur kerja ringkas (siapa mengerjakan)

1. **Pengelola master** mengisi klinik, jabatan, pegawai, tahun anggaran.
2. **Pengelola akun** membuat pengguna, peran jabatan, dan tugas di sistem.
3. **Pengelola instrumen** menyusun standar, EP, mengaktifkan versi.
4. **Perencana audit** membuat program, mengajukan; **pimpinan** menyetujui (bukan pembuat yang sama).
5. **Koordinator penugasan** membuat tim, menunggu deklarasi, mengaktifkan.
6. **Auditor lapangan** mengisi form EP, unggah bukti, simpan tabel, serahkan.
7. **Klinik (auditi)** mengisi tindak lanjut dan bukti.
8. **Auditor yang ditugaskan** memvalidasi efektif/tidak.
9. **Pimpinan** membaca dashboard dan laporan.

---

## 17. Ketentuan larangan

1. Membagikan kata sandi atau memakai akun orang lain.
2. Mengubah ID di alamat URL untuk menembus klinik lain — sistem menolak; percobaan tercatat.
3. Menyetujui, menutup, atau memverifikasi pekerjaan sendiri.
4. Menghapus bukti atau temuan untuk menghilangkan jejak.
5. Mengunggah berkas di luar keperluan audit atau berisi data pasien yang tidak perlu.
6. Mengisi klinik di dua tempat (pegawai dan pengguna) untuk orang yang sama.
7. Menganggap Super Admin sebagai pejabat penutup mutu.

Setiap operasi sensitif dicek di server. Menu yang disembunyikan **bukan** satu-satunya pengaman.

---

## 18. Gangguan yang sering terjadi dan apa yang dilakukan

| Gejala | Periksa dulu |
|--------|----------------|
| Menu tidak muncul | Peran jabatan dan tugas di sistem; bukan salah klinik |
| Data klinik orang lain tidak tampil | Master pegawai (klinik yang dipegang) dan klinik aktif di header |
| Tidak bisa menambah anggota tim | Izin `audit.assignment.manage`; akun calon anggota aktif dan punya izin pelaksanaan |
| Tidak bisa aktifkan penugasan | Deklarasi independensi/konflik belum selesai |
| Form pelaksanaan kosong / terkunci | Penugasan belum aktif; Anda bukan anggota tim; eksekusi belum dimulai |
| Temuan tidak muncul setelah isi EP | Gunakan **Simpan Tabel**, bukan hanya andalkan simpan otomatis |
| Tindak lanjut tidak bisa divalidasi | Bukan auditor yang ditugaskan, atau Anda PIC kasus itu sendiri |
| Tampilan lama setelah pembaruan | Bersihkan cache tampilan server; muat ulang berkas (Ctrl+F5) |
| Kalender kosong | Pindah bulan ke jadwal penugasan |

Laporkan gangguan yang berulang kepada Admin Sistem beserta nama akun, waktu, dan cuplikan layar tanpa data pasien.

---

## 19. Lampiran A — daftar halaman kerja

| Kegiatan | Alamat |
|----------|--------|
| Masuk | `/login` |
| Dashboard | `/dashboard` |
| Dashboard klinik | `/clinic/dashboard` |
| Master klinik | `/clinics` |
| Master pegawai | `/employees` |
| Master jabatan | `/positions` |
| Pengguna | `/users` |
| Tahun anggaran | `/settings/fiscal-years` |
| Sumber regulasi | `/audit/regulatory-sources` |
| Standar | `/audit/standards` |
| Program | `/audit/programs` |
| Penugasan | `/audit/assignments` |
| Kalender | `/audit/calendar` |
| Pelaksanaan | `/audit/executions` |
| Form EP | `/audit/executions/{id}/checklist` |
| Temuan | `/findings` |
| Tindak lanjut | `/capa` |
| Laporan audit | `/audit/reports` |
| Arsip bukti | `/audit/evidence` |

---

## 20. Lampiran B — dokumen terkait

| Dokumen | Isi |
|---------|-----|
| `docs/ROLE_MATRIX.md` | Matriks peran dan izin, termasuk pemisahan istilah akses |
| `docs/manual/01_ROLE_DAN_AKSES.md` | Rincian izin per peran |
| `docs/PERBAIKAN_MASTER_PEGAWAI_PENGGUNA_PENUGASAN.md` | Aturan pegawai, akun, dan sinkronisasi klinik |
| `docs/STRUKTUR_MENU_AUDIT_INTERNAL.md` | Urutan menu proses audit |
| `docs/UI_PELAKSANAAN_AUDIT_TEMUAN_CAPA.md` | Rincian layar pelaksanaan, temuan, tindak lanjut |

---

**Penutup.** Patuhi urutan master pegawai → akun → penugasan → pelaksanaan → temuan → tindak lanjut. Jika suatu isian sudah ada di master atau di tim audit, jangan diulang di form lain dengan nama yang mirip.
