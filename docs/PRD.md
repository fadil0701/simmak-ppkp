# Product Requirement Document (PRD) SIMMAK PPKP

Dokumen ini mendefinisikan kebutuhan produk SIMMAK PPKP sebagai platform enterprise untuk manajemen mutu, dokumen, audit internal, CAPA, risiko, akreditasi, dan dashboard manajemen klinik.
Dokumen disusun sebagai acuan bersama bagi pemilik proses, analis bisnis, desainer, engineer, QA, implementator, dan manajemen.
Bahasa kebutuhan menggunakan istilah wajib, harus, dapat, dan tidak boleh untuk membedakan tingkat kewajiban implementasi.

Versi Dokumen: 1.0
Tanggal Penyusunan: 25 Juni 2026
Target Teknologi: Laravel 12, PostgreSQL 17, Redis, LDAP hybrid authentication
Ruang Lingkup: FKTP/FKTL, klinik, puskesmas, unit pelayanan, dan organisasi kesehatan yang memerlukan tata kelola mutu berbasis bukti.

## 1. Ringkasan Eksekutif

1.1. SIMMAK PPKP menyediakan satu sumber kebenaran untuk dokumen mutu, audit, CAPA, risiko, akreditasi, indikator mutu, dan dashboard eksekutif.
1.2. Sistem dirancang agar konfigurasi workflow, standar akreditasi, matrix risiko, role, dan parameter operasional dikelola dari UI tanpa perubahan kode.
1.3. Platform menggunakan Laravel 12 sebagai backend aplikasi, PostgreSQL 17 sebagai basis data utama, Redis untuk cache, queue, dan session, serta LDAP hybrid auth untuk integrasi direktori organisasi.
1.4. Setiap modul wajib memiliki audit trail, notifikasi, kontrol akses, workflow, dan integrasi evidence yang dapat ditelusuri.
1.5. PRD ini mengutamakan kebutuhan enterprise: governance, traceability, configurability, security, compliance, dan readiness dashboard lintas modul.

## 2. Prinsip Produk

2.1. Konfigurasi sebelum koding: workflow, standar, matrix, role, dan template harus dikelola sebagai data.
2.2. Traceability penuh: keputusan, lampiran, status, dan perubahan harus dapat ditelusuri dari sumber sampai laporan.
2.3. Single source of truth: dokumen, evidence, temuan, CAPA, risiko, dan indikator tidak diduplikasi tanpa referensi jelas.
2.4. Least privilege: pengguna hanya melihat dan melakukan tindakan sesuai tugas, role, unit, dan status workflow.
2.5. Audit-ready by design: keluaran sistem harus siap diperiksa tanpa rekonsiliasi manual besar.
2.6. Interoperabilitas: API harus konsisten, terdokumentasi, dan siap diintegrasikan dengan layanan internal lain.
2.7. Data dinamis: standar akreditasi FKTP/FKTL dan parameter regulasi tidak boleh di-hardcode.
2.8. Operasional klinik: UI harus membantu pekerjaan harian, bukan hanya menyimpan data administratif.

## 3. Arsitektur Target

3.1. Backend menggunakan Laravel 12 dengan service layer untuk domain penting, policy untuk authorization, queue job untuk proses asynchronous, dan event untuk notifikasi.
3.2. Database utama menggunakan PostgreSQL 17 dengan foreign key, index, constraint, JSONB terbatas untuk konfigurasi fleksibel, dan migration terkontrol.
3.3. Redis digunakan untuk cache, queue, session, rate limiting, dan lock proses yang membutuhkan eksklusivitas.
3.4. Autentikasi menggunakan hybrid local account dan LDAP; sumber identitas dicatat pada profil user.
3.5. API menggunakan REST JSON versi /api/v1, mendukung pagination, sorting, filtering, idempotency key untuk operasi kritikal, dan error envelope standar.
3.6. File disimpan melalui storage abstraction Laravel sehingga dapat menggunakan local storage, S3 compatible storage, atau object storage internal.
3.7. Audit trail disimpan append-only secara logis dan tidak boleh diubah melalui UI reguler.
3.8. Workflow engine mengeksekusi template workflow yang didefinisikan melalui UI oleh admin berwenang.

## 1. Modul Core Platform

Kode Modul: M01
Cakupan: Auth, LDAP, RBAC, Workflow Engine, Notification, Audit Trail
Aktor Utama: Super Admin, Admin Mutu, Kepala Klinik, Auditor Internal, PIC Unit, Surveyor, Staf Dokumen
Komponen: Autentikasi Hybrid, Integrasi LDAP, RBAC, Workflow Engine, Notifikasi, Audit Trail, Session Management, Konfigurasi Tenant
Entitas Data: User, Role, Permission, Workflow Template, Workflow Instance, Notification, Audit Log, LDAP Mapping

Kebutuhan khusus modul:
M01-SP-01. Workflow engine wajib dapat dikonfigurasi dari UI tanpa perubahan kode.
M01-SP-02. Hak akses harus dapat diwariskan dari role namun tetap mendukung pengecualian berbasis user.
M01-SP-03. Login lokal dan LDAP harus dapat berjalan berdampingan sesuai kebijakan organisasi.

### Objective

M01-OBJ-01. Menyediakan kemampuan autentikasi hybrid yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M01-OBJ-01a. Memastikan proses autentikasi hybrid memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M01-OBJ-02. Menyediakan kemampuan integrasi ldap yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M01-OBJ-02a. Memastikan proses integrasi ldap memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M01-OBJ-03. Menyediakan kemampuan rbac yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M01-OBJ-03a. Memastikan proses rbac memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M01-OBJ-04. Menyediakan kemampuan workflow engine yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M01-OBJ-04a. Memastikan proses workflow engine memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M01-OBJ-05. Menyediakan kemampuan notifikasi yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M01-OBJ-05a. Memastikan proses notifikasi memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M01-OBJ-06. Menyediakan kemampuan audit trail yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M01-OBJ-06a. Memastikan proses audit trail memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M01-OBJ-07. Menyediakan kemampuan session management yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M01-OBJ-07a. Memastikan proses session management memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M01-OBJ-08. Menyediakan kemampuan konfigurasi tenant yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M01-OBJ-08a. Memastikan proses konfigurasi tenant memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M01-OBJ-DATA-01. Mengelola entitas User sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M01-OBJ-DATA-02. Mengelola entitas Role sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M01-OBJ-DATA-03. Mengelola entitas Permission sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M01-OBJ-DATA-04. Mengelola entitas Workflow Template sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M01-OBJ-DATA-05. Mengelola entitas Workflow Instance sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M01-OBJ-DATA-06. Mengelola entitas Notification sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M01-OBJ-DATA-07. Mengelola entitas Audit Log sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M01-OBJ-DATA-08. Mengelola entitas LDAP Mapping sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.

### User Story

M01-US-001. Sebagai Super Admin, saya ingin membuat data baru dengan validasi lengkap pada Autentikasi Hybrid, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-001-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-001-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-001-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-002. Sebagai Super Admin, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Integrasi LDAP, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-002-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-002-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-002-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-003. Sebagai Super Admin, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada RBAC, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-003-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-003-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-003-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-004. Sebagai Super Admin, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Workflow Engine, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-004-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-004-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-004-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-005. Sebagai Super Admin, saya ingin melihat riwayat perubahan dan keputusan pada Notifikasi, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-005-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-005-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-005-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-006. Sebagai Super Admin, saya ingin mencari data berdasarkan filter operasional pada Audit Trail, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-006-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-006-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-006-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-007. Sebagai Super Admin, saya ingin mengekspor data untuk rapat manajemen pada Session Management, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-007-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-007-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-007-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-008. Sebagai Super Admin, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Konfigurasi Tenant, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-008-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-008-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-008-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-009. Sebagai Super Admin, saya ingin menghubungkan data modul ini dengan modul lain pada Autentikasi Hybrid, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-009-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-009-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-009-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-010. Sebagai Super Admin, saya ingin memantau status pekerjaan yang belum selesai pada Integrasi LDAP, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-010-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-010-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-010-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-011. Sebagai Super Admin, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada RBAC, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-011-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-011-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-011-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-012. Sebagai Super Admin, saya ingin membuat laporan periodik yang siap diaudit pada Workflow Engine, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-012-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-012-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-012-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-013. Sebagai Admin Mutu, saya ingin membuat data baru dengan validasi lengkap pada Notifikasi, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-013-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-013-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-013-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-014. Sebagai Admin Mutu, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Audit Trail, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-014-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-014-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-014-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-015. Sebagai Admin Mutu, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Session Management, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-015-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-015-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-015-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-016. Sebagai Admin Mutu, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Konfigurasi Tenant, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-016-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-016-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-016-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-017. Sebagai Admin Mutu, saya ingin melihat riwayat perubahan dan keputusan pada Autentikasi Hybrid, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-017-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-017-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-017-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-018. Sebagai Admin Mutu, saya ingin mencari data berdasarkan filter operasional pada Integrasi LDAP, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-018-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-018-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-018-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-019. Sebagai Admin Mutu, saya ingin mengekspor data untuk rapat manajemen pada RBAC, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-019-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-019-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-019-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-020. Sebagai Admin Mutu, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Workflow Engine, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-020-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-020-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-020-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-021. Sebagai Admin Mutu, saya ingin menghubungkan data modul ini dengan modul lain pada Notifikasi, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-021-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-021-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-021-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-022. Sebagai Admin Mutu, saya ingin memantau status pekerjaan yang belum selesai pada Audit Trail, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-022-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-022-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-022-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-023. Sebagai Admin Mutu, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Session Management, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-023-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-023-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-023-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-024. Sebagai Admin Mutu, saya ingin membuat laporan periodik yang siap diaudit pada Konfigurasi Tenant, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-024-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-024-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-024-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-025. Sebagai Kepala Klinik, saya ingin membuat data baru dengan validasi lengkap pada Autentikasi Hybrid, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-025-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-025-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-025-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-026. Sebagai Kepala Klinik, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Integrasi LDAP, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-026-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-026-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-026-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-027. Sebagai Kepala Klinik, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada RBAC, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-027-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-027-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-027-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-028. Sebagai Kepala Klinik, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Workflow Engine, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-028-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-028-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-028-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-029. Sebagai Kepala Klinik, saya ingin melihat riwayat perubahan dan keputusan pada Notifikasi, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-029-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-029-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-029-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-030. Sebagai Kepala Klinik, saya ingin mencari data berdasarkan filter operasional pada Audit Trail, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-030-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-030-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-030-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-031. Sebagai Kepala Klinik, saya ingin mengekspor data untuk rapat manajemen pada Session Management, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-031-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-031-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-031-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-032. Sebagai Kepala Klinik, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Konfigurasi Tenant, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-032-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-032-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-032-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-033. Sebagai Kepala Klinik, saya ingin menghubungkan data modul ini dengan modul lain pada Autentikasi Hybrid, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-033-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-033-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-033-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-034. Sebagai Kepala Klinik, saya ingin memantau status pekerjaan yang belum selesai pada Integrasi LDAP, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-034-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-034-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-034-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-035. Sebagai Kepala Klinik, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada RBAC, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-035-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-035-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-035-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-036. Sebagai Kepala Klinik, saya ingin membuat laporan periodik yang siap diaudit pada Workflow Engine, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-036-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-036-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-036-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-037. Sebagai Auditor Internal, saya ingin membuat data baru dengan validasi lengkap pada Notifikasi, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-037-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-037-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-037-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-038. Sebagai Auditor Internal, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Audit Trail, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-038-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-038-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-038-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-039. Sebagai Auditor Internal, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Session Management, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-039-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-039-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-039-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-040. Sebagai Auditor Internal, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Konfigurasi Tenant, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-040-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-040-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-040-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-041. Sebagai Auditor Internal, saya ingin melihat riwayat perubahan dan keputusan pada Autentikasi Hybrid, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-041-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-041-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-041-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M01-US-042. Sebagai Auditor Internal, saya ingin mencari data berdasarkan filter operasional pada Integrasi LDAP, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M01-US-042-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M01-US-042-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M01-US-042-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.

### Use Case

M01-UC-01. Membuat Dan Menyimpan Entri Baru untuk Autentikasi Hybrid
  Aktor Utama: Super Admin.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Autentikasi Hybrid dan menjalankan aksi membuat dan menyimpan entri baru.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi User tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Autentikasi Hybrid sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data User tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M01-UC-02. Mengirim Entri Ke Workflow Persetujuan untuk Integrasi LDAP
  Aktor Utama: Admin Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Integrasi LDAP dan menjalankan aksi mengirim entri ke workflow persetujuan.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Role tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Integrasi LDAP sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Role tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M01-UC-03. Melakukan Review Substansi untuk RBAC
  Aktor Utama: Kepala Klinik.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu RBAC dan menjalankan aksi melakukan review substansi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Permission tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja RBAC sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Permission tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M01-UC-04. Memberikan Approval Final untuk Workflow Engine
  Aktor Utama: Auditor Internal.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Workflow Engine dan menjalankan aksi memberikan approval final.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Workflow Template tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Workflow Engine sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Workflow Template tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M01-UC-05. Mengembalikan Entri Untuk Revisi untuk Notifikasi
  Aktor Utama: PIC Unit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Notifikasi dan menjalankan aksi mengembalikan entri untuk revisi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Workflow Instance tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Notifikasi sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Workflow Instance tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M01-UC-06. Mempublikasikan Hasil Yang Telah Sah untuk Audit Trail
  Aktor Utama: Surveyor.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Audit Trail dan menjalankan aksi mempublikasikan hasil yang telah sah.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Notification tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Audit Trail sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Notification tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M01-UC-07. Menautkan Evidence Atau Lampiran untuk Session Management
  Aktor Utama: Staf Dokumen.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Session Management dan menjalankan aksi menautkan evidence atau lampiran.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Audit Log tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Session Management sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Audit Log tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M01-UC-08. Melakukan Monitoring Tenggat untuk Konfigurasi Tenant
  Aktor Utama: Super Admin.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Konfigurasi Tenant dan menjalankan aksi melakukan monitoring tenggat.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi LDAP Mapping tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Konfigurasi Tenant sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data LDAP Mapping tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M01-UC-09. Menutup Pekerjaan Setelah Seluruh Syarat Terpenuhi untuk Autentikasi Hybrid
  Aktor Utama: Admin Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Autentikasi Hybrid dan menjalankan aksi menutup pekerjaan setelah seluruh syarat terpenuhi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi User tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Autentikasi Hybrid sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data User tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M01-UC-10. Mengekspor Laporan Resmi untuk Integrasi LDAP
  Aktor Utama: Kepala Klinik.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Integrasi LDAP dan menjalankan aksi mengekspor laporan resmi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Role tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Integrasi LDAP sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Role tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M01-UC-11. Mengubah Konfigurasi Referensi untuk RBAC
  Aktor Utama: Auditor Internal.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu RBAC dan menjalankan aksi mengubah konfigurasi referensi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Permission tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja RBAC sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Permission tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M01-UC-12. Menelusuri Audit Trail untuk Workflow Engine
  Aktor Utama: PIC Unit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Workflow Engine dan menjalankan aksi menelusuri audit trail.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Workflow Template tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Workflow Engine sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Workflow Template tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.

### Functional Requirement

FR-M01-001. Sistem harus menyediakan menu Autentikasi Hybrid dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M01-002. Sistem harus menghubungkan Autentikasi Hybrid dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M01-003. Sistem harus mencatat audit trail untuk setiap perubahan data Autentikasi Hybrid, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M01-004. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Autentikasi Hybrid bila relevan dengan proses bisnis.
FR-M01-005. Sistem harus menyediakan menu Integrasi LDAP dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M01-006. Sistem harus menghubungkan Integrasi LDAP dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M01-007. Sistem harus mencatat audit trail untuk setiap perubahan data Integrasi LDAP, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M01-008. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Integrasi LDAP bila relevan dengan proses bisnis.
FR-M01-009. Sistem harus menyediakan menu RBAC dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M01-010. Sistem harus menghubungkan RBAC dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M01-011. Sistem harus mencatat audit trail untuk setiap perubahan data RBAC, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M01-012. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk RBAC bila relevan dengan proses bisnis.
FR-M01-013. Sistem harus menyediakan menu Workflow Engine dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M01-014. Sistem harus menghubungkan Workflow Engine dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M01-015. Sistem harus mencatat audit trail untuk setiap perubahan data Workflow Engine, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M01-016. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Workflow Engine bila relevan dengan proses bisnis.
FR-M01-017. Sistem harus menyediakan menu Notifikasi dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M01-018. Sistem harus menghubungkan Notifikasi dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M01-019. Sistem harus mencatat audit trail untuk setiap perubahan data Notifikasi, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M01-020. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Notifikasi bila relevan dengan proses bisnis.
FR-M01-021. Sistem harus menyediakan menu Audit Trail dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M01-022. Sistem harus menghubungkan Audit Trail dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M01-023. Sistem harus mencatat audit trail untuk setiap perubahan data Audit Trail, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M01-024. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Audit Trail bila relevan dengan proses bisnis.
FR-M01-025. Sistem harus menyediakan menu Session Management dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M01-026. Sistem harus menghubungkan Session Management dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M01-027. Sistem harus mencatat audit trail untuk setiap perubahan data Session Management, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M01-028. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Session Management bila relevan dengan proses bisnis.
FR-M01-029. Sistem harus menyediakan menu Konfigurasi Tenant dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M01-030. Sistem harus menghubungkan Konfigurasi Tenant dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M01-031. Sistem harus mencatat audit trail untuk setiap perubahan data Konfigurasi Tenant, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M01-032. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Konfigurasi Tenant bila relevan dengan proses bisnis.
FR-M01-033. Sistem harus memvalidasi entitas User menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M01-034. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas User dengan pagination standar.
FR-M01-035. Sistem harus memvalidasi entitas Role menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M01-036. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Role dengan pagination standar.
FR-M01-037. Sistem harus memvalidasi entitas Permission menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M01-038. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Permission dengan pagination standar.
FR-M01-039. Sistem harus memvalidasi entitas Workflow Template menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M01-040. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Workflow Template dengan pagination standar.
FR-M01-041. Sistem harus memvalidasi entitas Workflow Instance menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M01-042. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Workflow Instance dengan pagination standar.
FR-M01-043. Sistem harus memvalidasi entitas Notification menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M01-044. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Notification dengan pagination standar.
FR-M01-045. Sistem harus memvalidasi entitas Audit Log menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M01-046. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Audit Log dengan pagination standar.
FR-M01-047. Sistem harus memvalidasi entitas LDAP Mapping menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M01-048. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas LDAP Mapping dengan pagination standar.
FR-M01-049. Sistem harus memenuhi ketentuan khusus: Workflow engine wajib dapat dikonfigurasi dari UI tanpa perubahan kode.
FR-M01-050. Sistem harus memenuhi ketentuan khusus: Hak akses harus dapat diwariskan dari role namun tetap mendukung pengecualian berbasis user.
FR-M01-051. Sistem harus memenuhi ketentuan khusus: Login lokal dan LDAP harus dapat berjalan berdampingan sesuai kebijakan organisasi.
FR-M01-052. Sistem harus menyediakan kontrol operasional tambahan untuk Workflow Engine, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M01-053. Sistem harus menyediakan kontrol operasional tambahan untuk Notifikasi, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M01-054. Sistem harus menyediakan kontrol operasional tambahan untuk Audit Trail, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M01-055. Sistem harus menyediakan kontrol operasional tambahan untuk Session Management, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M01-056. Sistem harus menyediakan kontrol operasional tambahan untuk Konfigurasi Tenant, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M01-057. Sistem harus menyediakan kontrol operasional tambahan untuk Autentikasi Hybrid, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M01-058. Sistem harus menyediakan kontrol operasional tambahan untuk Integrasi LDAP, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.

### Non Functional Requirement

NFR-M01-001. Keamanan: sistem harus memastikan akses berbasis role, permission, unit, dan status workflow diterapkan konsisten pada seluruh fitur modul Core Platform.
NFR-M01-002. Keamanan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M01-003. Kinerja: sistem harus memastikan halaman daftar utama merespons cepat untuk data operasional besar pada seluruh fitur modul Core Platform.
NFR-M01-004. Kinerja: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M01-005. Reliabilitas: sistem harus memastikan transaksi penting menggunakan database transaction dan mekanisme retry terukur pada seluruh fitur modul Core Platform.
NFR-M01-006. Reliabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M01-007. Auditabilitas: sistem harus memastikan setiap perubahan bernilai kepatuhan memiliki jejak waktu, aktor, nilai lama, dan nilai baru pada seluruh fitur modul Core Platform.
NFR-M01-008. Auditabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M01-009. Ketersediaan: sistem harus memastikan fitur kritikal tetap stabil saat Redis digunakan untuk queue, cache, dan session pada seluruh fitur modul Core Platform.
NFR-M01-010. Ketersediaan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M01-011. Skalabilitas: sistem harus memastikan desain tabel dan indeks mendukung pertumbuhan multi tahun pada seluruh fitur modul Core Platform.
NFR-M01-012. Skalabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M01-013. Usability: sistem harus memastikan alur kerja utama dapat diselesaikan oleh pengguna non teknis dengan instruksi jelas pada seluruh fitur modul Core Platform.
NFR-M01-014. Usability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M01-015. Interoperabilitas: sistem harus memastikan API menggunakan JSON, pagination, filter, dan kode error konsisten pada seluruh fitur modul Core Platform.
NFR-M01-016. Interoperabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M01-017. Privasi: sistem harus memastikan data sensitif hanya tampil sesuai kebutuhan tugas dan kewenangan pada seluruh fitur modul Core Platform.
NFR-M01-018. Privasi: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M01-019. Maintainability: sistem harus memastikan konfigurasi bisnis disimpan sebagai data, bukan konstanta tersembunyi di kode pada seluruh fitur modul Core Platform.
NFR-M01-020. Maintainability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M01-021. Database: query utama modul harus memiliki index yang sesuai untuk filter status, periode, unit, pemilik, dan workflow.
NFR-M01-022. Queue: proses notifikasi, ekspor, reminder, dan kalkulasi agregat harus berjalan asynchronous menggunakan Redis queue.
NFR-M01-023. Observability: error, retry, durasi proses, dan kegagalan integrasi harus tercatat dalam log terstruktur.

### Acceptance Criteria

AC-M01-001. Diberikan pengguna Super Admin memiliki permission yang benar, ketika pengguna menjalankan proses Autentikasi Hybrid, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-001a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-002. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Integrasi LDAP, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-002a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-003. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses RBAC, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-003a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-004. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Workflow Engine, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-004a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-004b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M01-005. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Notifikasi, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-005a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-006. Diberikan pengguna Surveyor memiliki permission yang benar, ketika pengguna menjalankan proses Audit Trail, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-006a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-007. Diberikan pengguna Staf Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Session Management, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-007a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-008. Diberikan pengguna Super Admin memiliki permission yang benar, ketika pengguna menjalankan proses Konfigurasi Tenant, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-008a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-008b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M01-009. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Autentikasi Hybrid, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-009a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-010. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Integrasi LDAP, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-010a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-011. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses RBAC, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-011a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-012. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Workflow Engine, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-012a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-012b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M01-013. Diberikan pengguna Surveyor memiliki permission yang benar, ketika pengguna menjalankan proses Notifikasi, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-013a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-014. Diberikan pengguna Staf Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Audit Trail, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-014a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-015. Diberikan pengguna Super Admin memiliki permission yang benar, ketika pengguna menjalankan proses Session Management, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-015a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-016. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Konfigurasi Tenant, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-016a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-016b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M01-017. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Autentikasi Hybrid, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-017a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-018. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Integrasi LDAP, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-018a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-019. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses RBAC, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-019a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-020. Diberikan pengguna Surveyor memiliki permission yang benar, ketika pengguna menjalankan proses Workflow Engine, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-020a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-020b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M01-021. Diberikan pengguna Staf Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Notifikasi, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-021a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-022. Diberikan pengguna Super Admin memiliki permission yang benar, ketika pengguna menjalankan proses Audit Trail, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-022a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-023. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Session Management, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-023a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-024. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Konfigurasi Tenant, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-024a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-024b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M01-025. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Autentikasi Hybrid, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-025a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-026. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Integrasi LDAP, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-026a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-027. Diberikan pengguna Surveyor memiliki permission yang benar, ketika pengguna menjalankan proses RBAC, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-027a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-028. Diberikan pengguna Staf Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Workflow Engine, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-028a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-028b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M01-029. Diberikan pengguna Super Admin memiliki permission yang benar, ketika pengguna menjalankan proses Notifikasi, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-029a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-030. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Audit Trail, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-030a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-031. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Session Management, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-031a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-032. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Konfigurasi Tenant, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M01-032a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M01-032b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.

### Business Rules

BR-M01-001. Data User pada Autentikasi Hybrid hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-002. Data Role pada Integrasi LDAP hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-003. Data Permission pada RBAC hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-003a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M01-004. Data Workflow Template pada Workflow Engine hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-005. Data Workflow Instance pada Notifikasi hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-005b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M01-006. Data Notification pada Audit Trail hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-006a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M01-007. Data Audit Log pada Session Management hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-008. Data LDAP Mapping pada Konfigurasi Tenant hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-009. Data User pada Autentikasi Hybrid hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-009a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M01-010. Data Role pada Integrasi LDAP hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-010b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M01-011. Data Permission pada RBAC hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-012. Data Workflow Template pada Workflow Engine hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-012a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M01-013. Data Workflow Instance pada Notifikasi hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-014. Data Notification pada Audit Trail hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-015. Data Audit Log pada Session Management hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-015a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M01-015b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M01-016. Data LDAP Mapping pada Konfigurasi Tenant hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-017. Data User pada Autentikasi Hybrid hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-018. Data Role pada Integrasi LDAP hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-018a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M01-019. Data Permission pada RBAC hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-020. Data Workflow Template pada Workflow Engine hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-020b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M01-021. Data Workflow Instance pada Notifikasi hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-021a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M01-022. Data Notification pada Audit Trail hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-023. Data Audit Log pada Session Management hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-024. Data LDAP Mapping pada Konfigurasi Tenant hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-024a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M01-025. Data User pada Autentikasi Hybrid hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-025b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M01-026. Data Role pada Integrasi LDAP hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-027. Data Permission pada RBAC hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-027a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M01-028. Data Workflow Template pada Workflow Engine hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-029. Data Workflow Instance pada Notifikasi hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-030. Data Notification pada Audit Trail hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-030a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M01-030b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M01-031. Data Audit Log pada Session Management hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-032. Data LDAP Mapping pada Konfigurasi Tenant hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-033. Data User pada Autentikasi Hybrid hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-033a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M01-034. Data Role pada Integrasi LDAP hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-035. Data Permission pada RBAC hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-035b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M01-036. Data Workflow Template pada Workflow Engine hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M01-036a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M01-KHS-01. Workflow engine wajib dapat dikonfigurasi dari UI tanpa perubahan kode.
BR-M01-KHS-02. Hak akses harus dapat diwariskan dari role namun tetap mendukung pengecualian berbasis user.
BR-M01-KHS-03. Login lokal dan LDAP harus dapat berjalan berdampingan sesuai kebijakan organisasi.

### UI Requirement

UI-M01-001. Modul harus menyediakan dashboard ringkasan untuk Autentikasi Hybrid dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M01-001a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M01-001b. Halaman dashboard ringkasan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M01-002. Modul harus menyediakan halaman daftar untuk Integrasi LDAP dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M01-002a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M01-002b. Halaman halaman daftar harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M01-003. Modul harus menyediakan form tambah dan ubah untuk RBAC dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M01-003a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M01-003b. Halaman form tambah dan ubah harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M01-004. Modul harus menyediakan panel detail untuk Workflow Engine dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M01-004a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M01-004b. Halaman panel detail harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M01-005. Modul harus menyediakan timeline workflow untuk Notifikasi dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M01-005a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M01-005b. Halaman timeline workflow harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M01-006. Modul harus menyediakan tab lampiran untuk Audit Trail dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M01-006a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M01-006b. Halaman tab lampiran harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M01-007. Modul harus menyediakan modal approval untuk Session Management dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M01-007a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M01-007b. Halaman modal approval harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M01-008. Modul harus menyediakan filter lanjutan untuk Konfigurasi Tenant dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M01-008a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M01-008b. Halaman filter lanjutan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M01-009. Modul harus menyediakan indikator status untuk Autentikasi Hybrid dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M01-009a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M01-009b. Halaman indikator status harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M01-010. Modul harus menyediakan halaman konfigurasi untuk Integrasi LDAP dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M01-010a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M01-010b. Halaman halaman konfigurasi harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M01-011. Modul harus menyediakan wizard import untuk RBAC dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M01-011a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M01-011b. Halaman wizard import harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M01-012. Modul harus menyediakan halaman laporan untuk Workflow Engine dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M01-012a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M01-012b. Halaman halaman laporan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M01-999. Semua form modul Core Platform harus menampilkan validasi inline, konfirmasi sebelum aksi irreversible, dan indikator penyimpanan saat proses berlangsung.

### API Requirement

