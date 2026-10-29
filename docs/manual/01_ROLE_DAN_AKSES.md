# Role, Permission & Matriks Akses

## 1. Tujuan
Dokumen ini menjelaskan peran (role) pengguna SIMMAK PPKP, permission yang dimiliki, menu yang dapat diakses, serta halaman landing setelah login.

## 2. Daftar Role

Katalog resmi berisi 8 peran yang boleh dipilih pada form pengguna
(`SimmakRoles::catalog()`):

| Kode Role | Label UI | Fungsi utama |
|-----------|----------|--------------|
| `super-admin` | Super Admin (Akses Penuh Sistem) | Akses penuh seluruh modul termasuk pengguna; lintas klinik |
| `admin-sistem` | Admin Sistem | Administrasi teknis: pengguna, pengaturan, master klinik/pegawai/jabatan |
| `kepala-pusat` | Kepala Pusat | Monitoring lintas klinik (audit, mutu, akreditasi) |
| `kasubbag-tu` | Kasubbag TU | Administrasi umum, kepegawaian, pengguna, DMS |
| `kasatpel-ukp` | Kasatpel UKP | Pengawas lingkup UKP: menyiapkan & mengawal audit lingkupnya |
| `kasatpel-ukm` | Kasatpel UKM | Pengawas lingkup UKM (izin sama dengan Kasatpel UKP) |
| `kepala-klinik` | Kepala Klinik/Unit | Pantau mutu & CAPA, tindak lanjut temuan di kliniknya |
| `admin-klinik` | Admin Klinik/Unit | Operasional klinik: DMS, bukti audit, CAPA |

**Peran legacy** (tetap ada di database untuk akun existing, **tidak** dapat dipilih
untuk akun baru — `SimmakRoles::legacyRoleLabels()`):

| Kode Role | Label UI | Catatan |
|-----------|----------|---------|
| `ketua-tim-mutu` | Ketua Tim Mutu (lama) | Alias lama `pj-mutu`; bundel izin pemimpin mutu |
| `ketua-tim-audit` | Ketua Tim Audit (lama) | Bundel izin pemimpin audit internal |
| `ketua-tim-akreditasi` | Ketua Tim Akreditasi (lama) | Alias lama `tim-akreditasi` |
| `auditor` | Auditor (lama) | Fungsi audit kini juga dapat diberikan lewat Scope Sistem `auditor` |
| `auditi` | Auditi (lama) | Alias lama `staff`; fungsi auditi lewat Scope Sistem `auditi` |

## 3. Permission Sistem

| Permission | Arti |
|------------|------|
| `dashboard.view` | Akses dasar setelah login |
| `dashboard.executive` | Dashboard eksekutif + pelaporan |
| `dashboard.clinic` | Dashboard klinik |
| `dms.view` / `dms.manage` | Lihat / kelola dokumen |
| `audit.view` / `audit.manage` | Lihat / kelola audit & temuan |
| `capa.view` / `capa.manage` | Lihat / kelola CAPA |
| `risk.view` / `risk.manage` | Lihat / kelola risiko |
| `accreditation.view` / `accreditation.manage` | Lihat / kelola akreditasi |
| `quality.view` / `quality.manage` | Lihat / kelola program mutu |
| `users.view` / `users.manage` | Lihat / kelola pengguna |
| `settings.view` / `settings.manage` | Lihat / kelola pengaturan |
| `workflow.manage` | Kelola workflow (admin) |

## 4. Matriks Akses Menu

Keterangan: **V** = view (lihat), **M** = manage (ubah/buat), **—** = tidak ada akses.

| Modul / Menu | super-admin | admin-sistem | kepala-pusat | kasubbag-tu | kasatpel-ukp/ukm | kepala-klinik | admin-klinik |
|--------------|:-----------:|:------------:|:------------:|:-----------:|:----------------:|:-------------:|:------------:|
| Dashboard Eksekutif | V | — | V | — | V | — | — |
| Dashboard Klinik | V | — | V | — | V | V | V |
| Audit Internal | M | — | V | — | M | V | V |
| Temuan | M | — | V | — | M | V | V |
| CAPA | M | — | V | — | M | M | M |
| Risiko | M | — | V | — | M | V | — |
| Program Mutu | M | — | V | — | M | V | — |
| Akreditasi | M | — | V | — | V | V | V |
| Dokumen (DMS) | M | M | V | M | M | V | M |
| Pelaporan | M | — | M | — | M | M | — |
| Pengguna | M | M | — | M | — | — | — |
| Pengaturan | M | M | — | V | — | — | — |

## 5. Halaman Utama Setelah Login

