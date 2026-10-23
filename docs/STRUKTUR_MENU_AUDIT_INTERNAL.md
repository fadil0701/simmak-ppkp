# Struktur Menu Audit Internal — Analisis dan Rekomendasi

**Status:** Disetujui untuk implementasi  
**Tanggal:** 22 Oktober 2026  
**Acuan:** skill `simmak-audit-internal-clinic`, `references/UI_UX_SPEC.md`, `references/WORKFLOWS_AND_STATES.md`, `docs/PERINTAH_PENYELESAIAN_AUDIT_INTERNAL.md`

Dokumen ini merinci mengapa sidebar Audit Internal saat ini tidak terstruktur, apa isi setiap menu, dan urutan perbaikan yang disepakati. Implementasi kode menu mengikuti dokumen ini; jangan mengubah alur bisnis di luar yang tercantum.

## 1. Masalah arsitektur informasi

Sidebar Release 1 menampilkan seluruh item audit sebagai daftar datar, **bukan urutan proses**. Urutan aktual:

1. Audit Internal (halaman hub)  
2. Laporan Audit  
3. Standar & Instrumen  
4. Program Audit  
5. Penugasan Auditor  
6. Pelaksanaan Audit  
7. Bukti Audit  
8. Tahun Anggaran  
9. Temuan Audit  
10. CAPA  

Alur kerja yang harus diikuti pengguna:

**Standar → Program → Penugasan → Pelaksanaan (checklist + bukti) → Temuan → CAPA → Laporan**

Laporan tampil terlalu awal. Tahun anggaran terselip di tengah proses. Bukti dan jadwal lama didobelkan. Halaman pertama **Audit Internal** memakai model `AuditSchedule` yang bukan rantai operasional `penugasan → eksekusi`.

## 2. Struktur menu yang disepakati

Kelompokkan item, jangan biarkan 10 tautan setara.

### Kerja audit (urut proses)

| Urutan | Menu | Rute | Siapa yang memakai |
|---|---|---|---|
| 1 | Standar & Instrumen | `audit.standards.index` | Ketua Tim Mutu / Ketua Tim Audit / Admin instrumen |
| 2 | Program Audit | `audit.programs.index` | Ketua Tim Mutu / Ketua Tim Audit / Kepala Pusat (lihat) |
| 3 | Penugasan Auditor | `audit.assignments.index` | Ketua Tim Audit |
| 4 | Pelaksanaan Audit | `audit.executions.index` | Auditor / ketua tim |
| 5 | Temuan Audit | `findings.index` | Auditor, kepala klinik/unit, auditi (scope) |
| 6 | CAPA | `capa.index` | Auditi, kepala klinik/unit, verifikator |
| 7 | Laporan Audit | `audit.reports.index` | Ketua tim, kepala pusat, pembaca laporan |

### Konfigurasi (bukan langkah audit)

| Menu | Rute | Catatan |
|---|---|---|
| Tahun Anggaran | `fiscal-years.index` | Pindah ke area pengaturan. Program hanya **memakai** tahun anggaran. |

### Jangan jadi menu utama

| Item sekarang | Tindakan |
|---|---|
| Audit Internal (`audit.index`) | Jangan setara proses. Gabungkan ke Dashboard, atau ubah jadi ringkasan kerja berbasis penugasan/eksekusi — **bukan** `AuditSchedule`. |
| Bukti Audit (`audit.evidence.index`) | Arsip/pencarian. Unggah utama dari checklist pelaksanaan atau lampiran temuan. |

Dashboard eksekutif (plus blok audit) dan Dashboard Klinik tetap di atas, di luar kelompok proses.

## 3. Isi tiap menu dan rekomendasi

### 3.1 Audit Internal (hub) — perbaiki lebih dulu

**Isian sekarang:** kartu jumlah program/terjadwal/berjalan/selesai; daftar program; tabel jadwal dari `AuditSchedule`; tautan checklist lama; status bahasa Inggris (`Approved`).

**Rekomendasi:**
- Hapus dari sidebar sebagai item proses, atau ganti isinya menjadi antrian kerja (program menunggu persetujuan, penugasan belum aktif, eksekusi berjalan, temuan terbuka, CAPA terlambat).
- Jangan memakai `AuditSchedule` sebagai jalur utama.
- Status memakai label Indonesia (`UiLabel`).

### 3.2 Standar & Instrumen

**Isian:** kode, judul, sumber regulasi, versi, status, impor elemen penilaian.