API-M01-001. Endpoint /api/v1/auth/login harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M01-001a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M01-001b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M01-002. Endpoint /api/v1/auth/ldap/verify harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M01-002a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M01-002b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M01-003. Endpoint /api/v1/rbac/roles harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M01-003a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M01-003b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M01-004. Endpoint /api/v1/workflows/templates harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M01-004a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M01-004b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M01-005. Endpoint /api/v1/workflows/instances harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M01-005a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M01-005b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M01-006. Endpoint /api/v1/notifications harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M01-006a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M01-006b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M01-007. Endpoint /api/v1/audit-logs harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M01-007a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M01-007b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M01-OP-001. Operasi create harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M01-OP-002. Operasi read harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M01-OP-003. Operasi update harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M01-OP-004. Operasi submit harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M01-OP-005. Operasi approve harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M01-OP-006. Operasi reject harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M01-OP-007. Operasi publish harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M01-OP-008. Operasi archive harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M01-OP-009. Operasi search harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M01-OP-010. Operasi export harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M01-OP-011. Operasi history harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M01-OP-012. Operasi configure harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M01-SEC-001. API modul Core Platform harus menerapkan rate limiting, CSRF/session protection untuk web, dan pembatasan akses data lintas unit.

## 2. Modul Document Management System

Kode Modul: M02
Cakupan: Kebijakan, SOP, Pedoman, Formulir, IK, version control, review/approval/publish workflow, QR verification, expiry
Aktor Utama: Staf Dokumen, Reviewer, Approver, Admin Mutu, PIC Unit, Kepala Klinik, Pengguna Dokumen
Komponen: Kebijakan, SOP, Pedoman, Formulir, Instruksi Kerja, Version Control, QR Verification, Expiry Monitoring
Entitas Data: Document, Document Version, Document Category, Review Task, Approval Task, QR Token, Expiry Reminder, Distribution List

Kebutuhan khusus modul:
M02-SP-01. Setiap dokumen terbit harus memiliki nomor dokumen, versi, status, pemilik, tanggal efektif, dan tanggal kedaluwarsa.
M02-SP-02. QR verification harus menampilkan status dokumen resmi tanpa membuka file internal yang tidak berwenang.
M02-SP-03. Dokumen kedaluwarsa tidak boleh digunakan sebagai evidence aktif kecuali diberi justifikasi dan approval khusus.

### Objective

M02-OBJ-01. Menyediakan kemampuan kebijakan yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M02-OBJ-01a. Memastikan proses kebijakan memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M02-OBJ-02. Menyediakan kemampuan sop yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M02-OBJ-02a. Memastikan proses sop memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M02-OBJ-03. Menyediakan kemampuan pedoman yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M02-OBJ-03a. Memastikan proses pedoman memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M02-OBJ-04. Menyediakan kemampuan formulir yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M02-OBJ-04a. Memastikan proses formulir memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M02-OBJ-05. Menyediakan kemampuan instruksi kerja yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M02-OBJ-05a. Memastikan proses instruksi kerja memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M02-OBJ-06. Menyediakan kemampuan version control yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M02-OBJ-06a. Memastikan proses version control memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M02-OBJ-07. Menyediakan kemampuan qr verification yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M02-OBJ-07a. Memastikan proses qr verification memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M02-OBJ-08. Menyediakan kemampuan expiry monitoring yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M02-OBJ-08a. Memastikan proses expiry monitoring memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M02-OBJ-DATA-01. Mengelola entitas Document sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M02-OBJ-DATA-02. Mengelola entitas Document Version sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M02-OBJ-DATA-03. Mengelola entitas Document Category sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M02-OBJ-DATA-04. Mengelola entitas Review Task sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M02-OBJ-DATA-05. Mengelola entitas Approval Task sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M02-OBJ-DATA-06. Mengelola entitas QR Token sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M02-OBJ-DATA-07. Mengelola entitas Expiry Reminder sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M02-OBJ-DATA-08. Mengelola entitas Distribution List sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.

### User Story

M02-US-001. Sebagai Staf Dokumen, saya ingin membuat data baru dengan validasi lengkap pada Kebijakan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-001-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-001-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-001-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-002. Sebagai Staf Dokumen, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada SOP, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-002-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-002-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-002-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-003. Sebagai Staf Dokumen, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Pedoman, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-003-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-003-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-003-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-004. Sebagai Staf Dokumen, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Formulir, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-004-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-004-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-004-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-005. Sebagai Staf Dokumen, saya ingin melihat riwayat perubahan dan keputusan pada Instruksi Kerja, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-005-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-005-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-005-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-006. Sebagai Staf Dokumen, saya ingin mencari data berdasarkan filter operasional pada Version Control, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-006-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-006-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-006-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-007. Sebagai Staf Dokumen, saya ingin mengekspor data untuk rapat manajemen pada QR Verification, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-007-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-007-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-007-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-008. Sebagai Staf Dokumen, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Expiry Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-008-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-008-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-008-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-009. Sebagai Staf Dokumen, saya ingin menghubungkan data modul ini dengan modul lain pada Kebijakan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-009-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-009-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-009-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-010. Sebagai Staf Dokumen, saya ingin memantau status pekerjaan yang belum selesai pada SOP, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-010-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-010-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-010-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-011. Sebagai Staf Dokumen, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Pedoman, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-011-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-011-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-011-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-012. Sebagai Staf Dokumen, saya ingin membuat laporan periodik yang siap diaudit pada Formulir, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-012-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-012-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-012-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-013. Sebagai Reviewer, saya ingin membuat data baru dengan validasi lengkap pada Instruksi Kerja, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-013-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-013-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-013-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-014. Sebagai Reviewer, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Version Control, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-014-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-014-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-014-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-015. Sebagai Reviewer, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada QR Verification, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-015-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-015-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-015-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-016. Sebagai Reviewer, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Expiry Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-016-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-016-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-016-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-017. Sebagai Reviewer, saya ingin melihat riwayat perubahan dan keputusan pada Kebijakan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-017-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-017-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-017-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-018. Sebagai Reviewer, saya ingin mencari data berdasarkan filter operasional pada SOP, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-018-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-018-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-018-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-019. Sebagai Reviewer, saya ingin mengekspor data untuk rapat manajemen pada Pedoman, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-019-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-019-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-019-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-020. Sebagai Reviewer, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Formulir, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-020-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-020-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-020-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-021. Sebagai Reviewer, saya ingin menghubungkan data modul ini dengan modul lain pada Instruksi Kerja, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-021-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-021-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-021-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-022. Sebagai Reviewer, saya ingin memantau status pekerjaan yang belum selesai pada Version Control, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-022-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-022-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-022-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-023. Sebagai Reviewer, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada QR Verification, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-023-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-023-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-023-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-024. Sebagai Reviewer, saya ingin membuat laporan periodik yang siap diaudit pada Expiry Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-024-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-024-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-024-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-025. Sebagai Approver, saya ingin membuat data baru dengan validasi lengkap pada Kebijakan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-025-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-025-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-025-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-026. Sebagai Approver, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada SOP, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-026-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-026-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-026-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-027. Sebagai Approver, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Pedoman, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-027-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-027-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-027-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-028. Sebagai Approver, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Formulir, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-028-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-028-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-028-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-029. Sebagai Approver, saya ingin melihat riwayat perubahan dan keputusan pada Instruksi Kerja, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-029-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-029-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-029-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-030. Sebagai Approver, saya ingin mencari data berdasarkan filter operasional pada Version Control, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-030-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-030-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-030-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-031. Sebagai Approver, saya ingin mengekspor data untuk rapat manajemen pada QR Verification, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-031-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-031-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-031-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-032. Sebagai Approver, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Expiry Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-032-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-032-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-032-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-033. Sebagai Approver, saya ingin menghubungkan data modul ini dengan modul lain pada Kebijakan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-033-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-033-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-033-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-034. Sebagai Approver, saya ingin memantau status pekerjaan yang belum selesai pada SOP, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-034-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-034-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-034-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-035. Sebagai Approver, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Pedoman, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-035-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-035-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-035-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-036. Sebagai Approver, saya ingin membuat laporan periodik yang siap diaudit pada Formulir, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-036-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-036-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-036-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-037. Sebagai Admin Mutu, saya ingin membuat data baru dengan validasi lengkap pada Instruksi Kerja, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-037-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-037-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-037-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-038. Sebagai Admin Mutu, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Version Control, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-038-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-038-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-038-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-039. Sebagai Admin Mutu, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada QR Verification, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-039-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-039-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-039-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-040. Sebagai Admin Mutu, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Expiry Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-040-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-040-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-040-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-041. Sebagai Admin Mutu, saya ingin melihat riwayat perubahan dan keputusan pada Kebijakan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-041-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-041-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-041-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M02-US-042. Sebagai Admin Mutu, saya ingin mencari data berdasarkan filter operasional pada SOP, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M02-US-042-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M02-US-042-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M02-US-042-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.

### Use Case

M02-UC-01. Membuat Dan Menyimpan Entri Baru untuk Kebijakan
  Aktor Utama: Staf Dokumen.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Kebijakan dan menjalankan aksi membuat dan menyimpan entri baru.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Document tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Kebijakan sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Document tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M02-UC-02. Mengirim Entri Ke Workflow Persetujuan untuk SOP
  Aktor Utama: Reviewer.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu SOP dan menjalankan aksi mengirim entri ke workflow persetujuan.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Document Version tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja SOP sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Document Version tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M02-UC-03. Melakukan Review Substansi untuk Pedoman
  Aktor Utama: Approver.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Pedoman dan menjalankan aksi melakukan review substansi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Document Category tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Pedoman sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Document Category tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M02-UC-04. Memberikan Approval Final untuk Formulir
  Aktor Utama: Admin Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Formulir dan menjalankan aksi memberikan approval final.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Review Task tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Formulir sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Review Task tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M02-UC-05. Mengembalikan Entri Untuk Revisi untuk Instruksi Kerja
  Aktor Utama: PIC Unit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Instruksi Kerja dan menjalankan aksi mengembalikan entri untuk revisi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Approval Task tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Instruksi Kerja sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Approval Task tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M02-UC-06. Mempublikasikan Hasil Yang Telah Sah untuk Version Control
  Aktor Utama: Kepala Klinik.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Version Control dan menjalankan aksi mempublikasikan hasil yang telah sah.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi QR Token tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Version Control sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data QR Token tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M02-UC-07. Menautkan Evidence Atau Lampiran untuk QR Verification
  Aktor Utama: Pengguna Dokumen.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu QR Verification dan menjalankan aksi menautkan evidence atau lampiran.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Expiry Reminder tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja QR Verification sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Expiry Reminder tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M02-UC-08. Melakukan Monitoring Tenggat untuk Expiry Monitoring
  Aktor Utama: Staf Dokumen.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Expiry Monitoring dan menjalankan aksi melakukan monitoring tenggat.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Distribution List tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Expiry Monitoring sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Distribution List tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M02-UC-09. Menutup Pekerjaan Setelah Seluruh Syarat Terpenuhi untuk Kebijakan
  Aktor Utama: Reviewer.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Kebijakan dan menjalankan aksi menutup pekerjaan setelah seluruh syarat terpenuhi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Document tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Kebijakan sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Document tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M02-UC-10. Mengekspor Laporan Resmi untuk SOP
  Aktor Utama: Approver.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu SOP dan menjalankan aksi mengekspor laporan resmi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Document Version tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja SOP sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Document Version tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M02-UC-11. Mengubah Konfigurasi Referensi untuk Pedoman
  Aktor Utama: Admin Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Pedoman dan menjalankan aksi mengubah konfigurasi referensi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Document Category tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Pedoman sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Document Category tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M02-UC-12. Menelusuri Audit Trail untuk Formulir
  Aktor Utama: PIC Unit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Formulir dan menjalankan aksi menelusuri audit trail.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Review Task tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Formulir sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Review Task tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.

### Functional Requirement

FR-M02-001. Sistem harus menyediakan menu Kebijakan dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M02-002. Sistem harus menghubungkan Kebijakan dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M02-003. Sistem harus mencatat audit trail untuk setiap perubahan data Kebijakan, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M02-004. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Kebijakan bila relevan dengan proses bisnis.
FR-M02-005. Sistem harus menyediakan menu SOP dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M02-006. Sistem harus menghubungkan SOP dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M02-007. Sistem harus mencatat audit trail untuk setiap perubahan data SOP, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M02-008. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk SOP bila relevan dengan proses bisnis.
FR-M02-009. Sistem harus menyediakan menu Pedoman dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M02-010. Sistem harus menghubungkan Pedoman dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M02-011. Sistem harus mencatat audit trail untuk setiap perubahan data Pedoman, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M02-012. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Pedoman bila relevan dengan proses bisnis.
FR-M02-013. Sistem harus menyediakan menu Formulir dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M02-014. Sistem harus menghubungkan Formulir dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M02-015. Sistem harus mencatat audit trail untuk setiap perubahan data Formulir, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M02-016. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Formulir bila relevan dengan proses bisnis.
FR-M02-017. Sistem harus menyediakan menu Instruksi Kerja dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M02-018. Sistem harus menghubungkan Instruksi Kerja dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M02-019. Sistem harus mencatat audit trail untuk setiap perubahan data Instruksi Kerja, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M02-020. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Instruksi Kerja bila relevan dengan proses bisnis.
FR-M02-021. Sistem harus menyediakan menu Version Control dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M02-022. Sistem harus menghubungkan Version Control dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M02-023. Sistem harus mencatat audit trail untuk setiap perubahan data Version Control, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M02-024. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Version Control bila relevan dengan proses bisnis.
FR-M02-025. Sistem harus menyediakan menu QR Verification dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M02-026. Sistem harus menghubungkan QR Verification dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M02-027. Sistem harus mencatat audit trail untuk setiap perubahan data QR Verification, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M02-028. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk QR Verification bila relevan dengan proses bisnis.
FR-M02-029. Sistem harus menyediakan menu Expiry Monitoring dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M02-030. Sistem harus menghubungkan Expiry Monitoring dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M02-031. Sistem harus mencatat audit trail untuk setiap perubahan data Expiry Monitoring, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M02-032. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Expiry Monitoring bila relevan dengan proses bisnis.
FR-M02-033. Sistem harus memvalidasi entitas Document menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M02-034. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Document dengan pagination standar.
FR-M02-035. Sistem harus memvalidasi entitas Document Version menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M02-036. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Document Version dengan pagination standar.
FR-M02-037. Sistem harus memvalidasi entitas Document Category menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M02-038. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Document Category dengan pagination standar.
FR-M02-039. Sistem harus memvalidasi entitas Review Task menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M02-040. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Review Task dengan pagination standar.
FR-M02-041. Sistem harus memvalidasi entitas Approval Task menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M02-042. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Approval Task dengan pagination standar.
FR-M02-043. Sistem harus memvalidasi entitas QR Token menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M02-044. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas QR Token dengan pagination standar.
FR-M02-045. Sistem harus memvalidasi entitas Expiry Reminder menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M02-046. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Expiry Reminder dengan pagination standar.
FR-M02-047. Sistem harus memvalidasi entitas Distribution List menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M02-048. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Distribution List dengan pagination standar.
FR-M02-049. Sistem harus memenuhi ketentuan khusus: Setiap dokumen terbit harus memiliki nomor dokumen, versi, status, pemilik, tanggal efektif, dan tanggal kedaluwarsa.
FR-M02-050. Sistem harus memenuhi ketentuan khusus: QR verification harus menampilkan status dokumen resmi tanpa membuka file internal yang tidak berwenang.
FR-M02-051. Sistem harus memenuhi ketentuan khusus: Dokumen kedaluwarsa tidak boleh digunakan sebagai evidence aktif kecuali diberi justifikasi dan approval khusus.
FR-M02-052. Sistem harus menyediakan kontrol operasional tambahan untuk Formulir, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M02-053. Sistem harus menyediakan kontrol operasional tambahan untuk Instruksi Kerja, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M02-054. Sistem harus menyediakan kontrol operasional tambahan untuk Version Control, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M02-055. Sistem harus menyediakan kontrol operasional tambahan untuk QR Verification, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M02-056. Sistem harus menyediakan kontrol operasional tambahan untuk Expiry Monitoring, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M02-057. Sistem harus menyediakan kontrol operasional tambahan untuk Kebijakan, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M02-058. Sistem harus menyediakan kontrol operasional tambahan untuk SOP, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.

### Non Functional Requirement

NFR-M02-001. Keamanan: sistem harus memastikan akses berbasis role, permission, unit, dan status workflow diterapkan konsisten pada seluruh fitur modul Document Management System.
NFR-M02-002. Keamanan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M02-003. Kinerja: sistem harus memastikan halaman daftar utama merespons cepat untuk data operasional besar pada seluruh fitur modul Document Management System.
NFR-M02-004. Kinerja: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M02-005. Reliabilitas: sistem harus memastikan transaksi penting menggunakan database transaction dan mekanisme retry terukur pada seluruh fitur modul Document Management System.
NFR-M02-006. Reliabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M02-007. Auditabilitas: sistem harus memastikan setiap perubahan bernilai kepatuhan memiliki jejak waktu, aktor, nilai lama, dan nilai baru pada seluruh fitur modul Document Management System.
NFR-M02-008. Auditabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M02-009. Ketersediaan: sistem harus memastikan fitur kritikal tetap stabil saat Redis digunakan untuk queue, cache, dan session pada seluruh fitur modul Document Management System.
NFR-M02-010. Ketersediaan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M02-011. Skalabilitas: sistem harus memastikan desain tabel dan indeks mendukung pertumbuhan multi tahun pada seluruh fitur modul Document Management System.
NFR-M02-012. Skalabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M02-013. Usability: sistem harus memastikan alur kerja utama dapat diselesaikan oleh pengguna non teknis dengan instruksi jelas pada seluruh fitur modul Document Management System.
NFR-M02-014. Usability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M02-015. Interoperabilitas: sistem harus memastikan API menggunakan JSON, pagination, filter, dan kode error konsisten pada seluruh fitur modul Document Management System.
NFR-M02-016. Interoperabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M02-017. Privasi: sistem harus memastikan data sensitif hanya tampil sesuai kebutuhan tugas dan kewenangan pada seluruh fitur modul Document Management System.
NFR-M02-018. Privasi: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M02-019. Maintainability: sistem harus memastikan konfigurasi bisnis disimpan sebagai data, bukan konstanta tersembunyi di kode pada seluruh fitur modul Document Management System.
NFR-M02-020. Maintainability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M02-021. Database: query utama modul harus memiliki index yang sesuai untuk filter status, periode, unit, pemilik, dan workflow.
NFR-M02-022. Queue: proses notifikasi, ekspor, reminder, dan kalkulasi agregat harus berjalan asynchronous menggunakan Redis queue.
NFR-M02-023. Observability: error, retry, durasi proses, dan kegagalan integrasi harus tercatat dalam log terstruktur.

### Acceptance Criteria

AC-M02-001. Diberikan pengguna Staf Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Kebijakan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-001a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-002. Diberikan pengguna Reviewer memiliki permission yang benar, ketika pengguna menjalankan proses SOP, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-002a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-003. Diberikan pengguna Approver memiliki permission yang benar, ketika pengguna menjalankan proses Pedoman, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-003a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-004. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Formulir, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-004a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-004b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M02-005. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Instruksi Kerja, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-005a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-006. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Version Control, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-006a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-007. Diberikan pengguna Pengguna Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses QR Verification, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-007a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-008. Diberikan pengguna Staf Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Expiry Monitoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-008a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-008b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M02-009. Diberikan pengguna Reviewer memiliki permission yang benar, ketika pengguna menjalankan proses Kebijakan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-009a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-010. Diberikan pengguna Approver memiliki permission yang benar, ketika pengguna menjalankan proses SOP, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-010a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-011. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Pedoman, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-011a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-012. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Formulir, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-012a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-012b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M02-013. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Instruksi Kerja, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-013a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-014. Diberikan pengguna Pengguna Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Version Control, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-014a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-015. Diberikan pengguna Staf Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses QR Verification, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-015a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-016. Diberikan pengguna Reviewer memiliki permission yang benar, ketika pengguna menjalankan proses Expiry Monitoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-016a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-016b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M02-017. Diberikan pengguna Approver memiliki permission yang benar, ketika pengguna menjalankan proses Kebijakan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-017a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-018. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses SOP, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-018a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-019. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Pedoman, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-019a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-020. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Formulir, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-020a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-020b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M02-021. Diberikan pengguna Pengguna Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Instruksi Kerja, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-021a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-022. Diberikan pengguna Staf Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Version Control, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-022a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-023. Diberikan pengguna Reviewer memiliki permission yang benar, ketika pengguna menjalankan proses QR Verification, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-023a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-024. Diberikan pengguna Approver memiliki permission yang benar, ketika pengguna menjalankan proses Expiry Monitoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-024a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-024b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M02-025. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Kebijakan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-025a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-026. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses SOP, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-026a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-027. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Pedoman, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-027a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-028. Diberikan pengguna Pengguna Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Formulir, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-028a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-028b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M02-029. Diberikan pengguna Staf Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Instruksi Kerja, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-029a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-030. Diberikan pengguna Reviewer memiliki permission yang benar, ketika pengguna menjalankan proses Version Control, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-030a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-031. Diberikan pengguna Approver memiliki permission yang benar, ketika pengguna menjalankan proses QR Verification, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-031a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-032. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Expiry Monitoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M02-032a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M02-032b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.

### Business Rules

BR-M02-001. Data Document pada Kebijakan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-002. Data Document Version pada SOP hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-003. Data Document Category pada Pedoman hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-003a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M02-004. Data Review Task pada Formulir hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-005. Data Approval Task pada Instruksi Kerja hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-005b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M02-006. Data QR Token pada Version Control hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-006a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M02-007. Data Expiry Reminder pada QR Verification hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-008. Data Distribution List pada Expiry Monitoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-009. Data Document pada Kebijakan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-009a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M02-010. Data Document Version pada SOP hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-010b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M02-011. Data Document Category pada Pedoman hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-012. Data Review Task pada Formulir hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-012a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M02-013. Data Approval Task pada Instruksi Kerja hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-014. Data QR Token pada Version Control hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-015. Data Expiry Reminder pada QR Verification hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-015a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M02-015b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M02-016. Data Distribution List pada Expiry Monitoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-017. Data Document pada Kebijakan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-018. Data Document Version pada SOP hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-018a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M02-019. Data Document Category pada Pedoman hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-020. Data Review Task pada Formulir hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-020b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M02-021. Data Approval Task pada Instruksi Kerja hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-021a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M02-022. Data QR Token pada Version Control hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-023. Data Expiry Reminder pada QR Verification hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-024. Data Distribution List pada Expiry Monitoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-024a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M02-025. Data Document pada Kebijakan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-025b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M02-026. Data Document Version pada SOP hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-027. Data Document Category pada Pedoman hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-027a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M02-028. Data Review Task pada Formulir hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-029. Data Approval Task pada Instruksi Kerja hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-030. Data QR Token pada Version Control hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-030a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M02-030b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M02-031. Data Expiry Reminder pada QR Verification hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-032. Data Distribution List pada Expiry Monitoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-033. Data Document pada Kebijakan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-033a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M02-034. Data Document Version pada SOP hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-035. Data Document Category pada Pedoman hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-035b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M02-036. Data Review Task pada Formulir hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M02-036a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M02-KHS-01. Setiap dokumen terbit harus memiliki nomor dokumen, versi, status, pemilik, tanggal efektif, dan tanggal kedaluwarsa.
BR-M02-KHS-02. QR verification harus menampilkan status dokumen resmi tanpa membuka file internal yang tidak berwenang.
BR-M02-KHS-03. Dokumen kedaluwarsa tidak boleh digunakan sebagai evidence aktif kecuali diberi justifikasi dan approval khusus.

### UI Requirement

UI-M02-001. Modul harus menyediakan dashboard ringkasan untuk Kebijakan dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M02-001a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M02-001b. Halaman dashboard ringkasan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M02-002. Modul harus menyediakan halaman daftar untuk SOP dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M02-002a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M02-002b. Halaman halaman daftar harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M02-003. Modul harus menyediakan form tambah dan ubah untuk Pedoman dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M02-003a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M02-003b. Halaman form tambah dan ubah harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M02-004. Modul harus menyediakan panel detail untuk Formulir dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M02-004a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M02-004b. Halaman panel detail harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M02-005. Modul harus menyediakan timeline workflow untuk Instruksi Kerja dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M02-005a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M02-005b. Halaman timeline workflow harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M02-006. Modul harus menyediakan tab lampiran untuk Version Control dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M02-006a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M02-006b. Halaman tab lampiran harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M02-007. Modul harus menyediakan modal approval untuk QR Verification dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M02-007a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M02-007b. Halaman modal approval harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M02-008. Modul harus menyediakan filter lanjutan untuk Expiry Monitoring dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M02-008a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M02-008b. Halaman filter lanjutan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M02-009. Modul harus menyediakan indikator status untuk Kebijakan dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M02-009a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M02-009b. Halaman indikator status harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M02-010. Modul harus menyediakan halaman konfigurasi untuk SOP dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M02-010a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M02-010b. Halaman halaman konfigurasi harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M02-011. Modul harus menyediakan wizard import untuk Pedoman dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M02-011a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M02-011b. Halaman wizard import harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M02-012. Modul harus menyediakan halaman laporan untuk Formulir dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M02-012a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M02-012b. Halaman halaman laporan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M02-999. Semua form modul Document Management System harus menampilkan validasi inline, konfirmasi sebelum aksi irreversible, dan indikator penyimpanan saat proses berlangsung.

### API Requirement

API-M02-001. Endpoint /api/v1/documents harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M02-001a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M02-001b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M02-002. Endpoint /api/v1/documents/{id}/versions harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M02-002a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M02-002b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M02-003. Endpoint /api/v1/documents/{id}/review harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M02-003a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M02-003b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M02-004. Endpoint /api/v1/documents/{id}/approve harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M02-004a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M02-004b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M02-005. Endpoint /api/v1/documents/{id}/publish harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M02-005a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M02-005b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M02-006. Endpoint /api/v1/documents/verify/{qr} harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M02-006a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M02-006b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M02-007. Endpoint /api/v1/documents/expiring harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M02-007a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M02-007b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M02-OP-001. Operasi create harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M02-OP-002. Operasi read harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M02-OP-003. Operasi update harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M02-OP-004. Operasi submit harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M02-OP-005. Operasi approve harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M02-OP-006. Operasi reject harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M02-OP-007. Operasi publish harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M02-OP-008. Operasi archive harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M02-OP-009. Operasi search harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M02-OP-010. Operasi export harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M02-OP-011. Operasi history harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M02-OP-012. Operasi configure harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M02-SEC-001. API modul Document Management System harus menerapkan rate limiting, CSRF/session protection untuk web, dan pembatasan akses data lintas unit.

## 3. Modul Internal Audit

Kode Modul: M03
Cakupan: Program, Schedule, Checklist, Finding, Report
Aktor Utama: Admin Mutu, Ketua Tim Audit, Auditor Internal, Auditee, PIC Unit, Kepala Klinik, Manajemen
Komponen: Program Audit, Jadwal Audit, Checklist, Temuan, Laporan Audit, Tindak Lanjut, Kertas Kerja, Rekap Kepatuhan
Entitas Data: Audit Program, Audit Schedule, Audit Checklist, Audit Finding, Audit Report, Audit Assignment, Audit Evidence, Follow Up Request

Kebutuhan khusus modul:
M03-SP-01. Auditor tidak boleh mengaudit unit tempat ia menjadi penanggung jawab utama.
M03-SP-02. Temuan audit harus dapat dikonversi menjadi CAPA dengan jejak referensi otomatis.
M03-SP-03. Checklist harus dapat dikaitkan ke standar akreditasi, risiko, atau dokumen internal.

### Objective

M03-OBJ-01. Menyediakan kemampuan program audit yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M03-OBJ-01a. Memastikan proses program audit memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M03-OBJ-02. Menyediakan kemampuan jadwal audit yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M03-OBJ-02a. Memastikan proses jadwal audit memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M03-OBJ-03. Menyediakan kemampuan checklist yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M03-OBJ-03a. Memastikan proses checklist memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M03-OBJ-04. Menyediakan kemampuan temuan yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M03-OBJ-04a. Memastikan proses temuan memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M03-OBJ-05. Menyediakan kemampuan laporan audit yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M03-OBJ-05a. Memastikan proses laporan audit memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M03-OBJ-06. Menyediakan kemampuan tindak lanjut yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M03-OBJ-06a. Memastikan proses tindak lanjut memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M03-OBJ-07. Menyediakan kemampuan kertas kerja yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M03-OBJ-07a. Memastikan proses kertas kerja memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M03-OBJ-08. Menyediakan kemampuan rekap kepatuhan yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M03-OBJ-08a. Memastikan proses rekap kepatuhan memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M03-OBJ-DATA-01. Mengelola entitas Audit Program sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M03-OBJ-DATA-02. Mengelola entitas Audit Schedule sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M03-OBJ-DATA-03. Mengelola entitas Audit Checklist sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M03-OBJ-DATA-04. Mengelola entitas Audit Finding sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M03-OBJ-DATA-05. Mengelola entitas Audit Report sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M03-OBJ-DATA-06. Mengelola entitas Audit Assignment sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M03-OBJ-DATA-07. Mengelola entitas Audit Evidence sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M03-OBJ-DATA-08. Mengelola entitas Follow Up Request sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.

