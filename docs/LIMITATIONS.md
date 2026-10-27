# Keterbatasan dan Pekerjaan Belum Selesai — SIMMAK PPKP

## 1. Keterbatasan Saat Ini

### Database

- MySQL tidak tersedia di lingkungan pengembangan saat ini, sehingga pengujian berbasis database tidak dapat dijalankan.
- PostgreSQL 17 (produksi) belum diuji secara langsung.

### PDF Export

- Ekspor PDF menggunakan respons HTML sebagai fallback ketika `barryvdh/laravel-dompdf` tidak terpasang.
- Untuk produksi, disarankan menginstal paket PDF: `composer require barryvdh/laravel-dompdf`.

### Excel Export

- Ekspor Excel menggunakan format CSV (dapat dibuka oleh Microsoft Excel).
- Untuk format .xlsx asli, disarankan menginstal: `composer require maatwebsite/excel`.



### UI/UX

- Beberapa view (assignments, executions, evidence, capabilities, standards, checklist) masih menggunakan layout yang perlu ditinjau ulang konsistensinya dengan desain institusional baru.
- Responsivitas mobile perlu diuji pada perangkat aktual.



### Integrasi

- Modul Manajemen Mutu dan Manajemen Akreditasi belum dibangun (fase 2 dan 3).
- Integrasi LDAP Active Directory Pemprov DKI Jakarta belum diaktifkan (`LDAP_ENABLED=false`).
- Integrasi object storage (S3/Supabase) belum dikonfigurasi untuk produksi.



## 2. Pekerjaan Belum Selesai



### Prioritas Tinggi

- [ ] Menjalankan `php artisan migrate:fresh --seed` untuk memastikan semua migration berjalan tanpa error
- [ ] Menjalankan seluruh test suite setelah database tersedia
- [ ] Menginstal paket PDF dan Excel untuk ekspor produksi
- [ ] Memverifikasi referensi regulasi ke JDIH Kemenkes (lihat docs/REGULATION_VERIFICATION.md)
- [ ] Menguji responsivitas tampilan pada mobile



### Prioritas Menengah

- [ ] Menambahkan Form Request untuk validasi terpusat pada semua controller
- [ ] Menambahkan policy class untuk otorisasi berbasis model (mis. AuditFindingPolicy)
- [ ] Menambahkan notifikasi email untuk keterlambatan CAPA dan eskalasi
- [ ] Menambahkan fitur kalender audit (tampilan kalender)
- [ ] Menambahkan grafis visualisasi pada dashboard (chart library)



### Prioritas Rendah

- [ ] Menambahkan fitur autosave pada checklist
- [ ] Menambahkan fitur import/export standar dan instrumen
- [ ] Menambahkan API publik untuk integrasi sistem lain
- [ ] Menambahkan fitur pencarian full-text pada bukti dan temuan



## 3. Asumsi Desain

- Data klinik (16 klinik) adalah data demonstrasi, bukan data resmi PPKP DKI Jakarta.
- Nomor regulasi yang disebutkan dalam sistem belum diverifikasi dan ditandai sebagai `UNVERIFIED`.
- Akun demonstrasi memakai password dari environment `DEFAULT_USER_PASSWORD` (fallback `DEMO_ACCOUNT_PASSWORD`, tidak ada di codebase); di produksi akun demo dilewati kecuali password di-set eksplisit. Akun super admin dibuat terpisah lewat `php artisan simmak:create-superadmin`.
- File bukti disimpan pada storage lokal (private), bukan object storage.

