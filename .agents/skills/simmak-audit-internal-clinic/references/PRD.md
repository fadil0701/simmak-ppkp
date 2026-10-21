# PRD — Audit Internal Klinik SIMMAK

## Ringkasan
Modul pertama SIMMAK untuk mengelola program, pelaksanaan, temuan, CAPA, verifikasi, penutupan, bukti, laporan, dan jejak audit untuk PPKP DKI Jakarta. Fase awal hanya Audit Internal; Manajemen Mutu dan Akreditasi adalah fase lanjutan.

## Pengguna dan lingkup
- Organisasi memiliki 15 Klinik Pratama dan 1 Klinik Utama pada baseline awal; data klinik wajib berasal dari master data yang dapat dikonfigurasi, bukan hard-code.
- Pengguna: administrator sistem, pengelola audit, auditor/ketua tim, PIC klinik, verifikator, pejabat pemberi persetujuan, pembaca/manajemen.
- Stack pilihan: Laravel + PostgreSQL. Versi ditentukan setelah inspeksi repo dan kompatibilitas.

## Sasaran
1. Menyusun program dan jadwal audit internal.
2. Menetapkan auditor, unit/klinik, ruang lingkup, dan konflik kepentingan.
3. Menyimpan instrumen/checklist berversi, jawaban, sampel, dan bukti.
4. Mencatat temuan, klasifikasi, rekomendasi, dan akar penyebab.
5. Mengelola CAPA, PIC, tenggat, bukti pelaksanaan, verifikasi efektivitas, dan penutupan.
6. Menyediakan dashboard, notifikasi, ekspor dan audit trail dengan akses sesuai scope.

## In-scope Release 1
- Integrasi autentikasi, users, clinic master, RBAC/scope yang ada.
- Program/jadwal audit, assignment, deklarasi konflik kepentingan.
- Instrumen/checklist berversi dan metadata sumber/verifikasi regulasi.
- Pelaksanaan audit, respons, sampel, lampiran privat, temuan, rekomendasi.
- CAPA-lite per temuan, verifikasi, efektivitas, closure/reopen yang terkendali.
- Workflow review/approval, dashboard data nyata, PDF/Excel, audit trail, tes dan dokumentasi.

## Out of scope
- Modul Manajemen Mutu penuh, Manajemen Akreditasi penuh, risk register enterprise, operasional klinik umum, integrasi eksternal baru tanpa keputusan.
- Tidak membuat data pasien atau data produksi palsu untuk demo/seed.

## Kebutuhan nonfungsional
- Isolasi lintas-klinik dan otorisasi server-side pada seluruh akses objek.
- Privasi-by-design, private evidence storage, audit trail, secure exports, backup/restore yang dapat diuji.
- Responsif, aksesibel, task-oriented; tidak terlihat seperti template AI generik.
- Perubahan aditif, migrasi aman, backward compatibility, regresi teruji.
- Kinerja dan retensi ditetapkan setelah kebutuhan volume dan kebijakan pemilik diketahui.

## Kriteria keberhasilan
- Alur audit→temuan→CAPA→verifikasi→penutupan dapat dituntaskan end-to-end.
- Pengguna tidak dapat membaca/mengubah data klinik atau objek di luar kewenangannya.
- Hasil final tidak dapat diubah diam-diam; seluruh perubahan penting tercatat.
- Bukti privat dan ekspor mematuhi scope; security test plan dan regression tests memiliki bukti.
- Regulasi/instrumen belum diverifikasi diberi status UNVERIFIED/draft.