### User Story

M03-US-001. Sebagai Admin Mutu, saya ingin membuat data baru dengan validasi lengkap pada Program Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-001-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-001-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-001-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-002. Sebagai Admin Mutu, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Jadwal Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-002-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-002-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-002-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-003. Sebagai Admin Mutu, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Checklist, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-003-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-003-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-003-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-004. Sebagai Admin Mutu, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Temuan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-004-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-004-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-004-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-005. Sebagai Admin Mutu, saya ingin melihat riwayat perubahan dan keputusan pada Laporan Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-005-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-005-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-005-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-006. Sebagai Admin Mutu, saya ingin mencari data berdasarkan filter operasional pada Tindak Lanjut, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-006-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-006-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-006-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-007. Sebagai Admin Mutu, saya ingin mengekspor data untuk rapat manajemen pada Kertas Kerja, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-007-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-007-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-007-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-008. Sebagai Admin Mutu, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Rekap Kepatuhan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-008-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-008-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-008-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-009. Sebagai Admin Mutu, saya ingin menghubungkan data modul ini dengan modul lain pada Program Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-009-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-009-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-009-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-010. Sebagai Admin Mutu, saya ingin memantau status pekerjaan yang belum selesai pada Jadwal Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-010-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-010-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-010-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-011. Sebagai Admin Mutu, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Checklist, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-011-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-011-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-011-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-012. Sebagai Admin Mutu, saya ingin membuat laporan periodik yang siap diaudit pada Temuan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-012-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-012-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-012-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-013. Sebagai Ketua Tim Audit, saya ingin membuat data baru dengan validasi lengkap pada Laporan Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-013-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-013-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-013-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-014. Sebagai Ketua Tim Audit, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Tindak Lanjut, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-014-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-014-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-014-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-015. Sebagai Ketua Tim Audit, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Kertas Kerja, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-015-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-015-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-015-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-016. Sebagai Ketua Tim Audit, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Rekap Kepatuhan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-016-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-016-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-016-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-017. Sebagai Ketua Tim Audit, saya ingin melihat riwayat perubahan dan keputusan pada Program Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-017-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-017-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-017-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-018. Sebagai Ketua Tim Audit, saya ingin mencari data berdasarkan filter operasional pada Jadwal Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-018-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-018-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-018-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-019. Sebagai Ketua Tim Audit, saya ingin mengekspor data untuk rapat manajemen pada Checklist, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-019-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-019-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-019-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-020. Sebagai Ketua Tim Audit, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Temuan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-020-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-020-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-020-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-021. Sebagai Ketua Tim Audit, saya ingin menghubungkan data modul ini dengan modul lain pada Laporan Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-021-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-021-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-021-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-022. Sebagai Ketua Tim Audit, saya ingin memantau status pekerjaan yang belum selesai pada Tindak Lanjut, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-022-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-022-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-022-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-023. Sebagai Ketua Tim Audit, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Kertas Kerja, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-023-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-023-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-023-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-024. Sebagai Ketua Tim Audit, saya ingin membuat laporan periodik yang siap diaudit pada Rekap Kepatuhan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-024-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-024-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-024-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-025. Sebagai Auditor Internal, saya ingin membuat data baru dengan validasi lengkap pada Program Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-025-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-025-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-025-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-026. Sebagai Auditor Internal, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Jadwal Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-026-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-026-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-026-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-027. Sebagai Auditor Internal, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Checklist, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-027-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-027-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-027-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-028. Sebagai Auditor Internal, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Temuan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-028-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-028-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-028-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-029. Sebagai Auditor Internal, saya ingin melihat riwayat perubahan dan keputusan pada Laporan Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-029-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-029-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-029-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-030. Sebagai Auditor Internal, saya ingin mencari data berdasarkan filter operasional pada Tindak Lanjut, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-030-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-030-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-030-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-031. Sebagai Auditor Internal, saya ingin mengekspor data untuk rapat manajemen pada Kertas Kerja, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-031-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-031-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-031-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-032. Sebagai Auditor Internal, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Rekap Kepatuhan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-032-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-032-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-032-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-033. Sebagai Auditor Internal, saya ingin menghubungkan data modul ini dengan modul lain pada Program Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-033-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-033-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-033-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-034. Sebagai Auditor Internal, saya ingin memantau status pekerjaan yang belum selesai pada Jadwal Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-034-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-034-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-034-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-035. Sebagai Auditor Internal, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Checklist, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-035-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-035-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-035-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-036. Sebagai Auditor Internal, saya ingin membuat laporan periodik yang siap diaudit pada Temuan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-036-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-036-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-036-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-037. Sebagai Auditee, saya ingin membuat data baru dengan validasi lengkap pada Laporan Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-037-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-037-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-037-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-038. Sebagai Auditee, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Tindak Lanjut, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-038-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-038-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-038-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-039. Sebagai Auditee, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Kertas Kerja, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-039-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-039-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-039-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-040. Sebagai Auditee, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Rekap Kepatuhan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-040-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-040-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-040-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-041. Sebagai Auditee, saya ingin melihat riwayat perubahan dan keputusan pada Program Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-041-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-041-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-041-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M03-US-042. Sebagai Auditee, saya ingin mencari data berdasarkan filter operasional pada Jadwal Audit, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M03-US-042-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M03-US-042-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M03-US-042-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.

### Use Case

M03-UC-01. Membuat Dan Menyimpan Entri Baru untuk Program Audit
  Aktor Utama: Admin Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Program Audit dan menjalankan aksi membuat dan menyimpan entri baru.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Audit Program tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Program Audit sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Audit Program tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M03-UC-02. Mengirim Entri Ke Workflow Persetujuan untuk Jadwal Audit
  Aktor Utama: Ketua Tim Audit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Jadwal Audit dan menjalankan aksi mengirim entri ke workflow persetujuan.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Audit Schedule tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Jadwal Audit sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Audit Schedule tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M03-UC-03. Melakukan Review Substansi untuk Checklist
  Aktor Utama: Auditor Internal.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Checklist dan menjalankan aksi melakukan review substansi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Audit Checklist tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Checklist sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Audit Checklist tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M03-UC-04. Memberikan Approval Final untuk Temuan
  Aktor Utama: Auditee.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Temuan dan menjalankan aksi memberikan approval final.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Audit Finding tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Temuan sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Audit Finding tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M03-UC-05. Mengembalikan Entri Untuk Revisi untuk Laporan Audit
  Aktor Utama: PIC Unit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Laporan Audit dan menjalankan aksi mengembalikan entri untuk revisi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Audit Report tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Laporan Audit sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Audit Report tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M03-UC-06. Mempublikasikan Hasil Yang Telah Sah untuk Tindak Lanjut
  Aktor Utama: Kepala Klinik.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Tindak Lanjut dan menjalankan aksi mempublikasikan hasil yang telah sah.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Audit Assignment tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Tindak Lanjut sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Audit Assignment tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M03-UC-07. Menautkan Evidence Atau Lampiran untuk Kertas Kerja
  Aktor Utama: Manajemen.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Kertas Kerja dan menjalankan aksi menautkan evidence atau lampiran.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Audit Evidence tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Kertas Kerja sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Audit Evidence tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M03-UC-08. Melakukan Monitoring Tenggat untuk Rekap Kepatuhan
  Aktor Utama: Admin Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Rekap Kepatuhan dan menjalankan aksi melakukan monitoring tenggat.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Follow Up Request tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Rekap Kepatuhan sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Follow Up Request tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M03-UC-09. Menutup Pekerjaan Setelah Seluruh Syarat Terpenuhi untuk Program Audit
  Aktor Utama: Ketua Tim Audit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Program Audit dan menjalankan aksi menutup pekerjaan setelah seluruh syarat terpenuhi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Audit Program tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Program Audit sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Audit Program tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M03-UC-10. Mengekspor Laporan Resmi untuk Jadwal Audit
  Aktor Utama: Auditor Internal.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Jadwal Audit dan menjalankan aksi mengekspor laporan resmi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Audit Schedule tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Jadwal Audit sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Audit Schedule tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M03-UC-11. Mengubah Konfigurasi Referensi untuk Checklist
  Aktor Utama: Auditee.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Checklist dan menjalankan aksi mengubah konfigurasi referensi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Audit Checklist tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Checklist sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Audit Checklist tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M03-UC-12. Menelusuri Audit Trail untuk Temuan
  Aktor Utama: PIC Unit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Temuan dan menjalankan aksi menelusuri audit trail.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Audit Finding tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Temuan sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Audit Finding tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.

### Functional Requirement

FR-M03-001. Sistem harus menyediakan menu Program Audit dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M03-002. Sistem harus menghubungkan Program Audit dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M03-003. Sistem harus mencatat audit trail untuk setiap perubahan data Program Audit, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M03-004. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Program Audit bila relevan dengan proses bisnis.
FR-M03-005. Sistem harus menyediakan menu Jadwal Audit dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M03-006. Sistem harus menghubungkan Jadwal Audit dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M03-007. Sistem harus mencatat audit trail untuk setiap perubahan data Jadwal Audit, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M03-008. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Jadwal Audit bila relevan dengan proses bisnis.
FR-M03-009. Sistem harus menyediakan menu Checklist dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M03-010. Sistem harus menghubungkan Checklist dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M03-011. Sistem harus mencatat audit trail untuk setiap perubahan data Checklist, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M03-012. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Checklist bila relevan dengan proses bisnis.
FR-M03-013. Sistem harus menyediakan menu Temuan dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M03-014. Sistem harus menghubungkan Temuan dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M03-015. Sistem harus mencatat audit trail untuk setiap perubahan data Temuan, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M03-016. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Temuan bila relevan dengan proses bisnis.
FR-M03-017. Sistem harus menyediakan menu Laporan Audit dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M03-018. Sistem harus menghubungkan Laporan Audit dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M03-019. Sistem harus mencatat audit trail untuk setiap perubahan data Laporan Audit, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M03-020. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Laporan Audit bila relevan dengan proses bisnis.
FR-M03-021. Sistem harus menyediakan menu Tindak Lanjut dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M03-022. Sistem harus menghubungkan Tindak Lanjut dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M03-023. Sistem harus mencatat audit trail untuk setiap perubahan data Tindak Lanjut, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M03-024. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Tindak Lanjut bila relevan dengan proses bisnis.
FR-M03-025. Sistem harus menyediakan menu Kertas Kerja dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M03-026. Sistem harus menghubungkan Kertas Kerja dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M03-027. Sistem harus mencatat audit trail untuk setiap perubahan data Kertas Kerja, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M03-028. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Kertas Kerja bila relevan dengan proses bisnis.
FR-M03-029. Sistem harus menyediakan menu Rekap Kepatuhan dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M03-030. Sistem harus menghubungkan Rekap Kepatuhan dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M03-031. Sistem harus mencatat audit trail untuk setiap perubahan data Rekap Kepatuhan, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M03-032. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Rekap Kepatuhan bila relevan dengan proses bisnis.
FR-M03-033. Sistem harus memvalidasi entitas Audit Program menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M03-034. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Audit Program dengan pagination standar.
FR-M03-035. Sistem harus memvalidasi entitas Audit Schedule menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M03-036. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Audit Schedule dengan pagination standar.
FR-M03-037. Sistem harus memvalidasi entitas Audit Checklist menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M03-038. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Audit Checklist dengan pagination standar.
FR-M03-039. Sistem harus memvalidasi entitas Audit Finding menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M03-040. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Audit Finding dengan pagination standar.
FR-M03-041. Sistem harus memvalidasi entitas Audit Report menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M03-042. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Audit Report dengan pagination standar.
FR-M03-043. Sistem harus memvalidasi entitas Audit Assignment menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M03-044. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Audit Assignment dengan pagination standar.
FR-M03-045. Sistem harus memvalidasi entitas Audit Evidence menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M03-046. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Audit Evidence dengan pagination standar.
FR-M03-047. Sistem harus memvalidasi entitas Follow Up Request menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M03-048. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Follow Up Request dengan pagination standar.
FR-M03-049. Sistem harus memenuhi ketentuan khusus: Auditor tidak boleh mengaudit unit tempat ia menjadi penanggung jawab utama.
FR-M03-050. Sistem harus memenuhi ketentuan khusus: Temuan audit harus dapat dikonversi menjadi CAPA dengan jejak referensi otomatis.
FR-M03-051. Sistem harus memenuhi ketentuan khusus: Checklist harus dapat dikaitkan ke standar akreditasi, risiko, atau dokumen internal.
FR-M03-052. Sistem harus menyediakan kontrol operasional tambahan untuk Temuan, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M03-053. Sistem harus menyediakan kontrol operasional tambahan untuk Laporan Audit, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M03-054. Sistem harus menyediakan kontrol operasional tambahan untuk Tindak Lanjut, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M03-055. Sistem harus menyediakan kontrol operasional tambahan untuk Kertas Kerja, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M03-056. Sistem harus menyediakan kontrol operasional tambahan untuk Rekap Kepatuhan, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M03-057. Sistem harus menyediakan kontrol operasional tambahan untuk Program Audit, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M03-058. Sistem harus menyediakan kontrol operasional tambahan untuk Jadwal Audit, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.

### Non Functional Requirement

NFR-M03-001. Keamanan: sistem harus memastikan akses berbasis role, permission, unit, dan status workflow diterapkan konsisten pada seluruh fitur modul Internal Audit.
NFR-M03-002. Keamanan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M03-003. Kinerja: sistem harus memastikan halaman daftar utama merespons cepat untuk data operasional besar pada seluruh fitur modul Internal Audit.
NFR-M03-004. Kinerja: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M03-005. Reliabilitas: sistem harus memastikan transaksi penting menggunakan database transaction dan mekanisme retry terukur pada seluruh fitur modul Internal Audit.
NFR-M03-006. Reliabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M03-007. Auditabilitas: sistem harus memastikan setiap perubahan bernilai kepatuhan memiliki jejak waktu, aktor, nilai lama, dan nilai baru pada seluruh fitur modul Internal Audit.
NFR-M03-008. Auditabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M03-009. Ketersediaan: sistem harus memastikan fitur kritikal tetap stabil saat Redis digunakan untuk queue, cache, dan session pada seluruh fitur modul Internal Audit.
NFR-M03-010. Ketersediaan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M03-011. Skalabilitas: sistem harus memastikan desain tabel dan indeks mendukung pertumbuhan multi tahun pada seluruh fitur modul Internal Audit.
NFR-M03-012. Skalabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M03-013. Usability: sistem harus memastikan alur kerja utama dapat diselesaikan oleh pengguna non teknis dengan instruksi jelas pada seluruh fitur modul Internal Audit.
NFR-M03-014. Usability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M03-015. Interoperabilitas: sistem harus memastikan API menggunakan JSON, pagination, filter, dan kode error konsisten pada seluruh fitur modul Internal Audit.
NFR-M03-016. Interoperabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M03-017. Privasi: sistem harus memastikan data sensitif hanya tampil sesuai kebutuhan tugas dan kewenangan pada seluruh fitur modul Internal Audit.
NFR-M03-018. Privasi: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M03-019. Maintainability: sistem harus memastikan konfigurasi bisnis disimpan sebagai data, bukan konstanta tersembunyi di kode pada seluruh fitur modul Internal Audit.
NFR-M03-020. Maintainability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M03-021. Database: query utama modul harus memiliki index yang sesuai untuk filter status, periode, unit, pemilik, dan workflow.
NFR-M03-022. Queue: proses notifikasi, ekspor, reminder, dan kalkulasi agregat harus berjalan asynchronous menggunakan Redis queue.
NFR-M03-023. Observability: error, retry, durasi proses, dan kegagalan integrasi harus tercatat dalam log terstruktur.

### Acceptance Criteria

AC-M03-001. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Program Audit, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-001a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-002. Diberikan pengguna Ketua Tim Audit memiliki permission yang benar, ketika pengguna menjalankan proses Jadwal Audit, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-002a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-003. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Checklist, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-003a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-004. Diberikan pengguna Auditee memiliki permission yang benar, ketika pengguna menjalankan proses Temuan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-004a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-004b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M03-005. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Laporan Audit, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-005a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-006. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Tindak Lanjut, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-006a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-007. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Kertas Kerja, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-007a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-008. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Rekap Kepatuhan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-008a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-008b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M03-009. Diberikan pengguna Ketua Tim Audit memiliki permission yang benar, ketika pengguna menjalankan proses Program Audit, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-009a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-010. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Jadwal Audit, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-010a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-011. Diberikan pengguna Auditee memiliki permission yang benar, ketika pengguna menjalankan proses Checklist, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-011a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-012. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Temuan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-012a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-012b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M03-013. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Laporan Audit, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-013a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-014. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Tindak Lanjut, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-014a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-015. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Kertas Kerja, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-015a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-016. Diberikan pengguna Ketua Tim Audit memiliki permission yang benar, ketika pengguna menjalankan proses Rekap Kepatuhan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-016a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-016b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M03-017. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Program Audit, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-017a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-018. Diberikan pengguna Auditee memiliki permission yang benar, ketika pengguna menjalankan proses Jadwal Audit, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-018a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-019. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Checklist, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-019a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-020. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Temuan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-020a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-020b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M03-021. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Laporan Audit, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-021a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-022. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Tindak Lanjut, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-022a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-023. Diberikan pengguna Ketua Tim Audit memiliki permission yang benar, ketika pengguna menjalankan proses Kertas Kerja, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-023a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-024. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Rekap Kepatuhan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-024a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-024b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M03-025. Diberikan pengguna Auditee memiliki permission yang benar, ketika pengguna menjalankan proses Program Audit, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-025a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-026. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Jadwal Audit, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-026a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-027. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Checklist, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-027a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-028. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Temuan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-028a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-028b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M03-029. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Laporan Audit, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-029a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-030. Diberikan pengguna Ketua Tim Audit memiliki permission yang benar, ketika pengguna menjalankan proses Tindak Lanjut, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-030a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-031. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Kertas Kerja, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-031a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-032. Diberikan pengguna Auditee memiliki permission yang benar, ketika pengguna menjalankan proses Rekap Kepatuhan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M03-032a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M03-032b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.

### Business Rules

BR-M03-001. Data Audit Program pada Program Audit hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-002. Data Audit Schedule pada Jadwal Audit hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-003. Data Audit Checklist pada Checklist hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-003a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M03-004. Data Audit Finding pada Temuan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-005. Data Audit Report pada Laporan Audit hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-005b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M03-006. Data Audit Assignment pada Tindak Lanjut hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-006a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M03-007. Data Audit Evidence pada Kertas Kerja hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-008. Data Follow Up Request pada Rekap Kepatuhan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-009. Data Audit Program pada Program Audit hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-009a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M03-010. Data Audit Schedule pada Jadwal Audit hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-010b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M03-011. Data Audit Checklist pada Checklist hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-012. Data Audit Finding pada Temuan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-012a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M03-013. Data Audit Report pada Laporan Audit hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-014. Data Audit Assignment pada Tindak Lanjut hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-015. Data Audit Evidence pada Kertas Kerja hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-015a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M03-015b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M03-016. Data Follow Up Request pada Rekap Kepatuhan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-017. Data Audit Program pada Program Audit hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-018. Data Audit Schedule pada Jadwal Audit hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-018a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M03-019. Data Audit Checklist pada Checklist hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-020. Data Audit Finding pada Temuan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-020b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M03-021. Data Audit Report pada Laporan Audit hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-021a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M03-022. Data Audit Assignment pada Tindak Lanjut hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-023. Data Audit Evidence pada Kertas Kerja hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-024. Data Follow Up Request pada Rekap Kepatuhan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-024a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M03-025. Data Audit Program pada Program Audit hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-025b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M03-026. Data Audit Schedule pada Jadwal Audit hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-027. Data Audit Checklist pada Checklist hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-027a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M03-028. Data Audit Finding pada Temuan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-029. Data Audit Report pada Laporan Audit hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-030. Data Audit Assignment pada Tindak Lanjut hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-030a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M03-030b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M03-031. Data Audit Evidence pada Kertas Kerja hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-032. Data Follow Up Request pada Rekap Kepatuhan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-033. Data Audit Program pada Program Audit hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-033a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M03-034. Data Audit Schedule pada Jadwal Audit hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-035. Data Audit Checklist pada Checklist hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-035b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M03-036. Data Audit Finding pada Temuan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M03-036a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M03-KHS-01. Auditor tidak boleh mengaudit unit tempat ia menjadi penanggung jawab utama.
BR-M03-KHS-02. Temuan audit harus dapat dikonversi menjadi CAPA dengan jejak referensi otomatis.
BR-M03-KHS-03. Checklist harus dapat dikaitkan ke standar akreditasi, risiko, atau dokumen internal.

### UI Requirement

UI-M03-001. Modul harus menyediakan dashboard ringkasan untuk Program Audit dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M03-001a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M03-001b. Halaman dashboard ringkasan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M03-002. Modul harus menyediakan halaman daftar untuk Jadwal Audit dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M03-002a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M03-002b. Halaman halaman daftar harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M03-003. Modul harus menyediakan form tambah dan ubah untuk Checklist dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M03-003a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M03-003b. Halaman form tambah dan ubah harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M03-004. Modul harus menyediakan panel detail untuk Temuan dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M03-004a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M03-004b. Halaman panel detail harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M03-005. Modul harus menyediakan timeline workflow untuk Laporan Audit dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M03-005a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M03-005b. Halaman timeline workflow harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M03-006. Modul harus menyediakan tab lampiran untuk Tindak Lanjut dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M03-006a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M03-006b. Halaman tab lampiran harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M03-007. Modul harus menyediakan modal approval untuk Kertas Kerja dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M03-007a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M03-007b. Halaman modal approval harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M03-008. Modul harus menyediakan filter lanjutan untuk Rekap Kepatuhan dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M03-008a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M03-008b. Halaman filter lanjutan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M03-009. Modul harus menyediakan indikator status untuk Program Audit dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M03-009a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M03-009b. Halaman indikator status harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M03-010. Modul harus menyediakan halaman konfigurasi untuk Jadwal Audit dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M03-010a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M03-010b. Halaman halaman konfigurasi harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M03-011. Modul harus menyediakan wizard import untuk Checklist dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M03-011a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M03-011b. Halaman wizard import harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M03-012. Modul harus menyediakan halaman laporan untuk Temuan dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M03-012a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M03-012b. Halaman halaman laporan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M03-999. Semua form modul Internal Audit harus menampilkan validasi inline, konfirmasi sebelum aksi irreversible, dan indikator penyimpanan saat proses berlangsung.

### API Requirement

API-M03-001. Endpoint /api/v1/audits/programs harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M03-001a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M03-001b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M03-002. Endpoint /api/v1/audits/schedules harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M03-002a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M03-002b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M03-003. Endpoint /api/v1/audits/checklists harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M03-003a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M03-003b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M03-004. Endpoint /api/v1/audits/findings harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M03-004a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M03-004b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M03-005. Endpoint /api/v1/audits/reports harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M03-005a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M03-005b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M03-006. Endpoint /api/v1/audits/follow-ups harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M03-006a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M03-006b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M03-OP-001. Operasi create harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M03-OP-002. Operasi read harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M03-OP-003. Operasi update harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M03-OP-004. Operasi submit harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M03-OP-005. Operasi approve harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M03-OP-006. Operasi reject harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M03-OP-007. Operasi publish harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M03-OP-008. Operasi archive harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M03-OP-009. Operasi search harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M03-OP-010. Operasi export harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M03-OP-011. Operasi history harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M03-OP-012. Operasi configure harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M03-SEC-001. API modul Internal Audit harus menerapkan rate limiting, CSRF/session protection untuk web, dan pembatasan akses data lintas unit.

## 4. Modul CAPA

Kode Modul: M04
Cakupan: RCA, 5 Why, Fishbone, Corrective, Preventive, Verification, Effectiveness
Aktor Utama: Admin Mutu, PIC CAPA, Reviewer CAPA, Approver, Auditor Internal, Kepala Klinik, Manajemen
Komponen: RCA, 5 Why, Fishbone, Corrective Action, Preventive Action, Verification, Effectiveness Check, CAPA Board
Entitas Data: CAPA Case, Root Cause, Five Why Item, Fishbone Category, Action Plan, Verification Record, Effectiveness Record, CAPA Attachment

Kebutuhan khusus modul:
M04-SP-01. CAPA harus membedakan koreksi langsung, tindakan korektif, dan tindakan preventif.
M04-SP-02. Penutupan CAPA hanya boleh dilakukan setelah verifikasi implementasi dan evaluasi efektivitas terpenuhi.
M04-SP-03. RCA wajib terdokumentasi sebelum action plan final disetujui.

### Objective

M04-OBJ-01. Menyediakan kemampuan rca yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M04-OBJ-01a. Memastikan proses rca memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M04-OBJ-02. Menyediakan kemampuan 5 why yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M04-OBJ-02a. Memastikan proses 5 why memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M04-OBJ-03. Menyediakan kemampuan fishbone yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M04-OBJ-03a. Memastikan proses fishbone memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M04-OBJ-04. Menyediakan kemampuan corrective action yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M04-OBJ-04a. Memastikan proses corrective action memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M04-OBJ-05. Menyediakan kemampuan preventive action yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M04-OBJ-05a. Memastikan proses preventive action memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M04-OBJ-06. Menyediakan kemampuan verification yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M04-OBJ-06a. Memastikan proses verification memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M04-OBJ-07. Menyediakan kemampuan effectiveness check yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M04-OBJ-07a. Memastikan proses effectiveness check memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M04-OBJ-08. Menyediakan kemampuan capa board yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M04-OBJ-08a. Memastikan proses capa board memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M04-OBJ-DATA-01. Mengelola entitas CAPA Case sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M04-OBJ-DATA-02. Mengelola entitas Root Cause sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M04-OBJ-DATA-03. Mengelola entitas Five Why Item sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M04-OBJ-DATA-04. Mengelola entitas Fishbone Category sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M04-OBJ-DATA-05. Mengelola entitas Action Plan sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M04-OBJ-DATA-06. Mengelola entitas Verification Record sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M04-OBJ-DATA-07. Mengelola entitas Effectiveness Record sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M04-OBJ-DATA-08. Mengelola entitas CAPA Attachment sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.

### User Story

M04-US-001. Sebagai Admin Mutu, saya ingin membuat data baru dengan validasi lengkap pada RCA, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-001-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-001-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-001-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-002. Sebagai Admin Mutu, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada 5 Why, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-002-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-002-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-002-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-003. Sebagai Admin Mutu, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Fishbone, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-003-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-003-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-003-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-004. Sebagai Admin Mutu, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Corrective Action, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-004-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-004-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-004-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-005. Sebagai Admin Mutu, saya ingin melihat riwayat perubahan dan keputusan pada Preventive Action, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-005-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-005-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-005-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-006. Sebagai Admin Mutu, saya ingin mencari data berdasarkan filter operasional pada Verification, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-006-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-006-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-006-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-007. Sebagai Admin Mutu, saya ingin mengekspor data untuk rapat manajemen pada Effectiveness Check, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-007-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-007-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-007-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-008. Sebagai Admin Mutu, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada CAPA Board, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-008-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-008-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-008-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-009. Sebagai Admin Mutu, saya ingin menghubungkan data modul ini dengan modul lain pada RCA, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-009-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-009-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-009-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-010. Sebagai Admin Mutu, saya ingin memantau status pekerjaan yang belum selesai pada 5 Why, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-010-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-010-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-010-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-011. Sebagai Admin Mutu, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Fishbone, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-011-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-011-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-011-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-012. Sebagai Admin Mutu, saya ingin membuat laporan periodik yang siap diaudit pada Corrective Action, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-012-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-012-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-012-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-013. Sebagai PIC CAPA, saya ingin membuat data baru dengan validasi lengkap pada Preventive Action, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-013-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-013-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-013-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-014. Sebagai PIC CAPA, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Verification, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-014-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-014-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-014-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-015. Sebagai PIC CAPA, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Effectiveness Check, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-015-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-015-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-015-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-016. Sebagai PIC CAPA, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada CAPA Board, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-016-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-016-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-016-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-017. Sebagai PIC CAPA, saya ingin melihat riwayat perubahan dan keputusan pada RCA, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-017-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-017-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-017-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-018. Sebagai PIC CAPA, saya ingin mencari data berdasarkan filter operasional pada 5 Why, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-018-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-018-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-018-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-019. Sebagai PIC CAPA, saya ingin mengekspor data untuk rapat manajemen pada Fishbone, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-019-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-019-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-019-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-020. Sebagai PIC CAPA, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Corrective Action, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-020-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-020-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-020-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-021. Sebagai PIC CAPA, saya ingin menghubungkan data modul ini dengan modul lain pada Preventive Action, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-021-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-021-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-021-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-022. Sebagai PIC CAPA, saya ingin memantau status pekerjaan yang belum selesai pada Verification, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-022-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-022-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-022-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-023. Sebagai PIC CAPA, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Effectiveness Check, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-023-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-023-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-023-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-024. Sebagai PIC CAPA, saya ingin membuat laporan periodik yang siap diaudit pada CAPA Board, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-024-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-024-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-024-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-025. Sebagai Reviewer CAPA, saya ingin membuat data baru dengan validasi lengkap pada RCA, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-025-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-025-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-025-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-026. Sebagai Reviewer CAPA, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada 5 Why, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-026-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-026-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-026-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-027. Sebagai Reviewer CAPA, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Fishbone, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-027-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-027-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-027-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-028. Sebagai Reviewer CAPA, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Corrective Action, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-028-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-028-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-028-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-029. Sebagai Reviewer CAPA, saya ingin melihat riwayat perubahan dan keputusan pada Preventive Action, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-029-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-029-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-029-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-030. Sebagai Reviewer CAPA, saya ingin mencari data berdasarkan filter operasional pada Verification, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-030-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-030-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-030-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-031. Sebagai Reviewer CAPA, saya ingin mengekspor data untuk rapat manajemen pada Effectiveness Check, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-031-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-031-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-031-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-032. Sebagai Reviewer CAPA, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada CAPA Board, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-032-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-032-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-032-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-033. Sebagai Reviewer CAPA, saya ingin menghubungkan data modul ini dengan modul lain pada RCA, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-033-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-033-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-033-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-034. Sebagai Reviewer CAPA, saya ingin memantau status pekerjaan yang belum selesai pada 5 Why, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-034-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-034-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-034-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-035. Sebagai Reviewer CAPA, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Fishbone, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-035-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-035-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-035-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-036. Sebagai Reviewer CAPA, saya ingin membuat laporan periodik yang siap diaudit pada Corrective Action, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-036-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-036-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-036-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-037. Sebagai Approver, saya ingin membuat data baru dengan validasi lengkap pada Preventive Action, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-037-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-037-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-037-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-038. Sebagai Approver, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Verification, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-038-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-038-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-038-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-039. Sebagai Approver, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Effectiveness Check, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-039-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-039-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-039-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-040. Sebagai Approver, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada CAPA Board, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-040-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-040-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-040-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-041. Sebagai Approver, saya ingin melihat riwayat perubahan dan keputusan pada RCA, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-041-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-041-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-041-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M04-US-042. Sebagai Approver, saya ingin mencari data berdasarkan filter operasional pada 5 Why, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M04-US-042-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M04-US-042-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M04-US-042-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.