**Rekomendasi:** versi yang sudah dipakai pelaksanaan tidak diganti diam-diam; draf/`UNVERIFIED` terlihat; tombol kelola hanya untuk `audit.standard.manage`.

### 3.3 Program Audit

**Isian:** kode, nama, jenis audit, tahun anggaran, tujuan, ruang lingkup, status (draf → diajukan → disetujui / dikembalikan).

**Rekomendasi:** program disetujui dulu sebelum menjadi penugasan; pemohon tidak menyetujui pengajuan sendiri.

### 3.4 Penugasan Auditor

**Isian:** kode tugas, program, klinik/unit yang diaudit (`is_auditable` saja), ketua tim (nama pegawai + unit), anggota tim, tanggal mulai/selesai, deklarasi konflik kepentingan.

**Rekomendasi:** ketua tim otomatis anggota tim; aktivasi tidak memaksa “ada konflik”; satu penugasan = satu klinik/unit. Auditor hanya mengerjakan penugasan tempat ia anggota tim. Rincian UI: `docs/UI_PELAKSANAAN_AUDIT_TEMUAN_CAPA.md`.

### 3.5 Pelaksanaan Audit

**Isian:** pilih penugasan aktif yang diikuti dan versi instrumen; tabel EP dari instrumen; auditor hanya mengisi nilai kesesuaian dan rekomendasi.

**Rekomendasi:** EP tidak sesuai / sesuai sebagian otomatis menjadi temuan + CAPA. Jangan menampilkan skor sebagai kepatuhan Kemenkes. Rincian UI: `docs/UI_PELAKSANAAN_AUDIT_TEMUAN_CAPA.md`.

### 3.6 Bukti Audit (arsip)

**Isian:** daftar berkas lintas pelaksanaan.

**Rekomendasi:** menu arsip opsional, bukan langkah ke-7. Penyimpanan tetap privat; unduh terotorisasi.

### 3.7 Temuan Audit

**Isian:** kode, judul (dari EP), kriteria, keparahan, klinik, status, rekomendasi.

**Rekomendasi:** jalur utama dari form pemeriksaan (status dalam CAPA). Auditi hanya melihat kliniknya. Penutupan setelah auditor memvalidasi tindak lanjut, bukan setelah unggah bukti.

### 3.8 CAPA

**Isian:** nomor kasus, temuan terkait, PIC klinik, tenggat, status, uraian + unggah bukti, validasi auditor.

**Rekomendasi:** auditi mengirim bukti; auditor yang ditugaskan memvalidasi kesesuaian. PIC tidak memvalidasi sendiri. Tenggat terlambat adalah indikator, bukan status yang menimpa.

### 3.9 Laporan Audit

**Isian:** laporan per pelaksanaan/program; unduh PDF/Excel.

**Rekomendasi:** tampilkan setelah siklus; tampilkan cakupan, cap waktu, dan wewenang unduh.

### 3.10 Tahun Anggaran

**Isian:** kode, nama, periode, tahun berjalan.

**Rekomendasi:** bukan langkah audit. Hanya master yang dipilih saat membuat program.

## 4. Urutan implementasi

Kerjakan berurutan. Jangan mulai percantik laporan sebelum 1–4 jalan end-to-end.

1. **Restruktur sidebar** di `app/Support/SimmakMenu.php` sesuai kelompok dan urutan pasal 2.
2. **Rapikan atau alihkan** `audit.index` agar tidak dobel Dashboard dan tidak bergantung pada `AuditSchedule`.
3. Vertikal **Standar → Program → Penugasan → Pelaksanaan** (termasuk bukti di checklist).
4. Vertikal **Temuan → CAPA → verifikasi → Laporan**.
5. Pindahkan **Tahun Anggaran** ke konfigurasi/pengaturan.

Setiap slice: otorisasi server-side, validasi, jejak audit jika aksi penting, dan regresi isolasi klinik.

## 5. Yang tidak diubah oleh dokumen ini

- Master unit/klinik: 1 Kantor Pusat (Manajemen) tidak diaudit; klinik utama/pratama adalah sasaran yang dapat bertambah/berkurang.
- Master jabatan tidak menentukan role.
- Katalog role: Super Admin, Admin Sistem, Kepala Pusat, Kasubbag TU, Ketua Tim Mutu, Ketua Tim Audit, Ketua Tim Akreditasi, Auditor, Kepala Klinik/Unit, Auditi. Lihat `docs/decisions/ADR-0013-unit-klinik-dan-peran.md`.
