# Perubahan UI — Pelaksanaan Audit, Temuan, dan CAPA

**Status:** Berlaku untuk pembahasan dan uji tim  
**Tanggal:** 23 Oktober 2026  
**Modul:** Audit Internal Klinik SIMMAK PPKP  
**Acuan pembahasan:** alur auditor hanya mengisi kesesuaian + rekomendasi pada instrumen; klinik menindaklanjuti di CAPA; temuan tidak tertutup sebelum auditor memvalidasi; penugasan berdasarkan pegawai, unit/klinik, dan tanggal.

Dokumen ini merinci **apa yang berubah di layar** dan **bagaimana tim memakai halaman**, agar desain, pelatihan, UAT, dan pembahasan proses memakai bahasa yang sama. Ini bukan sertifikat kepatuhan Kemenkes. Instrumen produksi tetap `UNVERIFIED` sampai sumber resmi dicek.

Menu proses tidak berubah urutannya:

**Standar & Instrumen → Program → Penugasan → Pelaksanaan → Temuan → CAPA → Laporan**

---

## 1. Kesepakatan proses (yang harus tercermin di UI)

```
Ketua Tim Audit menugaskan pegawai + klinik + tanggal
        ↓
Auditor membuka pelaksanaan yang ditugaskan
        ↓
Form pemeriksaan = instrumen (EP, elemen, bukti yang diharapkan)
Auditor hanya isi: Nilai kesesuaian + Rekomendasi
        ↓
EP tidak sesuai / sesuai sebagian → Temuan + CAPA otomatis
        ↓
Auditi klinik melihat temuan kliniknya, isi tindak lanjut, unggah bukti
        ↓
Temuan TETAP terbuka (menunggu verifikasi)
        ↓
Auditor yang ditugaskan memvalidasi kesesuaian tindak lanjut
        ↓
Efektif → temuan & CAPA tertutup
Tidak efektif → kembali ke tindak lanjut
```

Yang **tidak** boleh tampil sebagai janji UI:

- Skor atau persentase sebagai “nilai kepatuhan Kemenkes”.
- Tombol tutup temuan oleh auditi setelah unggah bukti.
- Auditor mengisi pemeriksaan klinik yang tidak ada di penugasannya.
- Auditi melihat temuan klinik lain.

---

## 2. Siapa melihat layar apa

| Peran | Penugasan | Pelaksanaan / form EP | Temuan | CAPA |
|---|---|---|---|---|
| Ketua Tim Audit / Ketua Tim Mutu | Buat & aktifkan | Pantau (lintas klinik organisasi) | Pantau | Dapat memvalidasi jika kebijakan/izin mengizinkan |
| Auditor | Hanya tugas di mana ia anggota tim | Hanya tugasnya; isi kesesuaian + rekomendasi | Temuan dari tugasnya | Validasi tindak lanjut tugasnya; **bukan** PIC klinik |
| Kepala Klinik / Unit | Lihat klinik sendiri | Lihat, tidak mengisi EP | Temuan kliniknya | Tindak lanjut klinik |
| Auditi | Tidak menugaskan | Tidak mengisi EP | Hanya kliniknya | Isi tindakan, unggah bukti |
| Admin sistem | Tidak menutup hasil final | Tidak | Tidak menutup temuan | Tidak memvalidasi efektivitas |

Jika menu tidak tampil, itu penyaringan peran. Aturan yang sama tetap ditegakkan di server.

---

## 3. Perubahan per halaman

### 3.1 Penugasan Auditor — `Buat Penugasan` / `Sunting`

**Tujuan layar:** menetapkan *siapa* mengaudit *klinik mana* pada *tanggal berapa*.

| Elemen UI | Isi / label | Catatan untuk tim |
|---|---|---|
| Kode tugas, judul, program | Tetap | Program harus sudah disetujui |
| Klinik / unit yang diaudit | Wajib; hanya unit `is_auditable` | Kantor Pusat (Manajemen) tidak muncul |
| Ketua tim (nama pegawai) | Daftar pegawai auditor: nama + unit/klinik pegawai | Bukan daftar “semua user” tanpa konteks pegawai |
| Tanggal mulai / selesai | Wajib | Jadwal penugasan |
| Anggota tim auditor | Kotak centang nama pegawai | Ketua otomatis anggota; jangan minta user menambah ketua lagi |
| Catatan ruang lingkup | Opsional | |

**Teks bantuan di form:** auditor hanya dapat memeriksa klinik pada penugasan ini.

**Yang dihapus dari kesan UI lama:** memilih ketua hanya sebagai nama akun tanpa unit; kesan bahwa siapa pun dengan izin audit bisa mengisi semua klinik.

Setelah dibuat: deklarasi konflik dan aktivasi tetap di halaman detail (bukan di form buat).

---

### 3.2 Pelaksanaan Audit — daftar

**Tujuan layar:** antrian kerja auditor, bukan katalog semua klinik.