### Use Case

M04-UC-01. Membuat Dan Menyimpan Entri Baru untuk RCA
  Aktor Utama: Admin Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu RCA dan menjalankan aksi membuat dan menyimpan entri baru.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi CAPA Case tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja RCA sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data CAPA Case tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M04-UC-02. Mengirim Entri Ke Workflow Persetujuan untuk 5 Why
  Aktor Utama: PIC CAPA.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu 5 Why dan menjalankan aksi mengirim entri ke workflow persetujuan.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Root Cause tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja 5 Why sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Root Cause tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M04-UC-03. Melakukan Review Substansi untuk Fishbone
  Aktor Utama: Reviewer CAPA.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Fishbone dan menjalankan aksi melakukan review substansi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Five Why Item tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Fishbone sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Five Why Item tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M04-UC-04. Memberikan Approval Final untuk Corrective Action
  Aktor Utama: Approver.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Corrective Action dan menjalankan aksi memberikan approval final.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Fishbone Category tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Corrective Action sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Fishbone Category tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M04-UC-05. Mengembalikan Entri Untuk Revisi untuk Preventive Action
  Aktor Utama: Auditor Internal.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Preventive Action dan menjalankan aksi mengembalikan entri untuk revisi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Action Plan tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Preventive Action sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Action Plan tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M04-UC-06. Mempublikasikan Hasil Yang Telah Sah untuk Verification
  Aktor Utama: Kepala Klinik.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Verification dan menjalankan aksi mempublikasikan hasil yang telah sah.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Verification Record tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Verification sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Verification Record tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M04-UC-07. Menautkan Evidence Atau Lampiran untuk Effectiveness Check
  Aktor Utama: Manajemen.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Effectiveness Check dan menjalankan aksi menautkan evidence atau lampiran.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Effectiveness Record tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Effectiveness Check sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Effectiveness Record tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M04-UC-08. Melakukan Monitoring Tenggat untuk CAPA Board
  Aktor Utama: Admin Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu CAPA Board dan menjalankan aksi melakukan monitoring tenggat.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi CAPA Attachment tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja CAPA Board sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data CAPA Attachment tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M04-UC-09. Menutup Pekerjaan Setelah Seluruh Syarat Terpenuhi untuk RCA
  Aktor Utama: PIC CAPA.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu RCA dan menjalankan aksi menutup pekerjaan setelah seluruh syarat terpenuhi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi CAPA Case tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja RCA sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data CAPA Case tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M04-UC-10. Mengekspor Laporan Resmi untuk 5 Why
  Aktor Utama: Reviewer CAPA.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu 5 Why dan menjalankan aksi mengekspor laporan resmi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Root Cause tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja 5 Why sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Root Cause tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M04-UC-11. Mengubah Konfigurasi Referensi untuk Fishbone
  Aktor Utama: Approver.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Fishbone dan menjalankan aksi mengubah konfigurasi referensi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Five Why Item tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Fishbone sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Five Why Item tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M04-UC-12. Menelusuri Audit Trail untuk Corrective Action
  Aktor Utama: Auditor Internal.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Corrective Action dan menjalankan aksi menelusuri audit trail.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Fishbone Category tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Corrective Action sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Fishbone Category tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.

### Functional Requirement

FR-M04-001. Sistem harus menyediakan menu RCA dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M04-002. Sistem harus menghubungkan RCA dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M04-003. Sistem harus mencatat audit trail untuk setiap perubahan data RCA, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M04-004. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk RCA bila relevan dengan proses bisnis.
FR-M04-005. Sistem harus menyediakan menu 5 Why dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M04-006. Sistem harus menghubungkan 5 Why dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M04-007. Sistem harus mencatat audit trail untuk setiap perubahan data 5 Why, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M04-008. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk 5 Why bila relevan dengan proses bisnis.
FR-M04-009. Sistem harus menyediakan menu Fishbone dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M04-010. Sistem harus menghubungkan Fishbone dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M04-011. Sistem harus mencatat audit trail untuk setiap perubahan data Fishbone, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M04-012. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Fishbone bila relevan dengan proses bisnis.
FR-M04-013. Sistem harus menyediakan menu Corrective Action dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M04-014. Sistem harus menghubungkan Corrective Action dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M04-015. Sistem harus mencatat audit trail untuk setiap perubahan data Corrective Action, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M04-016. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Corrective Action bila relevan dengan proses bisnis.
FR-M04-017. Sistem harus menyediakan menu Preventive Action dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M04-018. Sistem harus menghubungkan Preventive Action dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M04-019. Sistem harus mencatat audit trail untuk setiap perubahan data Preventive Action, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M04-020. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Preventive Action bila relevan dengan proses bisnis.
FR-M04-021. Sistem harus menyediakan menu Verification dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M04-022. Sistem harus menghubungkan Verification dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M04-023. Sistem harus mencatat audit trail untuk setiap perubahan data Verification, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M04-024. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Verification bila relevan dengan proses bisnis.
FR-M04-025. Sistem harus menyediakan menu Effectiveness Check dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M04-026. Sistem harus menghubungkan Effectiveness Check dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M04-027. Sistem harus mencatat audit trail untuk setiap perubahan data Effectiveness Check, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M04-028. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Effectiveness Check bila relevan dengan proses bisnis.
FR-M04-029. Sistem harus menyediakan menu CAPA Board dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M04-030. Sistem harus menghubungkan CAPA Board dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M04-031. Sistem harus mencatat audit trail untuk setiap perubahan data CAPA Board, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M04-032. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk CAPA Board bila relevan dengan proses bisnis.
FR-M04-033. Sistem harus memvalidasi entitas CAPA Case menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M04-034. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas CAPA Case dengan pagination standar.
FR-M04-035. Sistem harus memvalidasi entitas Root Cause menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M04-036. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Root Cause dengan pagination standar.
FR-M04-037. Sistem harus memvalidasi entitas Five Why Item menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M04-038. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Five Why Item dengan pagination standar.
FR-M04-039. Sistem harus memvalidasi entitas Fishbone Category menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M04-040. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Fishbone Category dengan pagination standar.
FR-M04-041. Sistem harus memvalidasi entitas Action Plan menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M04-042. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Action Plan dengan pagination standar.
FR-M04-043. Sistem harus memvalidasi entitas Verification Record menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M04-044. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Verification Record dengan pagination standar.
FR-M04-045. Sistem harus memvalidasi entitas Effectiveness Record menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M04-046. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Effectiveness Record dengan pagination standar.
FR-M04-047. Sistem harus memvalidasi entitas CAPA Attachment menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M04-048. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas CAPA Attachment dengan pagination standar.
FR-M04-049. Sistem harus memenuhi ketentuan khusus: CAPA harus membedakan koreksi langsung, tindakan korektif, dan tindakan preventif.
FR-M04-050. Sistem harus memenuhi ketentuan khusus: Penutupan CAPA hanya boleh dilakukan setelah verifikasi implementasi dan evaluasi efektivitas terpenuhi.
FR-M04-051. Sistem harus memenuhi ketentuan khusus: RCA wajib terdokumentasi sebelum action plan final disetujui.
FR-M04-052. Sistem harus menyediakan kontrol operasional tambahan untuk Corrective Action, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M04-053. Sistem harus menyediakan kontrol operasional tambahan untuk Preventive Action, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M04-054. Sistem harus menyediakan kontrol operasional tambahan untuk Verification, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M04-055. Sistem harus menyediakan kontrol operasional tambahan untuk Effectiveness Check, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M04-056. Sistem harus menyediakan kontrol operasional tambahan untuk CAPA Board, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M04-057. Sistem harus menyediakan kontrol operasional tambahan untuk RCA, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M04-058. Sistem harus menyediakan kontrol operasional tambahan untuk 5 Why, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.

### Non Functional Requirement

NFR-M04-001. Keamanan: sistem harus memastikan akses berbasis role, permission, unit, dan status workflow diterapkan konsisten pada seluruh fitur modul CAPA.
NFR-M04-002. Keamanan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M04-003. Kinerja: sistem harus memastikan halaman daftar utama merespons cepat untuk data operasional besar pada seluruh fitur modul CAPA.
NFR-M04-004. Kinerja: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M04-005. Reliabilitas: sistem harus memastikan transaksi penting menggunakan database transaction dan mekanisme retry terukur pada seluruh fitur modul CAPA.
NFR-M04-006. Reliabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M04-007. Auditabilitas: sistem harus memastikan setiap perubahan bernilai kepatuhan memiliki jejak waktu, aktor, nilai lama, dan nilai baru pada seluruh fitur modul CAPA.
NFR-M04-008. Auditabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M04-009. Ketersediaan: sistem harus memastikan fitur kritikal tetap stabil saat Redis digunakan untuk queue, cache, dan session pada seluruh fitur modul CAPA.
NFR-M04-010. Ketersediaan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M04-011. Skalabilitas: sistem harus memastikan desain tabel dan indeks mendukung pertumbuhan multi tahun pada seluruh fitur modul CAPA.
NFR-M04-012. Skalabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M04-013. Usability: sistem harus memastikan alur kerja utama dapat diselesaikan oleh pengguna non teknis dengan instruksi jelas pada seluruh fitur modul CAPA.
NFR-M04-014. Usability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M04-015. Interoperabilitas: sistem harus memastikan API menggunakan JSON, pagination, filter, dan kode error konsisten pada seluruh fitur modul CAPA.
NFR-M04-016. Interoperabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M04-017. Privasi: sistem harus memastikan data sensitif hanya tampil sesuai kebutuhan tugas dan kewenangan pada seluruh fitur modul CAPA.
NFR-M04-018. Privasi: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M04-019. Maintainability: sistem harus memastikan konfigurasi bisnis disimpan sebagai data, bukan konstanta tersembunyi di kode pada seluruh fitur modul CAPA.
NFR-M04-020. Maintainability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M04-021. Database: query utama modul harus memiliki index yang sesuai untuk filter status, periode, unit, pemilik, dan workflow.
NFR-M04-022. Queue: proses notifikasi, ekspor, reminder, dan kalkulasi agregat harus berjalan asynchronous menggunakan Redis queue.
NFR-M04-023. Observability: error, retry, durasi proses, dan kegagalan integrasi harus tercatat dalam log terstruktur.

### Acceptance Criteria

AC-M04-001. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses RCA, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-001a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-002. Diberikan pengguna PIC CAPA memiliki permission yang benar, ketika pengguna menjalankan proses 5 Why, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-002a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-003. Diberikan pengguna Reviewer CAPA memiliki permission yang benar, ketika pengguna menjalankan proses Fishbone, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-003a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-004. Diberikan pengguna Approver memiliki permission yang benar, ketika pengguna menjalankan proses Corrective Action, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-004a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-004b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M04-005. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Preventive Action, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-005a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-006. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Verification, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-006a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-007. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Effectiveness Check, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-007a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-008. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses CAPA Board, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-008a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-008b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M04-009. Diberikan pengguna PIC CAPA memiliki permission yang benar, ketika pengguna menjalankan proses RCA, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-009a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-010. Diberikan pengguna Reviewer CAPA memiliki permission yang benar, ketika pengguna menjalankan proses 5 Why, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-010a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-011. Diberikan pengguna Approver memiliki permission yang benar, ketika pengguna menjalankan proses Fishbone, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-011a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-012. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Corrective Action, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-012a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-012b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M04-013. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Preventive Action, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-013a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-014. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Verification, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-014a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-015. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Effectiveness Check, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-015a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-016. Diberikan pengguna PIC CAPA memiliki permission yang benar, ketika pengguna menjalankan proses CAPA Board, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-016a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-016b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M04-017. Diberikan pengguna Reviewer CAPA memiliki permission yang benar, ketika pengguna menjalankan proses RCA, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-017a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-018. Diberikan pengguna Approver memiliki permission yang benar, ketika pengguna menjalankan proses 5 Why, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-018a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-019. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Fishbone, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-019a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-020. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Corrective Action, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-020a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-020b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M04-021. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Preventive Action, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-021a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-022. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Verification, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-022a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-023. Diberikan pengguna PIC CAPA memiliki permission yang benar, ketika pengguna menjalankan proses Effectiveness Check, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-023a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-024. Diberikan pengguna Reviewer CAPA memiliki permission yang benar, ketika pengguna menjalankan proses CAPA Board, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-024a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-024b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M04-025. Diberikan pengguna Approver memiliki permission yang benar, ketika pengguna menjalankan proses RCA, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-025a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-026. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses 5 Why, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-026a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-027. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Fishbone, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-027a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-028. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Corrective Action, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-028a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-028b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M04-029. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Preventive Action, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-029a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-030. Diberikan pengguna PIC CAPA memiliki permission yang benar, ketika pengguna menjalankan proses Verification, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-030a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-031. Diberikan pengguna Reviewer CAPA memiliki permission yang benar, ketika pengguna menjalankan proses Effectiveness Check, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-031a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-032. Diberikan pengguna Approver memiliki permission yang benar, ketika pengguna menjalankan proses CAPA Board, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M04-032a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M04-032b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.

### Business Rules

BR-M04-001. Data CAPA Case pada RCA hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-002. Data Root Cause pada 5 Why hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-003. Data Five Why Item pada Fishbone hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-003a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M04-004. Data Fishbone Category pada Corrective Action hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-005. Data Action Plan pada Preventive Action hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-005b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M04-006. Data Verification Record pada Verification hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-006a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M04-007. Data Effectiveness Record pada Effectiveness Check hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-008. Data CAPA Attachment pada CAPA Board hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-009. Data CAPA Case pada RCA hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-009a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M04-010. Data Root Cause pada 5 Why hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-010b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M04-011. Data Five Why Item pada Fishbone hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-012. Data Fishbone Category pada Corrective Action hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-012a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M04-013. Data Action Plan pada Preventive Action hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-014. Data Verification Record pada Verification hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-015. Data Effectiveness Record pada Effectiveness Check hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-015a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M04-015b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M04-016. Data CAPA Attachment pada CAPA Board hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-017. Data CAPA Case pada RCA hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-018. Data Root Cause pada 5 Why hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-018a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M04-019. Data Five Why Item pada Fishbone hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-020. Data Fishbone Category pada Corrective Action hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-020b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M04-021. Data Action Plan pada Preventive Action hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-021a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M04-022. Data Verification Record pada Verification hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-023. Data Effectiveness Record pada Effectiveness Check hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-024. Data CAPA Attachment pada CAPA Board hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-024a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M04-025. Data CAPA Case pada RCA hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-025b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M04-026. Data Root Cause pada 5 Why hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-027. Data Five Why Item pada Fishbone hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-027a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M04-028. Data Fishbone Category pada Corrective Action hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-029. Data Action Plan pada Preventive Action hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-030. Data Verification Record pada Verification hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-030a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M04-030b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M04-031. Data Effectiveness Record pada Effectiveness Check hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-032. Data CAPA Attachment pada CAPA Board hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-033. Data CAPA Case pada RCA hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-033a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M04-034. Data Root Cause pada 5 Why hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-035. Data Five Why Item pada Fishbone hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-035b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M04-036. Data Fishbone Category pada Corrective Action hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M04-036a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M04-KHS-01. CAPA harus membedakan koreksi langsung, tindakan korektif, dan tindakan preventif.
BR-M04-KHS-02. Penutupan CAPA hanya boleh dilakukan setelah verifikasi implementasi dan evaluasi efektivitas terpenuhi.
BR-M04-KHS-03. RCA wajib terdokumentasi sebelum action plan final disetujui.

### UI Requirement

UI-M04-001. Modul harus menyediakan dashboard ringkasan untuk RCA dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M04-001a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M04-001b. Halaman dashboard ringkasan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M04-002. Modul harus menyediakan halaman daftar untuk 5 Why dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M04-002a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M04-002b. Halaman halaman daftar harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M04-003. Modul harus menyediakan form tambah dan ubah untuk Fishbone dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M04-003a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M04-003b. Halaman form tambah dan ubah harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M04-004. Modul harus menyediakan panel detail untuk Corrective Action dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M04-004a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M04-004b. Halaman panel detail harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M04-005. Modul harus menyediakan timeline workflow untuk Preventive Action dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M04-005a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M04-005b. Halaman timeline workflow harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M04-006. Modul harus menyediakan tab lampiran untuk Verification dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M04-006a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M04-006b. Halaman tab lampiran harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M04-007. Modul harus menyediakan modal approval untuk Effectiveness Check dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M04-007a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M04-007b. Halaman modal approval harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M04-008. Modul harus menyediakan filter lanjutan untuk CAPA Board dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M04-008a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M04-008b. Halaman filter lanjutan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M04-009. Modul harus menyediakan indikator status untuk RCA dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M04-009a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M04-009b. Halaman indikator status harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M04-010. Modul harus menyediakan halaman konfigurasi untuk 5 Why dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M04-010a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M04-010b. Halaman halaman konfigurasi harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M04-011. Modul harus menyediakan wizard import untuk Fishbone dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M04-011a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M04-011b. Halaman wizard import harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M04-012. Modul harus menyediakan halaman laporan untuk Corrective Action dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M04-012a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M04-012b. Halaman halaman laporan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M04-999. Semua form modul CAPA harus menampilkan validasi inline, konfirmasi sebelum aksi irreversible, dan indikator penyimpanan saat proses berlangsung.

### API Requirement

API-M04-001. Endpoint /api/v1/capa/cases harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M04-001a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M04-001b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M04-002. Endpoint /api/v1/capa/{id}/rca harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M04-002a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M04-002b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M04-003. Endpoint /api/v1/capa/{id}/five-why harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M04-003a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M04-003b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M04-004. Endpoint /api/v1/capa/{id}/fishbone harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M04-004a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M04-004b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M04-005. Endpoint /api/v1/capa/{id}/actions harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M04-005a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M04-005b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M04-006. Endpoint /api/v1/capa/{id}/verification harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M04-006a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M04-006b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M04-007. Endpoint /api/v1/capa/{id}/effectiveness harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M04-007a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M04-007b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M04-OP-001. Operasi create harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M04-OP-002. Operasi read harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M04-OP-003. Operasi update harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M04-OP-004. Operasi submit harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M04-OP-005. Operasi approve harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M04-OP-006. Operasi reject harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M04-OP-007. Operasi publish harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M04-OP-008. Operasi archive harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M04-OP-009. Operasi search harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M04-OP-010. Operasi export harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M04-OP-011. Operasi history harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M04-OP-012. Operasi configure harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M04-SEC-001. API modul CAPA harus menerapkan rate limiting, CSRF/session protection untuk web, dan pembatasan akses data lintas unit.

## 5. Modul Risk Management

Kode Modul: M05
Cakupan: Register, Assessment, Matrix, Monitoring, Mitigation
Aktor Utama: Risk Owner, Admin Mutu, PIC Unit, Kepala Klinik, Komite Mutu, Manajemen, Auditor Internal
Komponen: Risk Register, Risk Assessment, Risk Matrix, Monitoring, Mitigation Plan, Residual Risk, Risk Review, Risk Dashboard
Entitas Data: Risk, Risk Category, Risk Assessment, Risk Matrix, Mitigation, Monitoring Log, Residual Assessment, Risk Appetite

Kebutuhan khusus modul:
M05-SP-01. Skala likelihood, impact, dan matrix harus dapat dikonfigurasi oleh admin tanpa hardcode.
M05-SP-02. Risiko residual harus dihitung setelah mitigasi dan disetujui risk owner.
M05-SP-03. Risiko tinggi wajib memiliki rencana mitigasi, pemilik, tenggat, dan monitoring aktif.

### Objective

M05-OBJ-01. Menyediakan kemampuan risk register yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M05-OBJ-01a. Memastikan proses risk register memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M05-OBJ-02. Menyediakan kemampuan risk assessment yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M05-OBJ-02a. Memastikan proses risk assessment memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M05-OBJ-03. Menyediakan kemampuan risk matrix yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M05-OBJ-03a. Memastikan proses risk matrix memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M05-OBJ-04. Menyediakan kemampuan monitoring yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M05-OBJ-04a. Memastikan proses monitoring memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M05-OBJ-05. Menyediakan kemampuan mitigation plan yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M05-OBJ-05a. Memastikan proses mitigation plan memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M05-OBJ-06. Menyediakan kemampuan residual risk yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M05-OBJ-06a. Memastikan proses residual risk memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M05-OBJ-07. Menyediakan kemampuan risk review yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M05-OBJ-07a. Memastikan proses risk review memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M05-OBJ-08. Menyediakan kemampuan risk dashboard yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M05-OBJ-08a. Memastikan proses risk dashboard memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M05-OBJ-DATA-01. Mengelola entitas Risk sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M05-OBJ-DATA-02. Mengelola entitas Risk Category sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M05-OBJ-DATA-03. Mengelola entitas Risk Assessment sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M05-OBJ-DATA-04. Mengelola entitas Risk Matrix sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M05-OBJ-DATA-05. Mengelola entitas Mitigation sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M05-OBJ-DATA-06. Mengelola entitas Monitoring Log sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M05-OBJ-DATA-07. Mengelola entitas Residual Assessment sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M05-OBJ-DATA-08. Mengelola entitas Risk Appetite sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.

### User Story

M05-US-001. Sebagai Risk Owner, saya ingin membuat data baru dengan validasi lengkap pada Risk Register, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-001-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-001-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-001-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-002. Sebagai Risk Owner, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Risk Assessment, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-002-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-002-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-002-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-003. Sebagai Risk Owner, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Risk Matrix, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-003-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-003-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-003-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-004. Sebagai Risk Owner, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-004-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-004-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-004-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-005. Sebagai Risk Owner, saya ingin melihat riwayat perubahan dan keputusan pada Mitigation Plan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-005-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-005-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-005-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-006. Sebagai Risk Owner, saya ingin mencari data berdasarkan filter operasional pada Residual Risk, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-006-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-006-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-006-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-007. Sebagai Risk Owner, saya ingin mengekspor data untuk rapat manajemen pada Risk Review, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-007-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-007-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-007-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-008. Sebagai Risk Owner, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Risk Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-008-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-008-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-008-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-009. Sebagai Risk Owner, saya ingin menghubungkan data modul ini dengan modul lain pada Risk Register, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-009-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-009-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-009-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-010. Sebagai Risk Owner, saya ingin memantau status pekerjaan yang belum selesai pada Risk Assessment, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-010-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-010-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-010-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-011. Sebagai Risk Owner, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Risk Matrix, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-011-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-011-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-011-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-012. Sebagai Risk Owner, saya ingin membuat laporan periodik yang siap diaudit pada Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-012-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-012-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-012-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-013. Sebagai Admin Mutu, saya ingin membuat data baru dengan validasi lengkap pada Mitigation Plan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-013-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-013-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-013-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-014. Sebagai Admin Mutu, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Residual Risk, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-014-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-014-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-014-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-015. Sebagai Admin Mutu, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Risk Review, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-015-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-015-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-015-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-016. Sebagai Admin Mutu, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Risk Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-016-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-016-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-016-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-017. Sebagai Admin Mutu, saya ingin melihat riwayat perubahan dan keputusan pada Risk Register, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-017-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-017-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-017-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-018. Sebagai Admin Mutu, saya ingin mencari data berdasarkan filter operasional pada Risk Assessment, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-018-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-018-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-018-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-019. Sebagai Admin Mutu, saya ingin mengekspor data untuk rapat manajemen pada Risk Matrix, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-019-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-019-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-019-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-020. Sebagai Admin Mutu, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-020-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-020-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-020-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-021. Sebagai Admin Mutu, saya ingin menghubungkan data modul ini dengan modul lain pada Mitigation Plan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-021-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-021-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-021-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-022. Sebagai Admin Mutu, saya ingin memantau status pekerjaan yang belum selesai pada Residual Risk, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-022-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-022-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-022-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-023. Sebagai Admin Mutu, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Risk Review, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-023-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-023-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-023-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-024. Sebagai Admin Mutu, saya ingin membuat laporan periodik yang siap diaudit pada Risk Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-024-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-024-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-024-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-025. Sebagai PIC Unit, saya ingin membuat data baru dengan validasi lengkap pada Risk Register, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-025-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-025-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-025-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-026. Sebagai PIC Unit, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Risk Assessment, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-026-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-026-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-026-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-027. Sebagai PIC Unit, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Risk Matrix, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-027-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-027-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-027-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-028. Sebagai PIC Unit, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-028-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-028-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-028-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-029. Sebagai PIC Unit, saya ingin melihat riwayat perubahan dan keputusan pada Mitigation Plan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-029-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-029-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-029-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-030. Sebagai PIC Unit, saya ingin mencari data berdasarkan filter operasional pada Residual Risk, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-030-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-030-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-030-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-031. Sebagai PIC Unit, saya ingin mengekspor data untuk rapat manajemen pada Risk Review, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-031-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-031-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-031-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-032. Sebagai PIC Unit, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Risk Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-032-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-032-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-032-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-033. Sebagai PIC Unit, saya ingin menghubungkan data modul ini dengan modul lain pada Risk Register, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-033-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-033-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-033-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-034. Sebagai PIC Unit, saya ingin memantau status pekerjaan yang belum selesai pada Risk Assessment, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-034-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-034-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-034-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-035. Sebagai PIC Unit, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Risk Matrix, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-035-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-035-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-035-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-036. Sebagai PIC Unit, saya ingin membuat laporan periodik yang siap diaudit pada Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-036-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-036-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-036-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-037. Sebagai Kepala Klinik, saya ingin membuat data baru dengan validasi lengkap pada Mitigation Plan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-037-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-037-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-037-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-038. Sebagai Kepala Klinik, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Residual Risk, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-038-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-038-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-038-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-039. Sebagai Kepala Klinik, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Risk Review, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-039-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-039-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-039-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-040. Sebagai Kepala Klinik, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Risk Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-040-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-040-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-040-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-041. Sebagai Kepala Klinik, saya ingin melihat riwayat perubahan dan keputusan pada Risk Register, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-041-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-041-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-041-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M05-US-042. Sebagai Kepala Klinik, saya ingin mencari data berdasarkan filter operasional pada Risk Assessment, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M05-US-042-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M05-US-042-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M05-US-042-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.

### Use Case