| Role | Redirect |
|------|----------|
| super-admin, kepala-pusat, kasatpel-ukp, kasatpel-ukm, ketua-tim-mutu (lama), ketua-tim-audit (lama), auditor (lama) | `/dashboard` |
| kepala-klinik, admin-klinik, ketua-tim-akreditasi (lama), auditi (lama) | `/clinic/dashboard` |
| admin-sistem, kasubbag-tu | `/dms` |

Urutan ini mengikuti `SimmakMenu::homeRouteFor()`: `dashboard.executive` →
`dashboard.clinic` → `audit.program.view` → `audit.execution.view` → `dms.view`.

## 6. Akun Demo

Password 9 akun demo diambil dari `DEFAULT_USER_PASSWORD` (fallback
`DEMO_ACCOUNT_PASSWORD`) — lihat `config/simmak.php`. Super admin **tidak** dibuat
seeder; jalankan `php artisan simmak:create-superadmin`.

| Email | Username | Role | Klinik default |
|-------|----------|------|----------------|
| `$SUPERADMIN_EMAIL` (default `admin@simmak.ppkp.local`) | `admin` | super-admin | KU |
| `sistem@simmak.ppkp.local` | `adminsistem` | admin-sistem | MP |
| kepala@simmak.ppkp.local | kepala | kepala-klinik | KP06 |
| `kepalapusat@simmak.ppkp.local` | `kepalapusat` | kepala-pusat | MP |
| `tu@simmak.ppkp.local` | `kasubbagtu` | kasubbag-tu | MP |
| `ukp@simmak.ppkp.local` | `kasatpelukp` | kasatpel-ukp | Sesuai penugasan |
| `ukm@simmak.ppkp.local` | `kasatpelukm` | kasatpel-ukm | Sesuai penugasan |
| akreditasi@simmak.ppkp.local | akreditasi | tim-akreditasi | KP06 |
| auditor@simmak.ppkp.local | auditor | auditor | KU |
| staff@simmak.ppkp.local | staff | staff | KP06 |

## 7. Prinsip Keamanan Akses
1. Menu sidebar difilter otomatis berdasarkan permission.
2. Route web dilindungi middleware `permission:*`.
3. Data scoped ke `organization_id` pengguna.
4. Konteks klinik aktif mempengaruhi filter data klinik.
5. Aksi kritis dicatat di audit trail (login/logout, dll.).

## 8. Model Akses Terkini (berlaku sejak 26 Oktober 2026)

Rincian lengkap ada di `docs/PERBAIKAN_MASTER_PEGAWAI_PENGGUNA_PENUGASAN.md`.

1. **Pegawai memegang beberapa klinik/unit kerja.** Master pegawai punya daftar klinik/unit
   dengan satu klinik utama (radio "Klinik utama"); subunit mengikuti klinik utama.
2. **Akses klinik akun mengikuti master pegawai.** Bila akun dihubungkan ke data pegawai,
   pilihan klinik manual pada form pengguna dinonaktifkan dan klinik diturunkan dari master
   pegawai (klinik default = klinik utama pegawai). Akun tanpa pegawai tetap boleh memilih
   klinik manual.
3. **Satu pengguna boleh memiliki beberapa peran jabatan** (checkbox di form pengguna) dari
   katalog 8 peran resmi: Super Admin, Admin Sistem, Kepala Pusat, Kasubbag TU, Kasatpel UKP,
   Kasatpel UKM, Kepala Klinik/Unit, Admin Klinik/Unit. Peran lama (`auditor`, `auditi`,
   `ketua-tim-*`, alias `admin-mutu`/`pj-mutu`/`tim-akreditasi`/`staff`) tetap tampil pada akun
   yang sudah memilikinya, tetapi tidak dapat dipilih untuk akun baru. Ini **bukan** peran
   anggota di detail penugasan audit (`team_role`).
4. **Tugas di sistem** (kode: Scope Sistem — Audit Internal, Mutu, Akreditasi, Monitoring,
   Auditor, Auditi) menambah kewenangan fungsional di atas peran jabatan. Izin efektif =
   peran ∪ tugas; tugas tidak menghapus izin peran, tidak melewati segregasi tugas (menyetujui
   program, menutup temuan, memvalidasi CAPA), dan tidak melewati batas klinik. Tugas **bukan**
   daftar klinik dan **bukan** keanggotaan tim audit.
5. **Indeks penugasan** menampilkan Petugas Auditor (ketua tim + NIP + jumlah anggota),
   Lokasi Audit (nama + kota), Tanggal Audit (rentang + penanda "Melewati jadwal"),
   Standar/Instrumen, serta aksi **Detail Tim** yang membuka susunan tim beserta status
   independensi/konflik. Daftar dipaginasi 15 baris per halaman.