| Elemen | Perubahan |
|---|---|
| Subjudul | Menjelaskan: hanya penugasan yang diikuti; isi kesesuaian dan rekomendasi; temuan ke CAPA klinik |
| Form “Buat eksekusi” | Hanya penugasan **aktif** yang user ikuti + versi instrumen |
| Daftar eksekusi | Auditor lapangan hanya baris tugasnya |

Jika tidak ada penugasan aktif: kartu peringatan mengarah ke **Kelola Penugasan**, bukan form kosong yang bisa diisi.

---

### 3.3 Form Pelaksanaan Audit (tabel instrumen)

**Ini layar utama lapangan.** Tata letak mengikuti instrumen, bukan formulir bebas.

#### Kolom yang tampil (mode isi)

| Kolom | Sumber | Boleh diubah auditor? |
|---|---|---|
| EP | Instrumen | Tidak |
| Elemen penilaian | Instrumen | Tidak |
| Kelengkapan bukti | Panduan bukti pada EP | Tidak (petunjuk, bukan unggah di kolom ini) |
| **Nilai kesesuaian** | Input auditor | Ya — Sesuai / Sesuai sebagian / Tidak sesuai / Tidak berlaku |
| **Rekomendasi** | Input auditor | Ya — wajib jika tidak sesuai atau sesuai sebagian |

#### Kolom yang **tidak lagi** menjadi isian utama

- SKOR dan SKOR Maksimal sebagai kolom kerja.
- Fakta dan analisis sebagai kolom wajib.
- Tombol **Catat Temuan** per baris.

Alasan: pembahasan menyepakati auditor tidak mengetik ulang temuan; sistem menurunkan temuan dari nilai kesesuaian.

#### Perilaku tombol **Simpan Tabel**

1. Menyimpan nilai kesesuaian dan rekomendasi.
2. Baris **Tidak sesuai** atau **Sesuai sebagian** + rekomendasi → membuat temuan (keparahan default: tidak sesuai = mayor, sesuai sebagian = ringan) dan kasus CAPA.
3. Baris **Sesuai** / **Tidak berlaku** → tidak membuat temuan.
4. Pesan sukses menyebut jumlah temuan yang dibuat, jika ada.

Progres pengisian: “X dari Y EP telah dinilai”. Teks di kartu: angka ini **kelengkapan pengisian**, bukan persentase kepatuhan Kemenkes.

Mode baca (bukan anggota tim, atau status sudah tinjauan): kolom yang sama, tanpa input.

Mulai audit: jika status masih persiapan, tampilkan aksi **Mulai Audit** sebelum tabel bisa diisi.

---

### 3.4 Temuan Audit

**Tujuan layar:** daftar temuan yang sudah “hidup” dari pemeriksaan, siap ditindaklanjuti klinik.

| Hal | UI yang diharapkan |
|---|---|
| Asal temuan | Dari EP yang tidak sesuai; judul mengikuti elemen penilaian |
| Rekomendasi | Sama dengan kolom rekomendasi di form pemeriksaan |
| Status awal | **Dalam CAPA** (bukan draf yang harus diketik ulang) |
| Filter | Klinik, status, keparahan, terbuka |
| Auditi | Hanya baris kliniknya |
| Auditor | Temuan dari penugasan yang diikutinya |

Jangan arahkan auditor untuk “buat temuan manual dulu” sebagai jalur utama. Tombol buat temuan manual, jika masih ada, adalah jalur cadangan.

Penutupan: tidak ada aksi tutup bagi auditi. Status **Selesai** hanya setelah validasi auditor (atau pejabat yang berwenang menutup setelah verifikasi efektif).

---

### 3.5 CAPA — detail kasus

**Tujuan layar kerja auditi:** tindak lanjut berdasarkan temuan, unggah bukti, menunggu auditor.

#### Blok kiri (identitas kasus)

Klinik, pernyataan masalah (dari temuan), tautan ke temuan terkait, PIC, target penutupan. Indikator terlambat **tidak** mengubah status menjadi selesai.

#### Blok tindakan

| Status tindakan | Yang tampil |
|---|---|
| Belum ada bukti | Form **Uraian tindak lanjut** + **Unggah bukti** (PDF/gambar/Office, maks. 10 MB) + **Kirim Bukti Tindak Lanjut** |
| Bukti terkirim | Uraian, nama berkas (unduh terotorisasi), teks “menunggu validasi auditor” |
| Kasus tertutup | Form unggah disembunyikan |

Auditi/kepala klinik kliniknya dapat menambah tindakan. PIC tindakan dipilih dari pengguna organisasi; praktisnya staf klinik itu.

#### Blok verifikasi (kanan bawah)

| Pengguna | Yang tampil |
|---|---|
| Auditor yang ditugaskan, setelah bukti ada | Form tanggal, hasil (efektif / perlu perbaikan / tidak efektif), catatan wajib, tenggat baru jika belum efektif, tombol **Catat Verifikasi** |
| Auditi / PIC | Teks: temuan belum tertutup; auditor yang ditugaskan yang memvalidasi |
| Setelah efektif | Riwayat verifikasi; kasus dan temuan **Selesai** |