M05-UC-01. Membuat Dan Menyimpan Entri Baru untuk Risk Register
  Aktor Utama: Risk Owner.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Risk Register dan menjalankan aksi membuat dan menyimpan entri baru.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Risk tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Risk Register sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Risk tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M05-UC-02. Mengirim Entri Ke Workflow Persetujuan untuk Risk Assessment
  Aktor Utama: Admin Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Risk Assessment dan menjalankan aksi mengirim entri ke workflow persetujuan.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Risk Category tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Risk Assessment sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Risk Category tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M05-UC-03. Melakukan Review Substansi untuk Risk Matrix
  Aktor Utama: PIC Unit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Risk Matrix dan menjalankan aksi melakukan review substansi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Risk Assessment tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Risk Matrix sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Risk Assessment tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M05-UC-04. Memberikan Approval Final untuk Monitoring
  Aktor Utama: Kepala Klinik.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Monitoring dan menjalankan aksi memberikan approval final.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Risk Matrix tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Monitoring sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Risk Matrix tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M05-UC-05. Mengembalikan Entri Untuk Revisi untuk Mitigation Plan
  Aktor Utama: Komite Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Mitigation Plan dan menjalankan aksi mengembalikan entri untuk revisi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Mitigation tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Mitigation Plan sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Mitigation tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M05-UC-06. Mempublikasikan Hasil Yang Telah Sah untuk Residual Risk
  Aktor Utama: Manajemen.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Residual Risk dan menjalankan aksi mempublikasikan hasil yang telah sah.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Monitoring Log tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Residual Risk sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Monitoring Log tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M05-UC-07. Menautkan Evidence Atau Lampiran untuk Risk Review
  Aktor Utama: Auditor Internal.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Risk Review dan menjalankan aksi menautkan evidence atau lampiran.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Residual Assessment tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Risk Review sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Residual Assessment tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M05-UC-08. Melakukan Monitoring Tenggat untuk Risk Dashboard
  Aktor Utama: Risk Owner.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Risk Dashboard dan menjalankan aksi melakukan monitoring tenggat.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Risk Appetite tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Risk Dashboard sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Risk Appetite tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M05-UC-09. Menutup Pekerjaan Setelah Seluruh Syarat Terpenuhi untuk Risk Register
  Aktor Utama: Admin Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Risk Register dan menjalankan aksi menutup pekerjaan setelah seluruh syarat terpenuhi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Risk tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Risk Register sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Risk tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M05-UC-10. Mengekspor Laporan Resmi untuk Risk Assessment
  Aktor Utama: PIC Unit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Risk Assessment dan menjalankan aksi mengekspor laporan resmi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Risk Category tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Risk Assessment sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Risk Category tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M05-UC-11. Mengubah Konfigurasi Referensi untuk Risk Matrix
  Aktor Utama: Kepala Klinik.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Risk Matrix dan menjalankan aksi mengubah konfigurasi referensi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Risk Assessment tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Risk Matrix sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Risk Assessment tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M05-UC-12. Menelusuri Audit Trail untuk Monitoring
  Aktor Utama: Komite Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Monitoring dan menjalankan aksi menelusuri audit trail.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Risk Matrix tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Monitoring sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Risk Matrix tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.

### Functional Requirement

FR-M05-001. Sistem harus menyediakan menu Risk Register dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M05-002. Sistem harus menghubungkan Risk Register dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M05-003. Sistem harus mencatat audit trail untuk setiap perubahan data Risk Register, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M05-004. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Risk Register bila relevan dengan proses bisnis.
FR-M05-005. Sistem harus menyediakan menu Risk Assessment dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M05-006. Sistem harus menghubungkan Risk Assessment dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M05-007. Sistem harus mencatat audit trail untuk setiap perubahan data Risk Assessment, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M05-008. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Risk Assessment bila relevan dengan proses bisnis.
FR-M05-009. Sistem harus menyediakan menu Risk Matrix dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M05-010. Sistem harus menghubungkan Risk Matrix dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M05-011. Sistem harus mencatat audit trail untuk setiap perubahan data Risk Matrix, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M05-012. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Risk Matrix bila relevan dengan proses bisnis.
FR-M05-013. Sistem harus menyediakan menu Monitoring dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M05-014. Sistem harus menghubungkan Monitoring dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M05-015. Sistem harus mencatat audit trail untuk setiap perubahan data Monitoring, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M05-016. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Monitoring bila relevan dengan proses bisnis.
FR-M05-017. Sistem harus menyediakan menu Mitigation Plan dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M05-018. Sistem harus menghubungkan Mitigation Plan dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M05-019. Sistem harus mencatat audit trail untuk setiap perubahan data Mitigation Plan, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M05-020. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Mitigation Plan bila relevan dengan proses bisnis.
FR-M05-021. Sistem harus menyediakan menu Residual Risk dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M05-022. Sistem harus menghubungkan Residual Risk dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M05-023. Sistem harus mencatat audit trail untuk setiap perubahan data Residual Risk, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M05-024. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Residual Risk bila relevan dengan proses bisnis.
FR-M05-025. Sistem harus menyediakan menu Risk Review dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M05-026. Sistem harus menghubungkan Risk Review dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M05-027. Sistem harus mencatat audit trail untuk setiap perubahan data Risk Review, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M05-028. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Risk Review bila relevan dengan proses bisnis.
FR-M05-029. Sistem harus menyediakan menu Risk Dashboard dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M05-030. Sistem harus menghubungkan Risk Dashboard dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M05-031. Sistem harus mencatat audit trail untuk setiap perubahan data Risk Dashboard, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M05-032. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Risk Dashboard bila relevan dengan proses bisnis.
FR-M05-033. Sistem harus memvalidasi entitas Risk menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M05-034. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Risk dengan pagination standar.
FR-M05-035. Sistem harus memvalidasi entitas Risk Category menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M05-036. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Risk Category dengan pagination standar.
FR-M05-037. Sistem harus memvalidasi entitas Risk Assessment menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M05-038. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Risk Assessment dengan pagination standar.
FR-M05-039. Sistem harus memvalidasi entitas Risk Matrix menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M05-040. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Risk Matrix dengan pagination standar.
FR-M05-041. Sistem harus memvalidasi entitas Mitigation menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M05-042. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Mitigation dengan pagination standar.
FR-M05-043. Sistem harus memvalidasi entitas Monitoring Log menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M05-044. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Monitoring Log dengan pagination standar.
FR-M05-045. Sistem harus memvalidasi entitas Residual Assessment menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M05-046. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Residual Assessment dengan pagination standar.
FR-M05-047. Sistem harus memvalidasi entitas Risk Appetite menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M05-048. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Risk Appetite dengan pagination standar.
FR-M05-049. Sistem harus memenuhi ketentuan khusus: Skala likelihood, impact, dan matrix harus dapat dikonfigurasi oleh admin tanpa hardcode.
FR-M05-050. Sistem harus memenuhi ketentuan khusus: Risiko residual harus dihitung setelah mitigasi dan disetujui risk owner.
FR-M05-051. Sistem harus memenuhi ketentuan khusus: Risiko tinggi wajib memiliki rencana mitigasi, pemilik, tenggat, dan monitoring aktif.
FR-M05-052. Sistem harus menyediakan kontrol operasional tambahan untuk Monitoring, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M05-053. Sistem harus menyediakan kontrol operasional tambahan untuk Mitigation Plan, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M05-054. Sistem harus menyediakan kontrol operasional tambahan untuk Residual Risk, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M05-055. Sistem harus menyediakan kontrol operasional tambahan untuk Risk Review, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M05-056. Sistem harus menyediakan kontrol operasional tambahan untuk Risk Dashboard, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M05-057. Sistem harus menyediakan kontrol operasional tambahan untuk Risk Register, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M05-058. Sistem harus menyediakan kontrol operasional tambahan untuk Risk Assessment, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.

### Non Functional Requirement

NFR-M05-001. Keamanan: sistem harus memastikan akses berbasis role, permission, unit, dan status workflow diterapkan konsisten pada seluruh fitur modul Risk Management.
NFR-M05-002. Keamanan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M05-003. Kinerja: sistem harus memastikan halaman daftar utama merespons cepat untuk data operasional besar pada seluruh fitur modul Risk Management.
NFR-M05-004. Kinerja: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M05-005. Reliabilitas: sistem harus memastikan transaksi penting menggunakan database transaction dan mekanisme retry terukur pada seluruh fitur modul Risk Management.
NFR-M05-006. Reliabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M05-007. Auditabilitas: sistem harus memastikan setiap perubahan bernilai kepatuhan memiliki jejak waktu, aktor, nilai lama, dan nilai baru pada seluruh fitur modul Risk Management.
NFR-M05-008. Auditabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M05-009. Ketersediaan: sistem harus memastikan fitur kritikal tetap stabil saat Redis digunakan untuk queue, cache, dan session pada seluruh fitur modul Risk Management.
NFR-M05-010. Ketersediaan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M05-011. Skalabilitas: sistem harus memastikan desain tabel dan indeks mendukung pertumbuhan multi tahun pada seluruh fitur modul Risk Management.
NFR-M05-012. Skalabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M05-013. Usability: sistem harus memastikan alur kerja utama dapat diselesaikan oleh pengguna non teknis dengan instruksi jelas pada seluruh fitur modul Risk Management.
NFR-M05-014. Usability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M05-015. Interoperabilitas: sistem harus memastikan API menggunakan JSON, pagination, filter, dan kode error konsisten pada seluruh fitur modul Risk Management.
NFR-M05-016. Interoperabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M05-017. Privasi: sistem harus memastikan data sensitif hanya tampil sesuai kebutuhan tugas dan kewenangan pada seluruh fitur modul Risk Management.
NFR-M05-018. Privasi: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M05-019. Maintainability: sistem harus memastikan konfigurasi bisnis disimpan sebagai data, bukan konstanta tersembunyi di kode pada seluruh fitur modul Risk Management.
NFR-M05-020. Maintainability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M05-021. Database: query utama modul harus memiliki index yang sesuai untuk filter status, periode, unit, pemilik, dan workflow.
NFR-M05-022. Queue: proses notifikasi, ekspor, reminder, dan kalkulasi agregat harus berjalan asynchronous menggunakan Redis queue.
NFR-M05-023. Observability: error, retry, durasi proses, dan kegagalan integrasi harus tercatat dalam log terstruktur.

### Acceptance Criteria

AC-M05-001. Diberikan pengguna Risk Owner memiliki permission yang benar, ketika pengguna menjalankan proses Risk Register, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-001a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-002. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Risk Assessment, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-002a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-003. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Risk Matrix, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-003a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-004. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Monitoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-004a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-004b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M05-005. Diberikan pengguna Komite Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Mitigation Plan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-005a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-006. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Residual Risk, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-006a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-007. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Risk Review, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-007a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-008. Diberikan pengguna Risk Owner memiliki permission yang benar, ketika pengguna menjalankan proses Risk Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-008a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-008b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M05-009. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Risk Register, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-009a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-010. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Risk Assessment, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-010a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-011. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Risk Matrix, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-011a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-012. Diberikan pengguna Komite Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Monitoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-012a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-012b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M05-013. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Mitigation Plan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-013a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-014. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Residual Risk, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-014a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-015. Diberikan pengguna Risk Owner memiliki permission yang benar, ketika pengguna menjalankan proses Risk Review, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-015a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-016. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Risk Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-016a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-016b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M05-017. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Risk Register, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-017a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-018. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Risk Assessment, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-018a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-019. Diberikan pengguna Komite Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Risk Matrix, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-019a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-020. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Monitoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-020a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-020b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M05-021. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Mitigation Plan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-021a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-022. Diberikan pengguna Risk Owner memiliki permission yang benar, ketika pengguna menjalankan proses Residual Risk, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-022a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-023. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Risk Review, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-023a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-024. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Risk Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-024a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-024b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M05-025. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Risk Register, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-025a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-026. Diberikan pengguna Komite Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Risk Assessment, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-026a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-027. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Risk Matrix, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-027a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-028. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Monitoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-028a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-028b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M05-029. Diberikan pengguna Risk Owner memiliki permission yang benar, ketika pengguna menjalankan proses Mitigation Plan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-029a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-030. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Residual Risk, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-030a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-031. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Risk Review, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-031a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-032. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Risk Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M05-032a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M05-032b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.

### Business Rules

BR-M05-001. Data Risk pada Risk Register hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-002. Data Risk Category pada Risk Assessment hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-003. Data Risk Assessment pada Risk Matrix hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-003a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M05-004. Data Risk Matrix pada Monitoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-005. Data Mitigation pada Mitigation Plan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-005b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M05-006. Data Monitoring Log pada Residual Risk hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-006a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M05-007. Data Residual Assessment pada Risk Review hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-008. Data Risk Appetite pada Risk Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-009. Data Risk pada Risk Register hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-009a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M05-010. Data Risk Category pada Risk Assessment hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-010b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M05-011. Data Risk Assessment pada Risk Matrix hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-012. Data Risk Matrix pada Monitoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-012a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M05-013. Data Mitigation pada Mitigation Plan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-014. Data Monitoring Log pada Residual Risk hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-015. Data Residual Assessment pada Risk Review hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-015a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M05-015b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M05-016. Data Risk Appetite pada Risk Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-017. Data Risk pada Risk Register hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-018. Data Risk Category pada Risk Assessment hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-018a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M05-019. Data Risk Assessment pada Risk Matrix hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-020. Data Risk Matrix pada Monitoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-020b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M05-021. Data Mitigation pada Mitigation Plan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-021a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M05-022. Data Monitoring Log pada Residual Risk hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-023. Data Residual Assessment pada Risk Review hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-024. Data Risk Appetite pada Risk Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-024a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M05-025. Data Risk pada Risk Register hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-025b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M05-026. Data Risk Category pada Risk Assessment hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-027. Data Risk Assessment pada Risk Matrix hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-027a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M05-028. Data Risk Matrix pada Monitoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-029. Data Mitigation pada Mitigation Plan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-030. Data Monitoring Log pada Residual Risk hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-030a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M05-030b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M05-031. Data Residual Assessment pada Risk Review hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-032. Data Risk Appetite pada Risk Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-033. Data Risk pada Risk Register hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-033a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M05-034. Data Risk Category pada Risk Assessment hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-035. Data Risk Assessment pada Risk Matrix hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-035b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M05-036. Data Risk Matrix pada Monitoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M05-036a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M05-KHS-01. Skala likelihood, impact, dan matrix harus dapat dikonfigurasi oleh admin tanpa hardcode.
BR-M05-KHS-02. Risiko residual harus dihitung setelah mitigasi dan disetujui risk owner.
BR-M05-KHS-03. Risiko tinggi wajib memiliki rencana mitigasi, pemilik, tenggat, dan monitoring aktif.

### UI Requirement

UI-M05-001. Modul harus menyediakan dashboard ringkasan untuk Risk Register dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M05-001a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M05-001b. Halaman dashboard ringkasan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M05-002. Modul harus menyediakan halaman daftar untuk Risk Assessment dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M05-002a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M05-002b. Halaman halaman daftar harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M05-003. Modul harus menyediakan form tambah dan ubah untuk Risk Matrix dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M05-003a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M05-003b. Halaman form tambah dan ubah harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M05-004. Modul harus menyediakan panel detail untuk Monitoring dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M05-004a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M05-004b. Halaman panel detail harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M05-005. Modul harus menyediakan timeline workflow untuk Mitigation Plan dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M05-005a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M05-005b. Halaman timeline workflow harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M05-006. Modul harus menyediakan tab lampiran untuk Residual Risk dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M05-006a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M05-006b. Halaman tab lampiran harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M05-007. Modul harus menyediakan modal approval untuk Risk Review dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M05-007a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M05-007b. Halaman modal approval harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M05-008. Modul harus menyediakan filter lanjutan untuk Risk Dashboard dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M05-008a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M05-008b. Halaman filter lanjutan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M05-009. Modul harus menyediakan indikator status untuk Risk Register dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M05-009a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M05-009b. Halaman indikator status harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M05-010. Modul harus menyediakan halaman konfigurasi untuk Risk Assessment dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M05-010a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M05-010b. Halaman halaman konfigurasi harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M05-011. Modul harus menyediakan wizard import untuk Risk Matrix dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M05-011a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M05-011b. Halaman wizard import harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M05-012. Modul harus menyediakan halaman laporan untuk Monitoring dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M05-012a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M05-012b. Halaman halaman laporan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M05-999. Semua form modul Risk Management harus menampilkan validasi inline, konfirmasi sebelum aksi irreversible, dan indikator penyimpanan saat proses berlangsung.

### API Requirement

API-M05-001. Endpoint /api/v1/risks harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M05-001a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M05-001b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M05-002. Endpoint /api/v1/risks/{id}/assessments harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M05-002a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M05-002b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M05-003. Endpoint /api/v1/risks/matrix harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M05-003a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M05-003b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M05-004. Endpoint /api/v1/risks/{id}/mitigations harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M05-004a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M05-004b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M05-005. Endpoint /api/v1/risks/{id}/monitoring harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M05-005a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M05-005b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M05-006. Endpoint /api/v1/risks/dashboard harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M05-006a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M05-006b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M05-OP-001. Operasi create harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M05-OP-002. Operasi read harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M05-OP-003. Operasi update harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M05-OP-004. Operasi submit harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M05-OP-005. Operasi approve harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M05-OP-006. Operasi reject harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M05-OP-007. Operasi publish harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M05-OP-008. Operasi archive harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M05-OP-009. Operasi search harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M05-OP-010. Operasi export harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M05-OP-011. Operasi history harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M05-OP-012. Operasi configure harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M05-SEC-001. API modul Risk Management harus menerapkan rate limiting, CSRF/session protection untuk web, dan pembatasan akses data lintas unit.

## 6. Modul Accreditation

Kode Modul: M06
Cakupan: FKTP/FKTL dynamic standards Bab > Standar > Kriteria > Elemen, Self Assessment, Gap Analysis, Mock Survey, Evidence, Readiness Dashboard
Aktor Utama: Admin Akreditasi, PIC Pokja, PIC Unit, Surveyor Internal, Kepala Klinik, Manajemen, Staf Dokumen
Komponen: Dynamic Standards, Self Assessment, Gap Analysis, Mock Survey, Evidence, Readiness Dashboard, Scoring, Survey Preparation
Entitas Data: Accreditation Scheme, Bab, Standar, Kriteria, Elemen, Assessment, Gap, Evidence, Mock Survey

Kebutuhan khusus modul:
M06-SP-01. Standar akreditasi FKTP/FKTL tidak boleh di-hardcode dan harus dikelola sebagai data dinamis.
M06-SP-02. Struktur Bab > Standar > Kriteria > Elemen harus mendukung perubahan regulasi tanpa deployment aplikasi.
M06-SP-03. Evidence dapat berasal dari dokumen, audit, CAPA, indikator mutu, risiko, atau upload manual dengan kontrol akses.

### Objective

M06-OBJ-01. Menyediakan kemampuan dynamic standards yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M06-OBJ-01a. Memastikan proses dynamic standards memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M06-OBJ-02. Menyediakan kemampuan self assessment yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M06-OBJ-02a. Memastikan proses self assessment memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M06-OBJ-03. Menyediakan kemampuan gap analysis yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M06-OBJ-03a. Memastikan proses gap analysis memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M06-OBJ-04. Menyediakan kemampuan mock survey yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M06-OBJ-04a. Memastikan proses mock survey memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M06-OBJ-05. Menyediakan kemampuan evidence yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M06-OBJ-05a. Memastikan proses evidence memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M06-OBJ-06. Menyediakan kemampuan readiness dashboard yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M06-OBJ-06a. Memastikan proses readiness dashboard memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M06-OBJ-07. Menyediakan kemampuan scoring yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M06-OBJ-07a. Memastikan proses scoring memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M06-OBJ-08. Menyediakan kemampuan survey preparation yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M06-OBJ-08a. Memastikan proses survey preparation memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M06-OBJ-DATA-01. Mengelola entitas Accreditation Scheme sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M06-OBJ-DATA-02. Mengelola entitas Bab sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M06-OBJ-DATA-03. Mengelola entitas Standar sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M06-OBJ-DATA-04. Mengelola entitas Kriteria sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M06-OBJ-DATA-05. Mengelola entitas Elemen sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M06-OBJ-DATA-06. Mengelola entitas Assessment sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M06-OBJ-DATA-07. Mengelola entitas Gap sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M06-OBJ-DATA-08. Mengelola entitas Evidence sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M06-OBJ-DATA-09. Mengelola entitas Mock Survey sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.

### User Story

M06-US-001. Sebagai Admin Akreditasi, saya ingin membuat data baru dengan validasi lengkap pada Dynamic Standards, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-001-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-001-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-001-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-002. Sebagai Admin Akreditasi, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Self Assessment, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-002-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-002-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-002-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-003. Sebagai Admin Akreditasi, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Gap Analysis, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-003-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-003-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-003-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-004. Sebagai Admin Akreditasi, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Mock Survey, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-004-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-004-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-004-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-005. Sebagai Admin Akreditasi, saya ingin melihat riwayat perubahan dan keputusan pada Evidence, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-005-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-005-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-005-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-006. Sebagai Admin Akreditasi, saya ingin mencari data berdasarkan filter operasional pada Readiness Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-006-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-006-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-006-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-007. Sebagai Admin Akreditasi, saya ingin mengekspor data untuk rapat manajemen pada Scoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-007-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-007-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-007-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-008. Sebagai Admin Akreditasi, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Survey Preparation, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-008-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-008-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-008-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-009. Sebagai Admin Akreditasi, saya ingin menghubungkan data modul ini dengan modul lain pada Dynamic Standards, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-009-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-009-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-009-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-010. Sebagai Admin Akreditasi, saya ingin memantau status pekerjaan yang belum selesai pada Self Assessment, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-010-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-010-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-010-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-011. Sebagai Admin Akreditasi, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Gap Analysis, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-011-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-011-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-011-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-012. Sebagai Admin Akreditasi, saya ingin membuat laporan periodik yang siap diaudit pada Mock Survey, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-012-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-012-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-012-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-013. Sebagai PIC Pokja, saya ingin membuat data baru dengan validasi lengkap pada Evidence, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-013-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-013-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-013-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-014. Sebagai PIC Pokja, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Readiness Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-014-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-014-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-014-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-015. Sebagai PIC Pokja, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Scoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-015-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-015-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-015-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-016. Sebagai PIC Pokja, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Survey Preparation, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-016-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-016-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-016-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-017. Sebagai PIC Pokja, saya ingin melihat riwayat perubahan dan keputusan pada Dynamic Standards, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-017-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-017-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-017-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-018. Sebagai PIC Pokja, saya ingin mencari data berdasarkan filter operasional pada Self Assessment, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-018-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-018-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-018-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-019. Sebagai PIC Pokja, saya ingin mengekspor data untuk rapat manajemen pada Gap Analysis, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-019-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-019-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-019-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-020. Sebagai PIC Pokja, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Mock Survey, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-020-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-020-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-020-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-021. Sebagai PIC Pokja, saya ingin menghubungkan data modul ini dengan modul lain pada Evidence, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-021-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-021-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-021-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-022. Sebagai PIC Pokja, saya ingin memantau status pekerjaan yang belum selesai pada Readiness Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-022-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-022-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-022-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-023. Sebagai PIC Pokja, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Scoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-023-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-023-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-023-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-024. Sebagai PIC Pokja, saya ingin membuat laporan periodik yang siap diaudit pada Survey Preparation, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-024-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-024-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-024-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-025. Sebagai PIC Unit, saya ingin membuat data baru dengan validasi lengkap pada Dynamic Standards, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-025-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-025-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-025-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-026. Sebagai PIC Unit, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Self Assessment, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-026-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-026-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-026-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-027. Sebagai PIC Unit, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Gap Analysis, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-027-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-027-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-027-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-028. Sebagai PIC Unit, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Mock Survey, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-028-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-028-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-028-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-029. Sebagai PIC Unit, saya ingin melihat riwayat perubahan dan keputusan pada Evidence, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-029-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-029-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-029-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-030. Sebagai PIC Unit, saya ingin mencari data berdasarkan filter operasional pada Readiness Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-030-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-030-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-030-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-031. Sebagai PIC Unit, saya ingin mengekspor data untuk rapat manajemen pada Scoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-031-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-031-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-031-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-032. Sebagai PIC Unit, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Survey Preparation, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-032-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-032-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-032-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-033. Sebagai PIC Unit, saya ingin menghubungkan data modul ini dengan modul lain pada Dynamic Standards, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-033-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-033-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-033-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-034. Sebagai PIC Unit, saya ingin memantau status pekerjaan yang belum selesai pada Self Assessment, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-034-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-034-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-034-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-035. Sebagai PIC Unit, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Gap Analysis, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-035-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-035-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-035-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-036. Sebagai PIC Unit, saya ingin membuat laporan periodik yang siap diaudit pada Mock Survey, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-036-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-036-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-036-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-037. Sebagai Surveyor Internal, saya ingin membuat data baru dengan validasi lengkap pada Evidence, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-037-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-037-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-037-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-038. Sebagai Surveyor Internal, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Readiness Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-038-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-038-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-038-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-039. Sebagai Surveyor Internal, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Scoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-039-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-039-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-039-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-040. Sebagai Surveyor Internal, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Survey Preparation, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-040-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-040-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-040-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-041. Sebagai Surveyor Internal, saya ingin melihat riwayat perubahan dan keputusan pada Dynamic Standards, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-041-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-041-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-041-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M06-US-042. Sebagai Surveyor Internal, saya ingin mencari data berdasarkan filter operasional pada Self Assessment, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M06-US-042-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M06-US-042-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M06-US-042-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.

### Use Case

M06-UC-01. Membuat Dan Menyimpan Entri Baru untuk Dynamic Standards
  Aktor Utama: Admin Akreditasi.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Dynamic Standards dan menjalankan aksi membuat dan menyimpan entri baru.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Accreditation Scheme tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Dynamic Standards sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Accreditation Scheme tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M06-UC-02. Mengirim Entri Ke Workflow Persetujuan untuk Self Assessment
  Aktor Utama: PIC Pokja.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Self Assessment dan menjalankan aksi mengirim entri ke workflow persetujuan.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Bab tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Self Assessment sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Bab tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M06-UC-03. Melakukan Review Substansi untuk Gap Analysis
  Aktor Utama: PIC Unit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Gap Analysis dan menjalankan aksi melakukan review substansi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Standar tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Gap Analysis sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Standar tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M06-UC-04. Memberikan Approval Final untuk Mock Survey
  Aktor Utama: Surveyor Internal.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Mock Survey dan menjalankan aksi memberikan approval final.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Kriteria tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Mock Survey sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Kriteria tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M06-UC-05. Mengembalikan Entri Untuk Revisi untuk Evidence
  Aktor Utama: Kepala Klinik.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Evidence dan menjalankan aksi mengembalikan entri untuk revisi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Elemen tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Evidence sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Elemen tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M06-UC-06. Mempublikasikan Hasil Yang Telah Sah untuk Readiness Dashboard
  Aktor Utama: Manajemen.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Readiness Dashboard dan menjalankan aksi mempublikasikan hasil yang telah sah.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Assessment tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Readiness Dashboard sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Assessment tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M06-UC-07. Menautkan Evidence Atau Lampiran untuk Scoring
  Aktor Utama: Staf Dokumen.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Scoring dan menjalankan aksi menautkan evidence atau lampiran.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Gap tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Scoring sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Gap tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M06-UC-08. Melakukan Monitoring Tenggat untuk Survey Preparation
  Aktor Utama: Admin Akreditasi.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Survey Preparation dan menjalankan aksi melakukan monitoring tenggat.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Evidence tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Survey Preparation sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Evidence tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M06-UC-09. Menutup Pekerjaan Setelah Seluruh Syarat Terpenuhi untuk Dynamic Standards
  Aktor Utama: PIC Pokja.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Dynamic Standards dan menjalankan aksi menutup pekerjaan setelah seluruh syarat terpenuhi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Mock Survey tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Dynamic Standards sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Mock Survey tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M06-UC-10. Mengekspor Laporan Resmi untuk Self Assessment
  Aktor Utama: PIC Unit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Self Assessment dan menjalankan aksi mengekspor laporan resmi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Accreditation Scheme tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Self Assessment sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Accreditation Scheme tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M06-UC-11. Mengubah Konfigurasi Referensi untuk Gap Analysis
  Aktor Utama: Surveyor Internal.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Gap Analysis dan menjalankan aksi mengubah konfigurasi referensi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Bab tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Gap Analysis sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Bab tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M06-UC-12. Menelusuri Audit Trail untuk Mock Survey
  Aktor Utama: Kepala Klinik.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Mock Survey dan menjalankan aksi menelusuri audit trail.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Standar tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Mock Survey sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Standar tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.

### Functional Requirement

FR-M06-001. Sistem harus menyediakan menu Dynamic Standards dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M06-002. Sistem harus menghubungkan Dynamic Standards dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M06-003. Sistem harus mencatat audit trail untuk setiap perubahan data Dynamic Standards, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M06-004. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Dynamic Standards bila relevan dengan proses bisnis.
FR-M06-005. Sistem harus menyediakan menu Self Assessment dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M06-006. Sistem harus menghubungkan Self Assessment dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M06-007. Sistem harus mencatat audit trail untuk setiap perubahan data Self Assessment, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M06-008. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Self Assessment bila relevan dengan proses bisnis.
FR-M06-009. Sistem harus menyediakan menu Gap Analysis dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M06-010. Sistem harus menghubungkan Gap Analysis dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M06-011. Sistem harus mencatat audit trail untuk setiap perubahan data Gap Analysis, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M06-012. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Gap Analysis bila relevan dengan proses bisnis.
FR-M06-013. Sistem harus menyediakan menu Mock Survey dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M06-014. Sistem harus menghubungkan Mock Survey dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M06-015. Sistem harus mencatat audit trail untuk setiap perubahan data Mock Survey, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M06-016. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Mock Survey bila relevan dengan proses bisnis.
FR-M06-017. Sistem harus menyediakan menu Evidence dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M06-018. Sistem harus menghubungkan Evidence dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M06-019. Sistem harus mencatat audit trail untuk setiap perubahan data Evidence, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M06-020. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Evidence bila relevan dengan proses bisnis.
FR-M06-021. Sistem harus menyediakan menu Readiness Dashboard dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M06-022. Sistem harus menghubungkan Readiness Dashboard dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M06-023. Sistem harus mencatat audit trail untuk setiap perubahan data Readiness Dashboard, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M06-024. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Readiness Dashboard bila relevan dengan proses bisnis.
FR-M06-025. Sistem harus menyediakan menu Scoring dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M06-026. Sistem harus menghubungkan Scoring dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M06-027. Sistem harus mencatat audit trail untuk setiap perubahan data Scoring, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M06-028. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Scoring bila relevan dengan proses bisnis.
FR-M06-029. Sistem harus menyediakan menu Survey Preparation dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M06-030. Sistem harus menghubungkan Survey Preparation dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M06-031. Sistem harus mencatat audit trail untuk setiap perubahan data Survey Preparation, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M06-032. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Survey Preparation bila relevan dengan proses bisnis.
FR-M06-033. Sistem harus memvalidasi entitas Accreditation Scheme menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M06-034. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Accreditation Scheme dengan pagination standar.
FR-M06-035. Sistem harus memvalidasi entitas Bab menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M06-036. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Bab dengan pagination standar.
FR-M06-037. Sistem harus memvalidasi entitas Standar menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M06-038. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Standar dengan pagination standar.
FR-M06-039. Sistem harus memvalidasi entitas Kriteria menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M06-040. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Kriteria dengan pagination standar.
FR-M06-041. Sistem harus memvalidasi entitas Elemen menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M06-042. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Elemen dengan pagination standar.
FR-M06-043. Sistem harus memvalidasi entitas Assessment menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M06-044. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Assessment dengan pagination standar.
FR-M06-045. Sistem harus memvalidasi entitas Gap menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M06-046. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Gap dengan pagination standar.
FR-M06-047. Sistem harus memvalidasi entitas Evidence menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M06-048. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Evidence dengan pagination standar.
FR-M06-049. Sistem harus memvalidasi entitas Mock Survey menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M06-050. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Mock Survey dengan pagination standar.
FR-M06-051. Sistem harus memenuhi ketentuan khusus: Standar akreditasi FKTP/FKTL tidak boleh di-hardcode dan harus dikelola sebagai data dinamis.
FR-M06-052. Sistem harus memenuhi ketentuan khusus: Struktur Bab > Standar > Kriteria > Elemen harus mendukung perubahan regulasi tanpa deployment aplikasi.
FR-M06-053. Sistem harus memenuhi ketentuan khusus: Evidence dapat berasal dari dokumen, audit, CAPA, indikator mutu, risiko, atau upload manual dengan kontrol akses.
FR-M06-054. Sistem harus menyediakan kontrol operasional tambahan untuk Readiness Dashboard, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M06-055. Sistem harus menyediakan kontrol operasional tambahan untuk Scoring, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M06-056. Sistem harus menyediakan kontrol operasional tambahan untuk Survey Preparation, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M06-057. Sistem harus menyediakan kontrol operasional tambahan untuk Dynamic Standards, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M06-058. Sistem harus menyediakan kontrol operasional tambahan untuk Self Assessment, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.

### Non Functional Requirement

NFR-M06-001. Keamanan: sistem harus memastikan akses berbasis role, permission, unit, dan status workflow diterapkan konsisten pada seluruh fitur modul Accreditation.
NFR-M06-002. Keamanan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M06-003. Kinerja: sistem harus memastikan halaman daftar utama merespons cepat untuk data operasional besar pada seluruh fitur modul Accreditation.
NFR-M06-004. Kinerja: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M06-005. Reliabilitas: sistem harus memastikan transaksi penting menggunakan database transaction dan mekanisme retry terukur pada seluruh fitur modul Accreditation.
NFR-M06-006. Reliabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M06-007. Auditabilitas: sistem harus memastikan setiap perubahan bernilai kepatuhan memiliki jejak waktu, aktor, nilai lama, dan nilai baru pada seluruh fitur modul Accreditation.
NFR-M06-008. Auditabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M06-009. Ketersediaan: sistem harus memastikan fitur kritikal tetap stabil saat Redis digunakan untuk queue, cache, dan session pada seluruh fitur modul Accreditation.
NFR-M06-010. Ketersediaan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M06-011. Skalabilitas: sistem harus memastikan desain tabel dan indeks mendukung pertumbuhan multi tahun pada seluruh fitur modul Accreditation.
NFR-M06-012. Skalabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M06-013. Usability: sistem harus memastikan alur kerja utama dapat diselesaikan oleh pengguna non teknis dengan instruksi jelas pada seluruh fitur modul Accreditation.
NFR-M06-014. Usability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M06-015. Interoperabilitas: sistem harus memastikan API menggunakan JSON, pagination, filter, dan kode error konsisten pada seluruh fitur modul Accreditation.
NFR-M06-016. Interoperabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M06-017. Privasi: sistem harus memastikan data sensitif hanya tampil sesuai kebutuhan tugas dan kewenangan pada seluruh fitur modul Accreditation.
NFR-M06-018. Privasi: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M06-019. Maintainability: sistem harus memastikan konfigurasi bisnis disimpan sebagai data, bukan konstanta tersembunyi di kode pada seluruh fitur modul Accreditation.
NFR-M06-020. Maintainability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M06-021. Database: query utama modul harus memiliki index yang sesuai untuk filter status, periode, unit, pemilik, dan workflow.
NFR-M06-022. Queue: proses notifikasi, ekspor, reminder, dan kalkulasi agregat harus berjalan asynchronous menggunakan Redis queue.
NFR-M06-023. Observability: error, retry, durasi proses, dan kegagalan integrasi harus tercatat dalam log terstruktur.

### Acceptance Criteria

AC-M06-001. Diberikan pengguna Admin Akreditasi memiliki permission yang benar, ketika pengguna menjalankan proses Dynamic Standards, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-001a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-002. Diberikan pengguna PIC Pokja memiliki permission yang benar, ketika pengguna menjalankan proses Self Assessment, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-002a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-003. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Gap Analysis, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-003a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-004. Diberikan pengguna Surveyor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Mock Survey, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-004a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-004b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M06-005. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Evidence, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-005a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-006. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Readiness Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-006a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-007. Diberikan pengguna Staf Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Scoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-007a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-008. Diberikan pengguna Admin Akreditasi memiliki permission yang benar, ketika pengguna menjalankan proses Survey Preparation, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-008a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-008b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M06-009. Diberikan pengguna PIC Pokja memiliki permission yang benar, ketika pengguna menjalankan proses Dynamic Standards, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-009a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-010. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Self Assessment, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-010a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-011. Diberikan pengguna Surveyor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Gap Analysis, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-011a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-012. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Mock Survey, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-012a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-012b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M06-013. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Evidence, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-013a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-014. Diberikan pengguna Staf Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Readiness Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-014a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-015. Diberikan pengguna Admin Akreditasi memiliki permission yang benar, ketika pengguna menjalankan proses Scoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-015a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-016. Diberikan pengguna PIC Pokja memiliki permission yang benar, ketika pengguna menjalankan proses Survey Preparation, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-016a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-016b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M06-017. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Dynamic Standards, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-017a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-018. Diberikan pengguna Surveyor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Self Assessment, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-018a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-019. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Gap Analysis, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-019a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-020. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Mock Survey, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-020a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-020b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M06-021. Diberikan pengguna Staf Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Evidence, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-021a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-022. Diberikan pengguna Admin Akreditasi memiliki permission yang benar, ketika pengguna menjalankan proses Readiness Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-022a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-023. Diberikan pengguna PIC Pokja memiliki permission yang benar, ketika pengguna menjalankan proses Scoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-023a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-024. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Survey Preparation, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-024a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-024b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M06-025. Diberikan pengguna Surveyor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Dynamic Standards, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-025a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-026. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Self Assessment, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-026a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-027. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Gap Analysis, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-027a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-028. Diberikan pengguna Staf Dokumen memiliki permission yang benar, ketika pengguna menjalankan proses Mock Survey, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-028a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-028b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M06-029. Diberikan pengguna Admin Akreditasi memiliki permission yang benar, ketika pengguna menjalankan proses Evidence, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-029a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-030. Diberikan pengguna PIC Pokja memiliki permission yang benar, ketika pengguna menjalankan proses Readiness Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-030a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-031. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Scoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-031a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-032. Diberikan pengguna Surveyor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Survey Preparation, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M06-032a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M06-032b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.

### Business Rules

BR-M06-001. Data Accreditation Scheme pada Dynamic Standards hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-002. Data Bab pada Self Assessment hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-003. Data Standar pada Gap Analysis hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-003a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M06-004. Data Kriteria pada Mock Survey hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-005. Data Elemen pada Evidence hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-005b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M06-006. Data Assessment pada Readiness Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-006a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M06-007. Data Gap pada Scoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-008. Data Evidence pada Survey Preparation hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-009. Data Mock Survey pada Dynamic Standards hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-009a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M06-010. Data Accreditation Scheme pada Self Assessment hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-010b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M06-011. Data Bab pada Gap Analysis hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-012. Data Standar pada Mock Survey hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-012a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M06-013. Data Kriteria pada Evidence hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-014. Data Elemen pada Readiness Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-015. Data Assessment pada Scoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-015a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M06-015b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M06-016. Data Gap pada Survey Preparation hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-017. Data Evidence pada Dynamic Standards hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-018. Data Mock Survey pada Self Assessment hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-018a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M06-019. Data Accreditation Scheme pada Gap Analysis hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-020. Data Bab pada Mock Survey hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-020b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M06-021. Data Standar pada Evidence hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-021a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M06-022. Data Kriteria pada Readiness Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-023. Data Elemen pada Scoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-024. Data Assessment pada Survey Preparation hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-024a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M06-025. Data Gap pada Dynamic Standards hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-025b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M06-026. Data Evidence pada Self Assessment hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-027. Data Mock Survey pada Gap Analysis hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-027a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M06-028. Data Accreditation Scheme pada Mock Survey hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-029. Data Bab pada Evidence hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-030. Data Standar pada Readiness Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-030a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M06-030b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M06-031. Data Kriteria pada Scoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-032. Data Elemen pada Survey Preparation hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-033. Data Assessment pada Dynamic Standards hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-033a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M06-034. Data Gap pada Self Assessment hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-035. Data Evidence pada Gap Analysis hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-035b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M06-036. Data Mock Survey pada Mock Survey hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M06-036a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M06-KHS-01. Standar akreditasi FKTP/FKTL tidak boleh di-hardcode dan harus dikelola sebagai data dinamis.
BR-M06-KHS-02. Struktur Bab > Standar > Kriteria > Elemen harus mendukung perubahan regulasi tanpa deployment aplikasi.
BR-M06-KHS-03. Evidence dapat berasal dari dokumen, audit, CAPA, indikator mutu, risiko, atau upload manual dengan kontrol akses.

### UI Requirement

UI-M06-001. Modul harus menyediakan dashboard ringkasan untuk Dynamic Standards dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M06-001a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M06-001b. Halaman dashboard ringkasan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M06-002. Modul harus menyediakan halaman daftar untuk Self Assessment dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M06-002a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M06-002b. Halaman halaman daftar harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M06-003. Modul harus menyediakan form tambah dan ubah untuk Gap Analysis dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M06-003a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M06-003b. Halaman form tambah dan ubah harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M06-004. Modul harus menyediakan panel detail untuk Mock Survey dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M06-004a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M06-004b. Halaman panel detail harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M06-005. Modul harus menyediakan timeline workflow untuk Evidence dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M06-005a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M06-005b. Halaman timeline workflow harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M06-006. Modul harus menyediakan tab lampiran untuk Readiness Dashboard dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M06-006a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M06-006b. Halaman tab lampiran harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M06-007. Modul harus menyediakan modal approval untuk Scoring dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M06-007a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M06-007b. Halaman modal approval harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M06-008. Modul harus menyediakan filter lanjutan untuk Survey Preparation dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M06-008a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M06-008b. Halaman filter lanjutan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M06-009. Modul harus menyediakan indikator status untuk Dynamic Standards dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M06-009a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M06-009b. Halaman indikator status harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M06-010. Modul harus menyediakan halaman konfigurasi untuk Self Assessment dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M06-010a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M06-010b. Halaman halaman konfigurasi harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M06-011. Modul harus menyediakan wizard import untuk Gap Analysis dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M06-011a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M06-011b. Halaman wizard import harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M06-012. Modul harus menyediakan halaman laporan untuk Mock Survey dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M06-012a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M06-012b. Halaman halaman laporan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M06-999. Semua form modul Accreditation harus menampilkan validasi inline, konfirmasi sebelum aksi irreversible, dan indikator penyimpanan saat proses berlangsung.

### API Requirement

API-M06-001. Endpoint /api/v1/accreditation/schemes harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M06-001a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M06-001b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M06-002. Endpoint /api/v1/accreditation/chapters harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M06-002a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M06-002b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M06-003. Endpoint /api/v1/accreditation/elements harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M06-003a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M06-003b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M06-004. Endpoint /api/v1/accreditation/self-assessments harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M06-004a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M06-004b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M06-005. Endpoint /api/v1/accreditation/gaps harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M06-005a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M06-005b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M06-006. Endpoint /api/v1/accreditation/mock-surveys harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M06-006a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M06-006b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M06-007. Endpoint /api/v1/accreditation/readiness harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M06-007a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M06-007b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M06-OP-001. Operasi create harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M06-OP-002. Operasi read harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M06-OP-003. Operasi update harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M06-OP-004. Operasi submit harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M06-OP-005. Operasi approve harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M06-OP-006. Operasi reject harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M06-OP-007. Operasi publish harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M06-OP-008. Operasi archive harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M06-OP-009. Operasi search harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M06-OP-010. Operasi export harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M06-OP-011. Operasi history harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M06-OP-012. Operasi configure harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M06-SEC-001. API modul Accreditation harus menerapkan rate limiting, CSRF/session protection untuk web, dan pembatasan akses data lintas unit.

## 7. Modul Quality Management

Kode Modul: M07
Cakupan: Program Mutu, Indicator, Monitoring, Evaluation
Aktor Utama: Admin Mutu, PIC Indikator, PIC Unit, Kepala Klinik, Komite Mutu, Manajemen, Auditor Internal
Komponen: Program Mutu, Indikator Mutu, Target, Data Collection, Monitoring, Evaluation, Trend Analysis, Improvement Plan
Entitas Data: Quality Program, Quality Indicator, Indicator Target, Measurement, Evaluation, Improvement Plan, Data Source, Validation Record

Kebutuhan khusus modul:
M07-SP-01. Formula indikator harus dapat dikonfigurasi dan tervalidasi sebelum digunakan untuk pelaporan.
M07-SP-02. Setiap indikator memiliki numerator, denominator, target, periode ukur, PIC, dan sumber data.
M07-SP-03. Indikator yang tidak mencapai target harus dapat memicu evaluasi dan rencana perbaikan.

### Objective

M07-OBJ-01. Menyediakan kemampuan program mutu yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M07-OBJ-01a. Memastikan proses program mutu memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M07-OBJ-02. Menyediakan kemampuan indikator mutu yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M07-OBJ-02a. Memastikan proses indikator mutu memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M07-OBJ-03. Menyediakan kemampuan target yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M07-OBJ-03a. Memastikan proses target memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M07-OBJ-04. Menyediakan kemampuan data collection yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M07-OBJ-04a. Memastikan proses data collection memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M07-OBJ-05. Menyediakan kemampuan monitoring yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M07-OBJ-05a. Memastikan proses monitoring memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M07-OBJ-06. Menyediakan kemampuan evaluation yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M07-OBJ-06a. Memastikan proses evaluation memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M07-OBJ-07. Menyediakan kemampuan trend analysis yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M07-OBJ-07a. Memastikan proses trend analysis memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M07-OBJ-08. Menyediakan kemampuan improvement plan yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M07-OBJ-08a. Memastikan proses improvement plan memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M07-OBJ-DATA-01. Mengelola entitas Quality Program sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M07-OBJ-DATA-02. Mengelola entitas Quality Indicator sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M07-OBJ-DATA-03. Mengelola entitas Indicator Target sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M07-OBJ-DATA-04. Mengelola entitas Measurement sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M07-OBJ-DATA-05. Mengelola entitas Evaluation sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M07-OBJ-DATA-06. Mengelola entitas Improvement Plan sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M07-OBJ-DATA-07. Mengelola entitas Data Source sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M07-OBJ-DATA-08. Mengelola entitas Validation Record sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.

### User Story

M07-US-001. Sebagai Admin Mutu, saya ingin membuat data baru dengan validasi lengkap pada Program Mutu, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-001-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-001-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-001-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-002. Sebagai Admin Mutu, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Indikator Mutu, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-002-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-002-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-002-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-003. Sebagai Admin Mutu, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Target, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-003-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-003-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-003-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-004. Sebagai Admin Mutu, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Data Collection, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-004-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-004-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-004-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-005. Sebagai Admin Mutu, saya ingin melihat riwayat perubahan dan keputusan pada Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-005-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-005-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-005-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-006. Sebagai Admin Mutu, saya ingin mencari data berdasarkan filter operasional pada Evaluation, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-006-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-006-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-006-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-007. Sebagai Admin Mutu, saya ingin mengekspor data untuk rapat manajemen pada Trend Analysis, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-007-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-007-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-007-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-008. Sebagai Admin Mutu, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Improvement Plan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-008-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-008-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-008-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-009. Sebagai Admin Mutu, saya ingin menghubungkan data modul ini dengan modul lain pada Program Mutu, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-009-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-009-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-009-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-010. Sebagai Admin Mutu, saya ingin memantau status pekerjaan yang belum selesai pada Indikator Mutu, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-010-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-010-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-010-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-011. Sebagai Admin Mutu, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Target, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-011-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-011-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-011-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-012. Sebagai Admin Mutu, saya ingin membuat laporan periodik yang siap diaudit pada Data Collection, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-012-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-012-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-012-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-013. Sebagai PIC Indikator, saya ingin membuat data baru dengan validasi lengkap pada Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-013-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-013-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-013-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-014. Sebagai PIC Indikator, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Evaluation, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-014-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-014-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-014-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-015. Sebagai PIC Indikator, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Trend Analysis, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-015-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-015-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-015-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-016. Sebagai PIC Indikator, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Improvement Plan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-016-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-016-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-016-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-017. Sebagai PIC Indikator, saya ingin melihat riwayat perubahan dan keputusan pada Program Mutu, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-017-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-017-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-017-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-018. Sebagai PIC Indikator, saya ingin mencari data berdasarkan filter operasional pada Indikator Mutu, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-018-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-018-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-018-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-019. Sebagai PIC Indikator, saya ingin mengekspor data untuk rapat manajemen pada Target, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-019-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-019-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-019-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-020. Sebagai PIC Indikator, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Data Collection, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-020-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-020-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-020-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-021. Sebagai PIC Indikator, saya ingin menghubungkan data modul ini dengan modul lain pada Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-021-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-021-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-021-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-022. Sebagai PIC Indikator, saya ingin memantau status pekerjaan yang belum selesai pada Evaluation, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-022-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-022-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-022-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-023. Sebagai PIC Indikator, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Trend Analysis, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-023-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-023-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-023-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-024. Sebagai PIC Indikator, saya ingin membuat laporan periodik yang siap diaudit pada Improvement Plan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-024-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-024-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-024-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-025. Sebagai PIC Unit, saya ingin membuat data baru dengan validasi lengkap pada Program Mutu, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-025-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-025-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-025-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-026. Sebagai PIC Unit, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Indikator Mutu, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-026-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-026-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-026-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-027. Sebagai PIC Unit, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Target, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-027-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-027-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-027-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-028. Sebagai PIC Unit, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Data Collection, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-028-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-028-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-028-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-029. Sebagai PIC Unit, saya ingin melihat riwayat perubahan dan keputusan pada Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-029-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-029-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-029-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-030. Sebagai PIC Unit, saya ingin mencari data berdasarkan filter operasional pada Evaluation, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-030-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-030-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-030-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-031. Sebagai PIC Unit, saya ingin mengekspor data untuk rapat manajemen pada Trend Analysis, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-031-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-031-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-031-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-032. Sebagai PIC Unit, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Improvement Plan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-032-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-032-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-032-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-033. Sebagai PIC Unit, saya ingin menghubungkan data modul ini dengan modul lain pada Program Mutu, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-033-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-033-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-033-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-034. Sebagai PIC Unit, saya ingin memantau status pekerjaan yang belum selesai pada Indikator Mutu, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-034-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-034-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-034-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-035. Sebagai PIC Unit, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Target, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-035-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-035-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-035-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-036. Sebagai PIC Unit, saya ingin membuat laporan periodik yang siap diaudit pada Data Collection, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-036-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-036-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-036-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-037. Sebagai Kepala Klinik, saya ingin membuat data baru dengan validasi lengkap pada Monitoring, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-037-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-037-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-037-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-038. Sebagai Kepala Klinik, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Evaluation, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-038-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-038-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-038-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-039. Sebagai Kepala Klinik, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Trend Analysis, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-039-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-039-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-039-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-040. Sebagai Kepala Klinik, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Improvement Plan, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-040-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-040-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-040-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-041. Sebagai Kepala Klinik, saya ingin melihat riwayat perubahan dan keputusan pada Program Mutu, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-041-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-041-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-041-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M07-US-042. Sebagai Kepala Klinik, saya ingin mencari data berdasarkan filter operasional pada Indikator Mutu, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M07-US-042-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M07-US-042-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M07-US-042-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.

### Use Case

M07-UC-01. Membuat Dan Menyimpan Entri Baru untuk Program Mutu
  Aktor Utama: Admin Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Program Mutu dan menjalankan aksi membuat dan menyimpan entri baru.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Quality Program tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Program Mutu sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Quality Program tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M07-UC-02. Mengirim Entri Ke Workflow Persetujuan untuk Indikator Mutu
  Aktor Utama: PIC Indikator.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Indikator Mutu dan menjalankan aksi mengirim entri ke workflow persetujuan.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Quality Indicator tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Indikator Mutu sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Quality Indicator tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M07-UC-03. Melakukan Review Substansi untuk Target
  Aktor Utama: PIC Unit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Target dan menjalankan aksi melakukan review substansi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Indicator Target tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Target sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Indicator Target tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M07-UC-04. Memberikan Approval Final untuk Data Collection
  Aktor Utama: Kepala Klinik.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Data Collection dan menjalankan aksi memberikan approval final.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Measurement tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Data Collection sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Measurement tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M07-UC-05. Mengembalikan Entri Untuk Revisi untuk Monitoring
  Aktor Utama: Komite Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Monitoring dan menjalankan aksi mengembalikan entri untuk revisi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Evaluation tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Monitoring sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Evaluation tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M07-UC-06. Mempublikasikan Hasil Yang Telah Sah untuk Evaluation
  Aktor Utama: Manajemen.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Evaluation dan menjalankan aksi mempublikasikan hasil yang telah sah.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Improvement Plan tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Evaluation sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Improvement Plan tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M07-UC-07. Menautkan Evidence Atau Lampiran untuk Trend Analysis
  Aktor Utama: Auditor Internal.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Trend Analysis dan menjalankan aksi menautkan evidence atau lampiran.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Data Source tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Trend Analysis sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Data Source tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M07-UC-08. Melakukan Monitoring Tenggat untuk Improvement Plan
  Aktor Utama: Admin Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Improvement Plan dan menjalankan aksi melakukan monitoring tenggat.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Validation Record tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Improvement Plan sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Validation Record tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M07-UC-09. Menutup Pekerjaan Setelah Seluruh Syarat Terpenuhi untuk Program Mutu
  Aktor Utama: PIC Indikator.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Program Mutu dan menjalankan aksi menutup pekerjaan setelah seluruh syarat terpenuhi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Quality Program tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Program Mutu sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Quality Program tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M07-UC-10. Mengekspor Laporan Resmi untuk Indikator Mutu
  Aktor Utama: PIC Unit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Indikator Mutu dan menjalankan aksi mengekspor laporan resmi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Quality Indicator tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Indikator Mutu sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Quality Indicator tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M07-UC-11. Mengubah Konfigurasi Referensi untuk Target
  Aktor Utama: Kepala Klinik.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Target dan menjalankan aksi mengubah konfigurasi referensi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Indicator Target tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Target sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Indicator Target tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M07-UC-12. Menelusuri Audit Trail untuk Data Collection
  Aktor Utama: Komite Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Data Collection dan menjalankan aksi menelusuri audit trail.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Measurement tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Data Collection sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Measurement tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.

### Functional Requirement

FR-M07-001. Sistem harus menyediakan menu Program Mutu dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M07-002. Sistem harus menghubungkan Program Mutu dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M07-003. Sistem harus mencatat audit trail untuk setiap perubahan data Program Mutu, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M07-004. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Program Mutu bila relevan dengan proses bisnis.
FR-M07-005. Sistem harus menyediakan menu Indikator Mutu dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M07-006. Sistem harus menghubungkan Indikator Mutu dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M07-007. Sistem harus mencatat audit trail untuk setiap perubahan data Indikator Mutu, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M07-008. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Indikator Mutu bila relevan dengan proses bisnis.
FR-M07-009. Sistem harus menyediakan menu Target dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M07-010. Sistem harus menghubungkan Target dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M07-011. Sistem harus mencatat audit trail untuk setiap perubahan data Target, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M07-012. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Target bila relevan dengan proses bisnis.
FR-M07-013. Sistem harus menyediakan menu Data Collection dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M07-014. Sistem harus menghubungkan Data Collection dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M07-015. Sistem harus mencatat audit trail untuk setiap perubahan data Data Collection, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M07-016. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Data Collection bila relevan dengan proses bisnis.
FR-M07-017. Sistem harus menyediakan menu Monitoring dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M07-018. Sistem harus menghubungkan Monitoring dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M07-019. Sistem harus mencatat audit trail untuk setiap perubahan data Monitoring, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M07-020. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Monitoring bila relevan dengan proses bisnis.
FR-M07-021. Sistem harus menyediakan menu Evaluation dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M07-022. Sistem harus menghubungkan Evaluation dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M07-023. Sistem harus mencatat audit trail untuk setiap perubahan data Evaluation, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M07-024. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Evaluation bila relevan dengan proses bisnis.
FR-M07-025. Sistem harus menyediakan menu Trend Analysis dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M07-026. Sistem harus menghubungkan Trend Analysis dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M07-027. Sistem harus mencatat audit trail untuk setiap perubahan data Trend Analysis, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M07-028. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Trend Analysis bila relevan dengan proses bisnis.
FR-M07-029. Sistem harus menyediakan menu Improvement Plan dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M07-030. Sistem harus menghubungkan Improvement Plan dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M07-031. Sistem harus mencatat audit trail untuk setiap perubahan data Improvement Plan, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M07-032. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Improvement Plan bila relevan dengan proses bisnis.
FR-M07-033. Sistem harus memvalidasi entitas Quality Program menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M07-034. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Quality Program dengan pagination standar.
FR-M07-035. Sistem harus memvalidasi entitas Quality Indicator menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M07-036. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Quality Indicator dengan pagination standar.
FR-M07-037. Sistem harus memvalidasi entitas Indicator Target menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M07-038. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Indicator Target dengan pagination standar.
FR-M07-039. Sistem harus memvalidasi entitas Measurement menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M07-040. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Measurement dengan pagination standar.
FR-M07-041. Sistem harus memvalidasi entitas Evaluation menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M07-042. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Evaluation dengan pagination standar.
FR-M07-043. Sistem harus memvalidasi entitas Improvement Plan menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M07-044. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Improvement Plan dengan pagination standar.
FR-M07-045. Sistem harus memvalidasi entitas Data Source menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M07-046. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Data Source dengan pagination standar.
FR-M07-047. Sistem harus memvalidasi entitas Validation Record menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M07-048. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Validation Record dengan pagination standar.
FR-M07-049. Sistem harus memenuhi ketentuan khusus: Formula indikator harus dapat dikonfigurasi dan tervalidasi sebelum digunakan untuk pelaporan.
FR-M07-050. Sistem harus memenuhi ketentuan khusus: Setiap indikator memiliki numerator, denominator, target, periode ukur, PIC, dan sumber data.
FR-M07-051. Sistem harus memenuhi ketentuan khusus: Indikator yang tidak mencapai target harus dapat memicu evaluasi dan rencana perbaikan.
FR-M07-052. Sistem harus menyediakan kontrol operasional tambahan untuk Data Collection, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M07-053. Sistem harus menyediakan kontrol operasional tambahan untuk Monitoring, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M07-054. Sistem harus menyediakan kontrol operasional tambahan untuk Evaluation, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M07-055. Sistem harus menyediakan kontrol operasional tambahan untuk Trend Analysis, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M07-056. Sistem harus menyediakan kontrol operasional tambahan untuk Improvement Plan, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M07-057. Sistem harus menyediakan kontrol operasional tambahan untuk Program Mutu, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M07-058. Sistem harus menyediakan kontrol operasional tambahan untuk Indikator Mutu, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.

### Non Functional Requirement