Teks lama “pembuat temuan tidak dapat memverifikasi” **tidak dipakai**. Pembahasan: auditor yang memeriksa jugalah yang menilai kesesuaian tindak lanjut. PIC tetap tidak boleh memvalidasi pekerjaannya sendiri.

Hasil **efektif:** UI harus menunjukkan temuan ikut tertutup, bukan “silakan tutup temuan di menu lain”.  
Hasil **tidak efektif / perlu perbaikan:** kasus kembali ke tindakan; form unggah muncul lagi.

---

## 4. Salinan teks (copy) yang harus konsisten

Gunakan frasa ini di desain, pelatihan, dan slide:

| Jangan | Pakai |
|---|---|
| Isi skor EP | Isi nilai kesesuaian |
| Catat temuan | Simpan tabel (temuan dibuat otomatis jika tidak sesuai) |
| Tutup temuan setelah unggah | Kirim bukti tindak lanjut; menunggu validasi auditor |
| Persentase kepatuhan | Kelengkapan pengisian instrumen |
| Standar Kemenkes terverifikasi | Instrumen internal; status sumber terlihat di Standar & Instrumen |
| Klinik sasaran (umum) | Klinik / unit yang diaudit |
| Pilih user auditor | Ketua tim (nama pegawai) |

Nilai kesesuaian di layar:

- Sesuai  
- Sesuai sebagian  
- Tidak sesuai  
- Tidak berlaku  

---

## 5. Status yang terlihat pengguna

### Temuan

| Status | Artinya di UI |
|---|---|
| Dalam CAPA | Klinik sedang / harus menindaklanjuti |
| Menunggu verifikasi | Bukti sudah dikirim, auditor belum memvalidasi |
| Selesai | Auditor menerima tindak lanjut |
| Tinjauan / draf | Jalur cadangan; bukan jalur utama form pemeriksaan |

### CAPA

| Status | Artinya di UI |
|---|---|
| Tindakan | Klinik mengerjakan / mengunggah bukti |
| Verifikasi | Menunggu auditor |
| Selesai | Validasi efektif |
| Terlambat | Label tenggat; status tidak diubah otomatis |

---

## 6. Yang tidak diubah di rilis ini (agar pembahasan tidak melebar)

- Urutan menu sidebar (sudah dikelompokkan proses).
- Master standar/instrumen dan versi (redaksi EP tetap dikelola di Standar & Instrumen).
- Deklarasi konflik kepentingan di detail penugasan.
- Laporan PDF/Excel: identitas “SIMMAK Audit Internal Klinik”, bukan sertifikat akreditasi.
- Kantor Pusat (Manajemen) tetap bukan objek audit.
- Jabatan pegawai ≠ peran sistem.

---

## 7. UAT singkat untuk tim

Kerjakan dengan dua akun: **auditor ditugaskan ke Klinik A** dan **auditi Klinik A**. Jangan pakai super-admin sebagai pengganti auditi.

1. Buat penugasan: pilih pegawai, Klinik A, tanggal. Aktifkan setelah konflik selesai.  
2. Login auditor lain yang tidak di tim → tidak bisa mulai pemeriksaan Klinik A.  
3. Login auditor tim → form EP hanya kesesuaian + rekomendasi. Simpan baris tidak sesuai.  
4. Cek Temuan: muncul otomatis, status dalam CAPA.  
5. Login auditi Klinik A → lihat temuan, unggah bukti. Status menunggu verifikasi; temuan **belum** selesai.  
6. Login auditi Klinik B → temuan Klinik A tidak bisa dibuka.  
7. Login auditor tim → validasi efektif → temuan dan CAPA selesai.  
8. Ulangi dengan hasil tidak efektif → form bukti muncul lagi.

Setelah rilis ke lingkungan yang sudah ada, sinkronkan izin peran (bukan `migrate:fresh`):

```bash
php artisan db:seed --class=AuditPermissionSeeder
php artisan permission:cache-reset
```

---

## 8. Berkas kode terkait (untuk desainer/developer)

| Area | Berkas |
|---|---|
| Form penugasan | `resources/views/audit/assignments/create.blade.php`, `edit.blade.php` |
| Daftar pelaksanaan | `resources/views/audit/executions/index.blade.php` |
| Tabel instrumen | `resources/views/audit/executions/checklist.blade.php` |
| CAPA | `resources/views/capa/show.blade.php` |
| Scope layar | `app/Support/AuditWorkScope.php` |
| Label kesesuaian | `app/Support/UiLabel.php` (`conformity`) |

Panduan menu: `docs/STRUKTUR_MENU_AUDIT_INTERNAL.md`.  
Panduan pengguna: `docs/manual/06_AUDIT_INTERNAL.md`, `07_TEMUAN_AUDIT.md`, `08_CAPA.md`.