NFR-M07-001. Keamanan: sistem harus memastikan akses berbasis role, permission, unit, dan status workflow diterapkan konsisten pada seluruh fitur modul Quality Management.
NFR-M07-002. Keamanan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M07-003. Kinerja: sistem harus memastikan halaman daftar utama merespons cepat untuk data operasional besar pada seluruh fitur modul Quality Management.
NFR-M07-004. Kinerja: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M07-005. Reliabilitas: sistem harus memastikan transaksi penting menggunakan database transaction dan mekanisme retry terukur pada seluruh fitur modul Quality Management.
NFR-M07-006. Reliabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M07-007. Auditabilitas: sistem harus memastikan setiap perubahan bernilai kepatuhan memiliki jejak waktu, aktor, nilai lama, dan nilai baru pada seluruh fitur modul Quality Management.
NFR-M07-008. Auditabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M07-009. Ketersediaan: sistem harus memastikan fitur kritikal tetap stabil saat Redis digunakan untuk queue, cache, dan session pada seluruh fitur modul Quality Management.
NFR-M07-010. Ketersediaan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M07-011. Skalabilitas: sistem harus memastikan desain tabel dan indeks mendukung pertumbuhan multi tahun pada seluruh fitur modul Quality Management.
NFR-M07-012. Skalabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M07-013. Usability: sistem harus memastikan alur kerja utama dapat diselesaikan oleh pengguna non teknis dengan instruksi jelas pada seluruh fitur modul Quality Management.
NFR-M07-014. Usability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M07-015. Interoperabilitas: sistem harus memastikan API menggunakan JSON, pagination, filter, dan kode error konsisten pada seluruh fitur modul Quality Management.
NFR-M07-016. Interoperabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M07-017. Privasi: sistem harus memastikan data sensitif hanya tampil sesuai kebutuhan tugas dan kewenangan pada seluruh fitur modul Quality Management.
NFR-M07-018. Privasi: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M07-019. Maintainability: sistem harus memastikan konfigurasi bisnis disimpan sebagai data, bukan konstanta tersembunyi di kode pada seluruh fitur modul Quality Management.
NFR-M07-020. Maintainability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M07-021. Database: query utama modul harus memiliki index yang sesuai untuk filter status, periode, unit, pemilik, dan workflow.
NFR-M07-022. Queue: proses notifikasi, ekspor, reminder, dan kalkulasi agregat harus berjalan asynchronous menggunakan Redis queue.
NFR-M07-023. Observability: error, retry, durasi proses, dan kegagalan integrasi harus tercatat dalam log terstruktur.

### Acceptance Criteria

AC-M07-001. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Program Mutu, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-001a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-002. Diberikan pengguna PIC Indikator memiliki permission yang benar, ketika pengguna menjalankan proses Indikator Mutu, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-002a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-003. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Target, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-003a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-004. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Data Collection, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-004a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-004b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M07-005. Diberikan pengguna Komite Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Monitoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-005a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-006. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Evaluation, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-006a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-007. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Trend Analysis, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-007a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-008. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Improvement Plan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-008a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-008b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M07-009. Diberikan pengguna PIC Indikator memiliki permission yang benar, ketika pengguna menjalankan proses Program Mutu, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-009a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-010. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Indikator Mutu, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-010a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-011. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Target, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-011a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-012. Diberikan pengguna Komite Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Data Collection, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-012a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-012b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M07-013. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Monitoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-013a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-014. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Evaluation, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-014a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-015. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Trend Analysis, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-015a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-016. Diberikan pengguna PIC Indikator memiliki permission yang benar, ketika pengguna menjalankan proses Improvement Plan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-016a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-016b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M07-017. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Program Mutu, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-017a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-018. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Indikator Mutu, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-018a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-019. Diberikan pengguna Komite Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Target, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-019a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-020. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Data Collection, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-020a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-020b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M07-021. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Monitoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-021a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-022. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Evaluation, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-022a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-023. Diberikan pengguna PIC Indikator memiliki permission yang benar, ketika pengguna menjalankan proses Trend Analysis, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-023a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-024. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Improvement Plan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-024a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-024b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M07-025. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Program Mutu, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-025a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-026. Diberikan pengguna Komite Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Indikator Mutu, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-026a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-027. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Target, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-027a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-028. Diberikan pengguna Auditor Internal memiliki permission yang benar, ketika pengguna menjalankan proses Data Collection, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-028a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-028b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M07-029. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Monitoring, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-029a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-030. Diberikan pengguna PIC Indikator memiliki permission yang benar, ketika pengguna menjalankan proses Evaluation, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-030a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-031. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Trend Analysis, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-031a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-032. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Improvement Plan, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M07-032a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M07-032b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.

### Business Rules

BR-M07-001. Data Quality Program pada Program Mutu hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-002. Data Quality Indicator pada Indikator Mutu hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-003. Data Indicator Target pada Target hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-003a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M07-004. Data Measurement pada Data Collection hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-005. Data Evaluation pada Monitoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-005b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M07-006. Data Improvement Plan pada Evaluation hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-006a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M07-007. Data Data Source pada Trend Analysis hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-008. Data Validation Record pada Improvement Plan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-009. Data Quality Program pada Program Mutu hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-009a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M07-010. Data Quality Indicator pada Indikator Mutu hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-010b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M07-011. Data Indicator Target pada Target hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-012. Data Measurement pada Data Collection hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-012a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M07-013. Data Evaluation pada Monitoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-014. Data Improvement Plan pada Evaluation hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-015. Data Data Source pada Trend Analysis hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-015a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M07-015b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M07-016. Data Validation Record pada Improvement Plan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-017. Data Quality Program pada Program Mutu hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-018. Data Quality Indicator pada Indikator Mutu hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-018a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M07-019. Data Indicator Target pada Target hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-020. Data Measurement pada Data Collection hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-020b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M07-021. Data Evaluation pada Monitoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-021a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M07-022. Data Improvement Plan pada Evaluation hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-023. Data Data Source pada Trend Analysis hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-024. Data Validation Record pada Improvement Plan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-024a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M07-025. Data Quality Program pada Program Mutu hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-025b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M07-026. Data Quality Indicator pada Indikator Mutu hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-027. Data Indicator Target pada Target hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-027a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M07-028. Data Measurement pada Data Collection hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-029. Data Evaluation pada Monitoring hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-030. Data Improvement Plan pada Evaluation hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-030a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M07-030b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M07-031. Data Data Source pada Trend Analysis hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-032. Data Validation Record pada Improvement Plan hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-033. Data Quality Program pada Program Mutu hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-033a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M07-034. Data Quality Indicator pada Indikator Mutu hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-035. Data Indicator Target pada Target hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-035b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M07-036. Data Measurement pada Data Collection hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M07-036a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M07-KHS-01. Formula indikator harus dapat dikonfigurasi dan tervalidasi sebelum digunakan untuk pelaporan.
BR-M07-KHS-02. Setiap indikator memiliki numerator, denominator, target, periode ukur, PIC, dan sumber data.
BR-M07-KHS-03. Indikator yang tidak mencapai target harus dapat memicu evaluasi dan rencana perbaikan.

### UI Requirement

UI-M07-001. Modul harus menyediakan dashboard ringkasan untuk Program Mutu dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M07-001a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M07-001b. Halaman dashboard ringkasan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M07-002. Modul harus menyediakan halaman daftar untuk Indikator Mutu dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M07-002a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M07-002b. Halaman halaman daftar harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M07-003. Modul harus menyediakan form tambah dan ubah untuk Target dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M07-003a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M07-003b. Halaman form tambah dan ubah harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M07-004. Modul harus menyediakan panel detail untuk Data Collection dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M07-004a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M07-004b. Halaman panel detail harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M07-005. Modul harus menyediakan timeline workflow untuk Monitoring dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M07-005a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M07-005b. Halaman timeline workflow harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M07-006. Modul harus menyediakan tab lampiran untuk Evaluation dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M07-006a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M07-006b. Halaman tab lampiran harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M07-007. Modul harus menyediakan modal approval untuk Trend Analysis dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M07-007a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M07-007b. Halaman modal approval harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M07-008. Modul harus menyediakan filter lanjutan untuk Improvement Plan dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M07-008a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M07-008b. Halaman filter lanjutan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M07-009. Modul harus menyediakan indikator status untuk Program Mutu dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M07-009a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M07-009b. Halaman indikator status harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M07-010. Modul harus menyediakan halaman konfigurasi untuk Indikator Mutu dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M07-010a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M07-010b. Halaman halaman konfigurasi harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M07-011. Modul harus menyediakan wizard import untuk Target dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M07-011a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M07-011b. Halaman wizard import harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M07-012. Modul harus menyediakan halaman laporan untuk Data Collection dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M07-012a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M07-012b. Halaman halaman laporan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M07-999. Semua form modul Quality Management harus menampilkan validasi inline, konfirmasi sebelum aksi irreversible, dan indikator penyimpanan saat proses berlangsung.

### API Requirement

API-M07-001. Endpoint /api/v1/quality/programs harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M07-001a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M07-001b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M07-002. Endpoint /api/v1/quality/indicators harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M07-002a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M07-002b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M07-003. Endpoint /api/v1/quality/measurements harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M07-003a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M07-003b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M07-004. Endpoint /api/v1/quality/evaluations harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M07-004a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M07-004b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M07-005. Endpoint /api/v1/quality/improvements harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M07-005a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M07-005b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M07-006. Endpoint /api/v1/quality/dashboard harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M07-006a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M07-006b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M07-OP-001. Operasi create harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M07-OP-002. Operasi read harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M07-OP-003. Operasi update harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M07-OP-004. Operasi submit harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M07-OP-005. Operasi approve harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M07-OP-006. Operasi reject harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M07-OP-007. Operasi publish harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M07-OP-008. Operasi archive harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M07-OP-009. Operasi search harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M07-OP-010. Operasi export harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M07-OP-011. Operasi history harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M07-OP-012. Operasi configure harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M07-SEC-001. API modul Quality Management harus menerapkan rate limiting, CSRF/session protection untuk web, dan pembatasan akses data lintas unit.

## 8. Modul Executive Dashboard & Clinic Dashboard

Kode Modul: M08
Cakupan: Executive Dashboard dan Clinic Dashboard lintas modul
Aktor Utama: Direktur, Kepala Klinik, Manajemen, Admin Mutu, PIC Unit, Komite Mutu, Pengawas
Komponen: Executive Dashboard, Clinic Dashboard, KPI Cards, Trend Charts, Alert Center, Drill Down, Export, Personalized View
Entitas Data: Dashboard Widget, KPI Metric, Dashboard Filter, Alert, Saved View, Export Job, Metric Snapshot, Drilldown Query

Kebutuhan khusus modul:
M08-SP-01. Dashboard harus menyajikan data lintas modul dengan filter periode, unit, status, dan tingkat risiko.
M08-SP-02. Setiap angka ringkasan harus dapat ditelusuri ke data sumber melalui drill down.
M08-SP-03. Tampilan eksekutif harus ringkas namun tetap menyertakan alert kritis dan tren kinerja.

### Objective

M08-OBJ-01. Menyediakan kemampuan executive dashboard yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M08-OBJ-01a. Memastikan proses executive dashboard memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M08-OBJ-02. Menyediakan kemampuan clinic dashboard yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M08-OBJ-02a. Memastikan proses clinic dashboard memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M08-OBJ-03. Menyediakan kemampuan kpi cards yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M08-OBJ-03a. Memastikan proses kpi cards memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M08-OBJ-04. Menyediakan kemampuan trend charts yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M08-OBJ-04a. Memastikan proses trend charts memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M08-OBJ-05. Menyediakan kemampuan alert center yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M08-OBJ-05a. Memastikan proses alert center memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M08-OBJ-06. Menyediakan kemampuan drill down yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M08-OBJ-06a. Memastikan proses drill down memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M08-OBJ-07. Menyediakan kemampuan export yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M08-OBJ-07a. Memastikan proses export memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M08-OBJ-08. Menyediakan kemampuan personalized view yang terdokumentasi, terukur, dapat diaudit, dan selaras dengan tata kelola mutu organisasi.
M08-OBJ-08a. Memastikan proses personalized view memiliki pemilik, status, tenggat, data pendukung, dan riwayat keputusan yang jelas.
M08-OBJ-DATA-01. Mengelola entitas Dashboard Widget sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M08-OBJ-DATA-02. Mengelola entitas KPI Metric sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M08-OBJ-DATA-03. Mengelola entitas Dashboard Filter sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M08-OBJ-DATA-04. Mengelola entitas Alert sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M08-OBJ-DATA-05. Mengelola entitas Saved View sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M08-OBJ-DATA-06. Mengelola entitas Export Job sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M08-OBJ-DATA-07. Mengelola entitas Metric Snapshot sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.
M08-OBJ-DATA-08. Mengelola entitas Drilldown Query sebagai data terstruktur dengan validasi, relasi, arsip, dan kontrol akses berbasis kebutuhan operasional.

### User Story

M08-US-001. Sebagai Direktur, saya ingin membuat data baru dengan validasi lengkap pada Executive Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-001-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-001-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-001-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-002. Sebagai Direktur, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Clinic Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-002-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-002-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-002-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-003. Sebagai Direktur, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada KPI Cards, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-003-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-003-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-003-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-004. Sebagai Direktur, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Trend Charts, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-004-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-004-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-004-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-005. Sebagai Direktur, saya ingin melihat riwayat perubahan dan keputusan pada Alert Center, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-005-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-005-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-005-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-006. Sebagai Direktur, saya ingin mencari data berdasarkan filter operasional pada Drill Down, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-006-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-006-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-006-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-007. Sebagai Direktur, saya ingin mengekspor data untuk rapat manajemen pada Export, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-007-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-007-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-007-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-008. Sebagai Direktur, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Personalized View, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-008-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-008-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-008-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-009. Sebagai Direktur, saya ingin menghubungkan data modul ini dengan modul lain pada Executive Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-009-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-009-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-009-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-010. Sebagai Direktur, saya ingin memantau status pekerjaan yang belum selesai pada Clinic Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-010-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-010-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-010-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-011. Sebagai Direktur, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada KPI Cards, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-011-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-011-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-011-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-012. Sebagai Direktur, saya ingin membuat laporan periodik yang siap diaudit pada Trend Charts, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-012-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-012-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-012-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-013. Sebagai Kepala Klinik, saya ingin membuat data baru dengan validasi lengkap pada Alert Center, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-013-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-013-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-013-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-014. Sebagai Kepala Klinik, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Drill Down, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-014-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-014-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-014-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-015. Sebagai Kepala Klinik, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Export, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-015-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-015-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-015-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-016. Sebagai Kepala Klinik, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Personalized View, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-016-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-016-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-016-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-017. Sebagai Kepala Klinik, saya ingin melihat riwayat perubahan dan keputusan pada Executive Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-017-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-017-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-017-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-018. Sebagai Kepala Klinik, saya ingin mencari data berdasarkan filter operasional pada Clinic Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-018-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-018-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-018-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-019. Sebagai Kepala Klinik, saya ingin mengekspor data untuk rapat manajemen pada KPI Cards, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-019-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-019-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-019-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-020. Sebagai Kepala Klinik, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Trend Charts, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-020-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-020-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-020-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-021. Sebagai Kepala Klinik, saya ingin menghubungkan data modul ini dengan modul lain pada Alert Center, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-021-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-021-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-021-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-022. Sebagai Kepala Klinik, saya ingin memantau status pekerjaan yang belum selesai pada Drill Down, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-022-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-022-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-022-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-023. Sebagai Kepala Klinik, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada Export, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-023-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-023-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-023-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-024. Sebagai Kepala Klinik, saya ingin membuat laporan periodik yang siap diaudit pada Personalized View, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-024-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-024-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-024-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-025. Sebagai Manajemen, saya ingin membuat data baru dengan validasi lengkap pada Executive Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-025-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-025-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-025-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-026. Sebagai Manajemen, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Clinic Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-026-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-026-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-026-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-027. Sebagai Manajemen, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada KPI Cards, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-027-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-027-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-027-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-028. Sebagai Manajemen, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Trend Charts, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-028-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-028-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-028-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-029. Sebagai Manajemen, saya ingin melihat riwayat perubahan dan keputusan pada Alert Center, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-029-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-029-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-029-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-030. Sebagai Manajemen, saya ingin mencari data berdasarkan filter operasional pada Drill Down, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-030-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-030-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-030-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-031. Sebagai Manajemen, saya ingin mengekspor data untuk rapat manajemen pada Export, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-031-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-031-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-031-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-032. Sebagai Manajemen, saya ingin mengunggah lampiran pendukung dengan kontrol akses pada Personalized View, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-032-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-032-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-032-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-033. Sebagai Manajemen, saya ingin menghubungkan data modul ini dengan modul lain pada Executive Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-033-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-033-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-033-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-034. Sebagai Manajemen, saya ingin memantau status pekerjaan yang belum selesai pada Clinic Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-034-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-034-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-034-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-035. Sebagai Manajemen, saya ingin mengatur parameter modul sesuai kebijakan organisasi pada KPI Cards, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-035-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-035-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-035-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-036. Sebagai Manajemen, saya ingin membuat laporan periodik yang siap diaudit pada Trend Charts, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-036-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-036-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-036-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-037. Sebagai Admin Mutu, saya ingin membuat data baru dengan validasi lengkap pada Alert Center, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-037-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-037-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-037-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-038. Sebagai Admin Mutu, saya ingin meninjau data sebelum dikirim ke tahap berikutnya pada Drill Down, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-038-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-038-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-038-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-039. Sebagai Admin Mutu, saya ingin menyetujui atau menolak pekerjaan dengan alasan terdokumentasi pada Export, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-039-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-039-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-039-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-040. Sebagai Admin Mutu, saya ingin menerima notifikasi ketika ada tugas baru atau tenggat mendekat pada Personalized View, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-040-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-040-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-040-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-041. Sebagai Admin Mutu, saya ingin melihat riwayat perubahan dan keputusan pada Executive Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-041-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-041-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-041-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.
M08-US-042. Sebagai Admin Mutu, saya ingin mencari data berdasarkan filter operasional pada Clinic Dashboard, sehingga pekerjaan saya dapat diselesaikan secara konsisten dan dapat dipertanggungjawabkan.
M08-US-042-A. Sistem menampilkan data yang relevan dengan role, unit, dan status pekerjaan pengguna.
M08-US-042-B. Sistem menyimpan jejak aktivitas ketika pengguna membuat, mengubah, mengirim, menyetujui, menolak, atau menutup pekerjaan.
M08-US-042-C. Sistem memberi pesan validasi yang jelas jika data wajib, lampiran, atau syarat workflow belum terpenuhi.

### Use Case

M08-UC-01. Membuat Dan Menyimpan Entri Baru untuk Executive Dashboard
  Aktor Utama: Direktur.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Executive Dashboard dan menjalankan aksi membuat dan menyimpan entri baru.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Dashboard Widget tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Executive Dashboard sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Dashboard Widget tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M08-UC-02. Mengirim Entri Ke Workflow Persetujuan untuk Clinic Dashboard
  Aktor Utama: Kepala Klinik.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Clinic Dashboard dan menjalankan aksi mengirim entri ke workflow persetujuan.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi KPI Metric tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Clinic Dashboard sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data KPI Metric tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M08-UC-03. Melakukan Review Substansi untuk KPI Cards
  Aktor Utama: Manajemen.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu KPI Cards dan menjalankan aksi melakukan review substansi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Dashboard Filter tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja KPI Cards sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Dashboard Filter tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M08-UC-04. Memberikan Approval Final untuk Trend Charts
  Aktor Utama: Admin Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Trend Charts dan menjalankan aksi memberikan approval final.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Alert tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Trend Charts sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Alert tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M08-UC-05. Mengembalikan Entri Untuk Revisi untuk Alert Center
  Aktor Utama: PIC Unit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Alert Center dan menjalankan aksi mengembalikan entri untuk revisi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Saved View tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Alert Center sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Saved View tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M08-UC-06. Mempublikasikan Hasil Yang Telah Sah untuk Drill Down
  Aktor Utama: Komite Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Drill Down dan menjalankan aksi mempublikasikan hasil yang telah sah.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Export Job tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Drill Down sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Export Job tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M08-UC-07. Menautkan Evidence Atau Lampiran untuk Export
  Aktor Utama: Pengawas.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Export dan menjalankan aksi menautkan evidence atau lampiran.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Metric Snapshot tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Export sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Metric Snapshot tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M08-UC-08. Melakukan Monitoring Tenggat untuk Personalized View
  Aktor Utama: Direktur.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Personalized View dan menjalankan aksi melakukan monitoring tenggat.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Drilldown Query tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Personalized View sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Drilldown Query tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M08-UC-09. Menutup Pekerjaan Setelah Seluruh Syarat Terpenuhi untuk Executive Dashboard
  Aktor Utama: Kepala Klinik.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Executive Dashboard dan menjalankan aksi menutup pekerjaan setelah seluruh syarat terpenuhi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Dashboard Widget tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Executive Dashboard sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Dashboard Widget tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M08-UC-10. Mengekspor Laporan Resmi untuk Clinic Dashboard
  Aktor Utama: Manajemen.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Clinic Dashboard dan menjalankan aksi mengekspor laporan resmi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi KPI Metric tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Clinic Dashboard sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data KPI Metric tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M08-UC-11. Mengubah Konfigurasi Referensi untuk KPI Cards
  Aktor Utama: Admin Mutu.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu KPI Cards dan menjalankan aksi mengubah konfigurasi referensi.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Dashboard Filter tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja KPI Cards sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Dashboard Filter tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.
M08-UC-12. Menelusuri Audit Trail untuk Trend Charts
  Aktor Utama: PIC Unit.
  Aktor Pendukung: Admin Mutu, PIC Unit, sistem notifikasi, dan workflow engine sesuai konfigurasi.
  Pemicu: Pengguna memilih menu Trend Charts dan menjalankan aksi menelusuri audit trail.
  Prasyarat: Pengguna telah login, memiliki permission yang sesuai, dan data referensi Alert tersedia.
  Alur Normal 1: Sistem menampilkan form atau halaman kerja Trend Charts sesuai status data dan kewenangan pengguna.
  Alur Normal 2: Pengguna melengkapi informasi utama, metadata, lampiran, relasi, dan catatan yang diwajibkan.
  Alur Normal 3: Sistem menjalankan validasi field, validasi bisnis, validasi workflow, dan validasi akses unit.
  Alur Normal 4: Sistem menyimpan perubahan dalam transaksi PostgreSQL dan membuat audit trail lengkap.
  Alur Normal 5: Sistem mengirim notifikasi melalui queue Redis kepada pihak berikutnya bila ada perubahan status.
  Alur Alternatif 1: Jika validasi gagal, sistem menolak penyimpanan dan menampilkan pesan kesalahan per field.
  Alur Alternatif 2: Jika pengguna tidak berwenang, sistem mengembalikan respons 403 dan mencatat percobaan akses.
  Alur Alternatif 3: Jika workflow belum dikonfigurasi, sistem menahan aksi submit dan meminta admin melengkapi konfigurasi.
  Kondisi Akhir Sukses: Data Alert tersimpan, status diperbarui, notifikasi terkirim, dan riwayat dapat ditelusuri.
  Kondisi Akhir Gagal: Tidak ada perubahan parsial yang tertinggal dan pengguna menerima instruksi perbaikan yang spesifik.

### Functional Requirement

FR-M08-001. Sistem harus menyediakan menu Executive Dashboard dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M08-002. Sistem harus menghubungkan Executive Dashboard dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M08-003. Sistem harus mencatat audit trail untuk setiap perubahan data Executive Dashboard, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M08-004. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Executive Dashboard bila relevan dengan proses bisnis.
FR-M08-005. Sistem harus menyediakan menu Clinic Dashboard dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M08-006. Sistem harus menghubungkan Clinic Dashboard dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M08-007. Sistem harus mencatat audit trail untuk setiap perubahan data Clinic Dashboard, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M08-008. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Clinic Dashboard bila relevan dengan proses bisnis.
FR-M08-009. Sistem harus menyediakan menu KPI Cards dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M08-010. Sistem harus menghubungkan KPI Cards dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M08-011. Sistem harus mencatat audit trail untuk setiap perubahan data KPI Cards, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M08-012. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk KPI Cards bila relevan dengan proses bisnis.
FR-M08-013. Sistem harus menyediakan menu Trend Charts dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M08-014. Sistem harus menghubungkan Trend Charts dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M08-015. Sistem harus mencatat audit trail untuk setiap perubahan data Trend Charts, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M08-016. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Trend Charts bila relevan dengan proses bisnis.
FR-M08-017. Sistem harus menyediakan menu Alert Center dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M08-018. Sistem harus menghubungkan Alert Center dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M08-019. Sistem harus mencatat audit trail untuk setiap perubahan data Alert Center, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M08-020. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Alert Center bila relevan dengan proses bisnis.
FR-M08-021. Sistem harus menyediakan menu Drill Down dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M08-022. Sistem harus menghubungkan Drill Down dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M08-023. Sistem harus mencatat audit trail untuk setiap perubahan data Drill Down, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M08-024. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Drill Down bila relevan dengan proses bisnis.
FR-M08-025. Sistem harus menyediakan menu Export dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M08-026. Sistem harus menghubungkan Export dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M08-027. Sistem harus mencatat audit trail untuk setiap perubahan data Export, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M08-028. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Export bila relevan dengan proses bisnis.
FR-M08-029. Sistem harus menyediakan menu Personalized View dengan kemampuan tambah, lihat, ubah, arsip, pencarian, filter, dan ekspor sesuai permission.
FR-M08-030. Sistem harus menghubungkan Personalized View dengan workflow engine agar status draft, review, approval, revisi, publish, dan closed dapat dikonfigurasi dari UI.
FR-M08-031. Sistem harus mencatat audit trail untuk setiap perubahan data Personalized View, termasuk aktor, waktu, aksi, nilai lama, nilai baru, alamat IP, dan user agent.
FR-M08-032. Sistem harus mendukung lampiran, komentar, riwayat keputusan, dan referensi silang untuk Personalized View bila relevan dengan proses bisnis.
FR-M08-033. Sistem harus memvalidasi entitas Dashboard Widget menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M08-034. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Dashboard Widget dengan pagination standar.
FR-M08-035. Sistem harus memvalidasi entitas KPI Metric menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M08-036. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas KPI Metric dengan pagination standar.
FR-M08-037. Sistem harus memvalidasi entitas Dashboard Filter menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M08-038. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Dashboard Filter dengan pagination standar.
FR-M08-039. Sistem harus memvalidasi entitas Alert menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M08-040. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Alert dengan pagination standar.
FR-M08-041. Sistem harus memvalidasi entitas Saved View menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M08-042. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Saved View dengan pagination standar.
FR-M08-043. Sistem harus memvalidasi entitas Export Job menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M08-044. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Export Job dengan pagination standar.
FR-M08-045. Sistem harus memvalidasi entitas Metric Snapshot menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M08-046. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Metric Snapshot dengan pagination standar.
FR-M08-047. Sistem harus memvalidasi entitas Drilldown Query menggunakan aturan wajib, format, keunikan, relasi, status, dan masa berlaku yang dapat dikonfigurasi.
FR-M08-048. Sistem harus menyediakan endpoint API untuk membaca, memfilter, dan menelusuri riwayat entitas Drilldown Query dengan pagination standar.
FR-M08-049. Sistem harus memenuhi ketentuan khusus: Dashboard harus menyajikan data lintas modul dengan filter periode, unit, status, dan tingkat risiko.
FR-M08-050. Sistem harus memenuhi ketentuan khusus: Setiap angka ringkasan harus dapat ditelusuri ke data sumber melalui drill down.
FR-M08-051. Sistem harus memenuhi ketentuan khusus: Tampilan eksekutif harus ringkas namun tetap menyertakan alert kritis dan tren kinerja.
FR-M08-052. Sistem harus menyediakan kontrol operasional tambahan untuk Trend Charts, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M08-053. Sistem harus menyediakan kontrol operasional tambahan untuk Alert Center, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M08-054. Sistem harus menyediakan kontrol operasional tambahan untuk Drill Down, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M08-055. Sistem harus menyediakan kontrol operasional tambahan untuk Export, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M08-056. Sistem harus menyediakan kontrol operasional tambahan untuk Personalized View, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M08-057. Sistem harus menyediakan kontrol operasional tambahan untuk Executive Dashboard, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.
FR-M08-058. Sistem harus menyediakan kontrol operasional tambahan untuk Clinic Dashboard, termasuk validasi status, catatan wajib, pemberitahuan tenggat, dan pelaporan ringkas.

### Non Functional Requirement

NFR-M08-001. Keamanan: sistem harus memastikan akses berbasis role, permission, unit, dan status workflow diterapkan konsisten pada seluruh fitur modul Executive Dashboard & Clinic Dashboard.
NFR-M08-002. Keamanan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M08-003. Kinerja: sistem harus memastikan halaman daftar utama merespons cepat untuk data operasional besar pada seluruh fitur modul Executive Dashboard & Clinic Dashboard.
NFR-M08-004. Kinerja: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M08-005. Reliabilitas: sistem harus memastikan transaksi penting menggunakan database transaction dan mekanisme retry terukur pada seluruh fitur modul Executive Dashboard & Clinic Dashboard.
NFR-M08-006. Reliabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M08-007. Auditabilitas: sistem harus memastikan setiap perubahan bernilai kepatuhan memiliki jejak waktu, aktor, nilai lama, dan nilai baru pada seluruh fitur modul Executive Dashboard & Clinic Dashboard.
NFR-M08-008. Auditabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M08-009. Ketersediaan: sistem harus memastikan fitur kritikal tetap stabil saat Redis digunakan untuk queue, cache, dan session pada seluruh fitur modul Executive Dashboard & Clinic Dashboard.
NFR-M08-010. Ketersediaan: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M08-011. Skalabilitas: sistem harus memastikan desain tabel dan indeks mendukung pertumbuhan multi tahun pada seluruh fitur modul Executive Dashboard & Clinic Dashboard.
NFR-M08-012. Skalabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M08-013. Usability: sistem harus memastikan alur kerja utama dapat diselesaikan oleh pengguna non teknis dengan instruksi jelas pada seluruh fitur modul Executive Dashboard & Clinic Dashboard.
NFR-M08-014. Usability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M08-015. Interoperabilitas: sistem harus memastikan API menggunakan JSON, pagination, filter, dan kode error konsisten pada seluruh fitur modul Executive Dashboard & Clinic Dashboard.
NFR-M08-016. Interoperabilitas: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M08-017. Privasi: sistem harus memastikan data sensitif hanya tampil sesuai kebutuhan tugas dan kewenangan pada seluruh fitur modul Executive Dashboard & Clinic Dashboard.
NFR-M08-018. Privasi: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M08-019. Maintainability: sistem harus memastikan konfigurasi bisnis disimpan sebagai data, bukan konstanta tersembunyi di kode pada seluruh fitur modul Executive Dashboard & Clinic Dashboard.
NFR-M08-020. Maintainability: implementasi harus dapat diuji melalui skenario QA, log aplikasi, dan bukti audit yang dapat direproduksi.
NFR-M08-021. Database: query utama modul harus memiliki index yang sesuai untuk filter status, periode, unit, pemilik, dan workflow.
NFR-M08-022. Queue: proses notifikasi, ekspor, reminder, dan kalkulasi agregat harus berjalan asynchronous menggunakan Redis queue.
NFR-M08-023. Observability: error, retry, durasi proses, dan kegagalan integrasi harus tercatat dalam log terstruktur.

### Acceptance Criteria

AC-M08-001. Diberikan pengguna Direktur memiliki permission yang benar, ketika pengguna menjalankan proses Executive Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-001a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-002. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Clinic Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-002a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-003. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses KPI Cards, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-003a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-004. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Trend Charts, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-004a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-004b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M08-005. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Alert Center, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-005a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-006. Diberikan pengguna Komite Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Drill Down, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-006a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-007. Diberikan pengguna Pengawas memiliki permission yang benar, ketika pengguna menjalankan proses Export, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-007a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-008. Diberikan pengguna Direktur memiliki permission yang benar, ketika pengguna menjalankan proses Personalized View, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-008a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-008b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M08-009. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Executive Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-009a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-010. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Clinic Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-010a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-011. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses KPI Cards, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-011a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-012. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Trend Charts, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-012a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-012b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M08-013. Diberikan pengguna Komite Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Alert Center, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-013a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-014. Diberikan pengguna Pengawas memiliki permission yang benar, ketika pengguna menjalankan proses Drill Down, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-014a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-015. Diberikan pengguna Direktur memiliki permission yang benar, ketika pengguna menjalankan proses Export, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-015a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-016. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Personalized View, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-016a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-016b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M08-017. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Executive Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-017a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-018. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Clinic Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-018a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-019. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses KPI Cards, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-019a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-020. Diberikan pengguna Komite Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Trend Charts, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-020a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-020b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M08-021. Diberikan pengguna Pengawas memiliki permission yang benar, ketika pengguna menjalankan proses Alert Center, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-021a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-022. Diberikan pengguna Direktur memiliki permission yang benar, ketika pengguna menjalankan proses Drill Down, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-022a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-023. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Export, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-023a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-024. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Personalized View, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-024a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-024b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M08-025. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Executive Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-025a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-026. Diberikan pengguna PIC Unit memiliki permission yang benar, ketika pengguna menjalankan proses Clinic Dashboard, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-026a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-027. Diberikan pengguna Komite Mutu memiliki permission yang benar, ketika pengguna menjalankan proses KPI Cards, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-027a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-028. Diberikan pengguna Pengawas memiliki permission yang benar, ketika pengguna menjalankan proses Trend Charts, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-028a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-028b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.
AC-M08-029. Diberikan pengguna Direktur memiliki permission yang benar, ketika pengguna menjalankan proses Alert Center, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-029a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-030. Diberikan pengguna Kepala Klinik memiliki permission yang benar, ketika pengguna menjalankan proses Drill Down, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-030a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-031. Diberikan pengguna Manajemen memiliki permission yang benar, ketika pengguna menjalankan proses Export, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-031a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-032. Diberikan pengguna Admin Mutu memiliki permission yang benar, ketika pengguna menjalankan proses Personalized View, maka sistem menyimpan data valid, memperbarui status, dan menampilkan pesan sukses.
AC-M08-032a. Diberikan data wajib belum lengkap, ketika pengguna menyimpan, maka sistem menolak transaksi dan menampilkan pesan validasi yang dapat ditindaklanjuti.
AC-M08-032b. Diberikan proses melewati workflow, ketika status berubah, maka pihak berikutnya menerima notifikasi dan audit trail mencatat keputusan.

### Business Rules

BR-M08-001. Data Dashboard Widget pada Executive Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-002. Data KPI Metric pada Clinic Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-003. Data Dashboard Filter pada KPI Cards hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-003a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M08-004. Data Alert pada Trend Charts hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-005. Data Saved View pada Alert Center hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-005b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M08-006. Data Export Job pada Drill Down hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-006a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M08-007. Data Metric Snapshot pada Export hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-008. Data Drilldown Query pada Personalized View hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-009. Data Dashboard Widget pada Executive Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-009a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M08-010. Data KPI Metric pada Clinic Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-010b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M08-011. Data Dashboard Filter pada KPI Cards hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-012. Data Alert pada Trend Charts hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-012a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M08-013. Data Saved View pada Alert Center hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-014. Data Export Job pada Drill Down hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-015. Data Metric Snapshot pada Export hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-015a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M08-015b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M08-016. Data Drilldown Query pada Personalized View hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-017. Data Dashboard Widget pada Executive Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-018. Data KPI Metric pada Clinic Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-018a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M08-019. Data Dashboard Filter pada KPI Cards hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-020. Data Alert pada Trend Charts hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-020b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M08-021. Data Saved View pada Alert Center hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-021a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M08-022. Data Export Job pada Drill Down hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-023. Data Metric Snapshot pada Export hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-024. Data Drilldown Query pada Personalized View hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-024a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M08-025. Data Dashboard Widget pada Executive Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-025b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M08-026. Data KPI Metric pada Clinic Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-027. Data Dashboard Filter pada KPI Cards hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-027a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M08-028. Data Alert pada Trend Charts hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-029. Data Saved View pada Alert Center hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-030. Data Export Job pada Drill Down hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-030a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M08-030b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M08-031. Data Metric Snapshot pada Export hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-032. Data Drilldown Query pada Personalized View hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-033. Data Dashboard Widget pada Executive Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-033a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M08-034. Data KPI Metric pada Clinic Dashboard hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-035. Data Dashboard Filter pada KPI Cards hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-035b. Tenggat yang melewati batas harus memicu reminder, eskalasi, dan penanda overdue pada dashboard.
BR-M08-036. Data Alert pada Trend Charts hanya boleh diubah oleh pengguna yang memiliki role, permission, cakupan unit, dan status workflow yang sesuai.
BR-M08-036a. Perubahan setelah approval harus menghasilkan versi, revisi, atau catatan koreksi sesuai konfigurasi modul, bukan menghapus jejak keputusan sebelumnya.
BR-M08-KHS-01. Dashboard harus menyajikan data lintas modul dengan filter periode, unit, status, dan tingkat risiko.
BR-M08-KHS-02. Setiap angka ringkasan harus dapat ditelusuri ke data sumber melalui drill down.
BR-M08-KHS-03. Tampilan eksekutif harus ringkas namun tetap menyertakan alert kritis dan tren kinerja.

### UI Requirement

UI-M08-001. Modul harus menyediakan dashboard ringkasan untuk Executive Dashboard dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M08-001a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M08-001b. Halaman dashboard ringkasan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M08-002. Modul harus menyediakan halaman daftar untuk Clinic Dashboard dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M08-002a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M08-002b. Halaman halaman daftar harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M08-003. Modul harus menyediakan form tambah dan ubah untuk KPI Cards dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M08-003a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M08-003b. Halaman form tambah dan ubah harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M08-004. Modul harus menyediakan panel detail untuk Trend Charts dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M08-004a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M08-004b. Halaman panel detail harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M08-005. Modul harus menyediakan timeline workflow untuk Alert Center dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M08-005a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M08-005b. Halaman timeline workflow harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M08-006. Modul harus menyediakan tab lampiran untuk Drill Down dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M08-006a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M08-006b. Halaman tab lampiran harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M08-007. Modul harus menyediakan modal approval untuk Export dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M08-007a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M08-007b. Halaman modal approval harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M08-008. Modul harus menyediakan filter lanjutan untuk Personalized View dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M08-008a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M08-008b. Halaman filter lanjutan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M08-009. Modul harus menyediakan indikator status untuk Executive Dashboard dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M08-009a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M08-009b. Halaman indikator status harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M08-010. Modul harus menyediakan halaman konfigurasi untuk Clinic Dashboard dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M08-010a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M08-010b. Halaman halaman konfigurasi harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M08-011. Modul harus menyediakan wizard import untuk KPI Cards dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M08-011a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M08-011b. Halaman wizard import harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M08-012. Modul harus menyediakan halaman laporan untuk Trend Charts dengan desain responsif, breadcrumb, status badge, aksi utama yang jelas, dan empty state informatif.
UI-M08-012a. Komponen UI harus menampilkan hak aksi berdasarkan permission pengguna sehingga tombol yang tidak relevan tidak membingungkan pengguna.
UI-M08-012b. Halaman halaman laporan harus mendukung filter periode, status, unit, pemilik, kata kunci, serta reset filter.
UI-M08-999. Semua form modul Executive Dashboard & Clinic Dashboard harus menampilkan validasi inline, konfirmasi sebelum aksi irreversible, dan indikator penyimpanan saat proses berlangsung.

### API Requirement

API-M08-001. Endpoint /api/v1/dashboards/executive harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M08-001a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M08-001b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M08-002. Endpoint /api/v1/dashboards/clinic harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M08-002a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M08-002b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M08-003. Endpoint /api/v1/dashboards/widgets harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M08-003a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M08-003b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M08-004. Endpoint /api/v1/dashboards/alerts harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M08-004a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M08-004b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M08-005. Endpoint /api/v1/dashboards/exports harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M08-005a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M08-005b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M08-006. Endpoint /api/v1/dashboards/drilldowns harus tersedia pada versi /api/v1, menggunakan autentikasi token/session sesuai kanal, dan mematuhi policy authorization Laravel.
API-M08-006a. Request harus menerima JSON dengan validasi Form Request, error envelope standar, correlation id, dan pesan kesalahan berbahasa Indonesia.
API-M08-006b. Response daftar harus mendukung pagination, sorting, filtering, sparse field bila relevan, dan metadata total data.
API-M08-OP-001. Operasi create harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M08-OP-002. Operasi read harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M08-OP-003. Operasi update harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M08-OP-004. Operasi submit harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M08-OP-005. Operasi approve harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M08-OP-006. Operasi reject harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M08-OP-007. Operasi publish harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M08-OP-008. Operasi archive harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M08-OP-009. Operasi search harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M08-OP-010. Operasi export harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M08-OP-011. Operasi history harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M08-OP-012. Operasi configure harus mencatat audit trail, menghormati workflow, dan mengembalikan status HTTP yang konsisten untuk sukses maupun gagal.
API-M08-SEC-001. API modul Executive Dashboard & Clinic Dashboard harus menerapkan rate limiting, CSRF/session protection untuk web, dan pembatasan akses data lintas unit.

## 9. Kebutuhan Data dan Integrasi

DATA-001. Entitas User pada modul Core Platform harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-002. Entitas Document Version pada modul Document Management System harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-003. Entitas Audit Checklist pada modul Internal Audit harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-004. Entitas Fishbone Category pada modul CAPA harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-005. Entitas Mitigation pada modul Risk Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-006. Entitas Assessment pada modul Accreditation harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-007. Entitas Data Source pada modul Quality Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-008. Entitas Drilldown Query pada modul Executive Dashboard & Clinic Dashboard harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-009. Entitas User pada modul Core Platform harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-010. Entitas Document Version pada modul Document Management System harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-011. Entitas Audit Checklist pada modul Internal Audit harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-012. Entitas Fishbone Category pada modul CAPA harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-013. Entitas Mitigation pada modul Risk Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-014. Entitas Elemen pada modul Accreditation harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-015. Entitas Data Source pada modul Quality Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-016. Entitas Drilldown Query pada modul Executive Dashboard & Clinic Dashboard harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-017. Entitas User pada modul Core Platform harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-018. Entitas Document Version pada modul Document Management System harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-019. Entitas Audit Checklist pada modul Internal Audit harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-020. Entitas Fishbone Category pada modul CAPA harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-021. Entitas Mitigation pada modul Risk Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-022. Entitas Kriteria pada modul Accreditation harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-023. Entitas Data Source pada modul Quality Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-024. Entitas Drilldown Query pada modul Executive Dashboard & Clinic Dashboard harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-025. Entitas User pada modul Core Platform harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-026. Entitas Document Version pada modul Document Management System harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-027. Entitas Audit Checklist pada modul Internal Audit harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-028. Entitas Fishbone Category pada modul CAPA harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-029. Entitas Mitigation pada modul Risk Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-030. Entitas Standar pada modul Accreditation harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-031. Entitas Data Source pada modul Quality Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-032. Entitas Drilldown Query pada modul Executive Dashboard & Clinic Dashboard harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-033. Entitas User pada modul Core Platform harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-034. Entitas Document Version pada modul Document Management System harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-035. Entitas Audit Checklist pada modul Internal Audit harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-036. Entitas Fishbone Category pada modul CAPA harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-037. Entitas Mitigation pada modul Risk Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-038. Entitas Bab pada modul Accreditation harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-039. Entitas Data Source pada modul Quality Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-040. Entitas Drilldown Query pada modul Executive Dashboard & Clinic Dashboard harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-041. Entitas User pada modul Core Platform harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-042. Entitas Document Version pada modul Document Management System harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-043. Entitas Audit Checklist pada modul Internal Audit harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-044. Entitas Fishbone Category pada modul CAPA harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-045. Entitas Mitigation pada modul Risk Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-046. Entitas Accreditation Scheme pada modul Accreditation harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-047. Entitas Data Source pada modul Quality Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-048. Entitas Drilldown Query pada modul Executive Dashboard & Clinic Dashboard harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-049. Entitas User pada modul Core Platform harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-050. Entitas Document Version pada modul Document Management System harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-051. Entitas Audit Checklist pada modul Internal Audit harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-052. Entitas Fishbone Category pada modul CAPA harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-053. Entitas Mitigation pada modul Risk Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-054. Entitas Mock Survey pada modul Accreditation harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-055. Entitas Data Source pada modul Quality Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-056. Entitas Drilldown Query pada modul Executive Dashboard & Clinic Dashboard harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-057. Entitas User pada modul Core Platform harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-058. Entitas Document Version pada modul Document Management System harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-059. Entitas Audit Checklist pada modul Internal Audit harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-060. Entitas Fishbone Category pada modul CAPA harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-061. Entitas Mitigation pada modul Risk Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-062. Entitas Evidence pada modul Accreditation harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-063. Entitas Data Source pada modul Quality Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-064. Entitas Drilldown Query pada modul Executive Dashboard & Clinic Dashboard harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-065. Entitas User pada modul Core Platform harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-066. Entitas Document Version pada modul Document Management System harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-067. Entitas Audit Checklist pada modul Internal Audit harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-068. Entitas Fishbone Category pada modul CAPA harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-069. Entitas Mitigation pada modul Risk Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-070. Entitas Gap pada modul Accreditation harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-071. Entitas Data Source pada modul Quality Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-072. Entitas Drilldown Query pada modul Executive Dashboard & Clinic Dashboard harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-073. Entitas User pada modul Core Platform harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-074. Entitas Document Version pada modul Document Management System harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-075. Entitas Audit Checklist pada modul Internal Audit harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-076. Entitas Fishbone Category pada modul CAPA harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-077. Entitas Mitigation pada modul Risk Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-078. Entitas Assessment pada modul Accreditation harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-079. Entitas Data Source pada modul Quality Management harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.
DATA-080. Entitas Drilldown Query pada modul Executive Dashboard & Clinic Dashboard harus memiliki primary key, timestamp, soft delete bila relevan, created_by, updated_by, status, dan relasi audit trail.

## 10. Reporting dan Export

RPT-001. Sistem harus menyediakan laporan Core Platform dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-002. Sistem harus menyediakan laporan Document Management System dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-003. Sistem harus menyediakan laporan Internal Audit dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-004. Sistem harus menyediakan laporan CAPA dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-005. Sistem harus menyediakan laporan Risk Management dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-006. Sistem harus menyediakan laporan Accreditation dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-007. Sistem harus menyediakan laporan Quality Management dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-008. Sistem harus menyediakan laporan Executive Dashboard & Clinic Dashboard dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-009. Sistem harus menyediakan laporan Core Platform dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-010. Sistem harus menyediakan laporan Document Management System dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-011. Sistem harus menyediakan laporan Internal Audit dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-012. Sistem harus menyediakan laporan CAPA dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-013. Sistem harus menyediakan laporan Risk Management dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-014. Sistem harus menyediakan laporan Accreditation dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-015. Sistem harus menyediakan laporan Quality Management dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-016. Sistem harus menyediakan laporan Executive Dashboard & Clinic Dashboard dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-017. Sistem harus menyediakan laporan Core Platform dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-018. Sistem harus menyediakan laporan Document Management System dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-019. Sistem harus menyediakan laporan Internal Audit dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-020. Sistem harus menyediakan laporan CAPA dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-021. Sistem harus menyediakan laporan Risk Management dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-022. Sistem harus menyediakan laporan Accreditation dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-023. Sistem harus menyediakan laporan Quality Management dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-024. Sistem harus menyediakan laporan Executive Dashboard & Clinic Dashboard dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-025. Sistem harus menyediakan laporan Core Platform dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-026. Sistem harus menyediakan laporan Document Management System dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-027. Sistem harus menyediakan laporan Internal Audit dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-028. Sistem harus menyediakan laporan CAPA dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-029. Sistem harus menyediakan laporan Risk Management dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-030. Sistem harus menyediakan laporan Accreditation dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-031. Sistem harus menyediakan laporan Quality Management dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-032. Sistem harus menyediakan laporan Executive Dashboard & Clinic Dashboard dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-033. Sistem harus menyediakan laporan Core Platform dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-034. Sistem harus menyediakan laporan Document Management System dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-035. Sistem harus menyediakan laporan Internal Audit dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-036. Sistem harus menyediakan laporan CAPA dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-037. Sistem harus menyediakan laporan Risk Management dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-038. Sistem harus menyediakan laporan Accreditation dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-039. Sistem harus menyediakan laporan Quality Management dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-040. Sistem harus menyediakan laporan Executive Dashboard & Clinic Dashboard dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-041. Sistem harus menyediakan laporan Core Platform dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-042. Sistem harus menyediakan laporan Document Management System dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-043. Sistem harus menyediakan laporan Internal Audit dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-044. Sistem harus menyediakan laporan CAPA dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-045. Sistem harus menyediakan laporan Risk Management dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-046. Sistem harus menyediakan laporan Accreditation dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-047. Sistem harus menyediakan laporan Quality Management dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-048. Sistem harus menyediakan laporan Executive Dashboard & Clinic Dashboard dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-049. Sistem harus menyediakan laporan Core Platform dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-050. Sistem harus menyediakan laporan Document Management System dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-051. Sistem harus menyediakan laporan Internal Audit dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-052. Sistem harus menyediakan laporan CAPA dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-053. Sistem harus menyediakan laporan Risk Management dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-054. Sistem harus menyediakan laporan Accreditation dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-055. Sistem harus menyediakan laporan Quality Management dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-056. Sistem harus menyediakan laporan Executive Dashboard & Clinic Dashboard dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-057. Sistem harus menyediakan laporan Core Platform dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-058. Sistem harus menyediakan laporan Document Management System dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-059. Sistem harus menyediakan laporan Internal Audit dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.
RPT-060. Sistem harus menyediakan laporan CAPA dengan filter periode, unit, status, pemilik, format PDF/XLSX, nomor laporan, dan waktu generate.

## 11. Migration, Deployment, dan Operasional

OPS-001. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-002. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-003. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-004. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-005. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-006. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-007. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-008. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-009. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-010. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-011. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-012. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-013. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-014. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-015. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-016. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-017. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-018. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-019. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-020. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-021. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-022. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-023. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-024. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-025. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-026. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-027. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-028. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-029. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-030. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-031. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-032. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-033. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-034. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-035. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-036. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-037. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-038. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-039. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-040. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-041. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-042. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-043. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-044. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-045. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-046. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-047. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-048. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-049. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-050. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-051. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-052. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-053. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-054. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-055. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-056. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-057. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-058. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-059. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-060. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-061. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-062. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-063. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-064. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-065. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-066. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-067. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-068. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-069. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.
OPS-070. Implementasi harus menyediakan migration Laravel yang reversible bila memungkinkan, seed data referensi minimal, konfigurasi environment, queue worker, scheduler, backup PostgreSQL, dan prosedur rollback teruji.

## 12. Quality Assurance

QA-001. QA harus menguji modul Core Platform untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-002. QA harus menguji modul Document Management System untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-003. QA harus menguji modul Internal Audit untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-004. QA harus menguji modul CAPA untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-005. QA harus menguji modul Risk Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-006. QA harus menguji modul Accreditation untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-007. QA harus menguji modul Quality Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-008. QA harus menguji modul Executive Dashboard & Clinic Dashboard untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-009. QA harus menguji modul Core Platform untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-010. QA harus menguji modul Document Management System untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-011. QA harus menguji modul Internal Audit untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-012. QA harus menguji modul CAPA untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-013. QA harus menguji modul Risk Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-014. QA harus menguji modul Accreditation untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-015. QA harus menguji modul Quality Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-016. QA harus menguji modul Executive Dashboard & Clinic Dashboard untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-017. QA harus menguji modul Core Platform untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-018. QA harus menguji modul Document Management System untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-019. QA harus menguji modul Internal Audit untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-020. QA harus menguji modul CAPA untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-021. QA harus menguji modul Risk Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-022. QA harus menguji modul Accreditation untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-023. QA harus menguji modul Quality Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-024. QA harus menguji modul Executive Dashboard & Clinic Dashboard untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-025. QA harus menguji modul Core Platform untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-026. QA harus menguji modul Document Management System untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-027. QA harus menguji modul Internal Audit untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-028. QA harus menguji modul CAPA untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-029. QA harus menguji modul Risk Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-030. QA harus menguji modul Accreditation untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-031. QA harus menguji modul Quality Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-032. QA harus menguji modul Executive Dashboard & Clinic Dashboard untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-033. QA harus menguji modul Core Platform untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-034. QA harus menguji modul Document Management System untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-035. QA harus menguji modul Internal Audit untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-036. QA harus menguji modul CAPA untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-037. QA harus menguji modul Risk Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-038. QA harus menguji modul Accreditation untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-039. QA harus menguji modul Quality Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-040. QA harus menguji modul Executive Dashboard & Clinic Dashboard untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-041. QA harus menguji modul Core Platform untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-042. QA harus menguji modul Document Management System untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-043. QA harus menguji modul Internal Audit untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-044. QA harus menguji modul CAPA untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-045. QA harus menguji modul Risk Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-046. QA harus menguji modul Accreditation untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-047. QA harus menguji modul Quality Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-048. QA harus menguji modul Executive Dashboard & Clinic Dashboard untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-049. QA harus menguji modul Core Platform untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-050. QA harus menguji modul Document Management System untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-051. QA harus menguji modul Internal Audit untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-052. QA harus menguji modul CAPA untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-053. QA harus menguji modul Risk Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-054. QA harus menguji modul Accreditation untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-055. QA harus menguji modul Quality Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-056. QA harus menguji modul Executive Dashboard & Clinic Dashboard untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-057. QA harus menguji modul Core Platform untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-058. QA harus menguji modul Document Management System untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-059. QA harus menguji modul Internal Audit untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-060. QA harus menguji modul CAPA untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-061. QA harus menguji modul Risk Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-062. QA harus menguji modul Accreditation untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-063. QA harus menguji modul Quality Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-064. QA harus menguji modul Executive Dashboard & Clinic Dashboard untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-065. QA harus menguji modul Core Platform untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-066. QA harus menguji modul Document Management System untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-067. QA harus menguji modul Internal Audit untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-068. QA harus menguji modul CAPA untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-069. QA harus menguji modul Risk Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-070. QA harus menguji modul Accreditation untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-071. QA harus menguji modul Quality Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-072. QA harus menguji modul Executive Dashboard & Clinic Dashboard untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-073. QA harus menguji modul Core Platform untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-074. QA harus menguji modul Document Management System untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-075. QA harus menguji modul Internal Audit untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-076. QA harus menguji modul CAPA untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-077. QA harus menguji modul Risk Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-078. QA harus menguji modul Accreditation untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-079. QA harus menguji modul Quality Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-080. QA harus menguji modul Executive Dashboard & Clinic Dashboard untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-081. QA harus menguji modul Core Platform untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-082. QA harus menguji modul Document Management System untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-083. QA harus menguji modul Internal Audit untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-084. QA harus menguji modul CAPA untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-085. QA harus menguji modul Risk Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-086. QA harus menguji modul Accreditation untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-087. QA harus menguji modul Quality Management untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-088. QA harus menguji modul Executive Dashboard & Clinic Dashboard untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-089. QA harus menguji modul Core Platform untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.
QA-090. QA harus menguji modul Document Management System untuk skenario happy path, validasi gagal, akses terlarang, workflow revisi, audit trail, notifikasi, ekspor, dan konsistensi API.

## 13. Lampiran Glosarium

GLO-001. Workflow: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-002. Evidence: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-003. CAPA: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-004. RCA: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-005. 5 Why: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-006. Fishbone: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-007. Risk Matrix: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-008. Self Assessment: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-009. Gap Analysis: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-010. Mock Survey: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-011. Readiness: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-012. RBAC: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-013. LDAP: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-014. Audit Trail: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-015. Version Control: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-016. Expiry: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-017. Indicator: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-018. Dashboard: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-019. Redis Queue: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-020. PostgreSQL Constraint: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-021. Workflow: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-022. Evidence: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-023. CAPA: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-024. RCA: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-025. 5 Why: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-026. Fishbone: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-027. Risk Matrix: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-028. Self Assessment: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-029. Gap Analysis: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-030. Mock Survey: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-031. Readiness: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-032. RBAC: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-033. LDAP: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-034. Audit Trail: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-035. Version Control: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-036. Expiry: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-037. Indicator: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-038. Dashboard: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-039. Redis Queue: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-040. PostgreSQL Constraint: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-041. Workflow: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-042. Evidence: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-043. CAPA: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-044. RCA: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-045. 5 Why: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-046. Fishbone: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-047. Risk Matrix: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-048. Self Assessment: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-049. Gap Analysis: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-050. Mock Survey: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-051. Readiness: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-052. RBAC: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-053. LDAP: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-054. Audit Trail: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-055. Version Control: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-056. Expiry: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-057. Indicator: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-058. Dashboard: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-059. Redis Queue: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-060. PostgreSQL Constraint: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-061. Workflow: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-062. Evidence: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-063. CAPA: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-064. RCA: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-065. 5 Why: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-066. Fishbone: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-067. Risk Matrix: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-068. Self Assessment: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-069. Gap Analysis: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-070. Mock Survey: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-071. Readiness: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-072. RBAC: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-073. LDAP: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-074. Audit Trail: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-075. Version Control: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-076. Expiry: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-077. Indicator: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-078. Dashboard: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-079. Redis Queue: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-080. PostgreSQL Constraint: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-081. Workflow: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-082. Evidence: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-083. CAPA: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-084. RCA: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-085. 5 Why: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-086. Fishbone: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-087. Risk Matrix: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-088. Self Assessment: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-089. Gap Analysis: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-090. Mock Survey: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-091. Readiness: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-092. RBAC: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-093. LDAP: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-094. Audit Trail: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-095. Version Control: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-096. Expiry: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-097. Indicator: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-098. Dashboard: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-099. Redis Queue: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
GLO-100. PostgreSQL Constraint: istilah operasional yang digunakan dalam SIMMAK PPKP dan harus dijelaskan di help text, dokumentasi admin, serta materi pelatihan pengguna.
