# Security Baseline — SIMMAK Audit Internal Klinik

## Tujuan
Baseline pemeriksaan keamanan untuk modul Audit Internal Klinik SIMMAK PPKP DKI Jakarta. Ini bukan sertifikasi, audit independen, atau pernyataan kepatuhan hukum. Nilai setiap kontrol berdasarkan repository, konfigurasi, dan bukti yang benar-benar tersedia.

## Prinsip wajib
- Secure by default, least privilege, defense in depth, privacy by design, dan fail closed.
- Server-side authorization pada setiap operasi: layar, API, unduhan, ekspor, pencarian, bulk action, queue/job.
- Isolasi data berdasarkan clinic scope, assignment scope, role/permission, dan workflow state.
- Minimalkan data pribadi dan kesehatan; gunakan data sintetis untuk pengujian.
- Perubahan keamanan harus additive, terukur, dan memiliki regression tests.

## Klasifikasi data awal
Klasifikasi ini usulan teknis, bukan penetapan resmi. Pemilik proses/pejabat berwenang harus mengesahkan klasifikasi, akses, retensi, dan pemusnahan.

| Kategori | Contoh | Kontrol minimum |
|---|---|---|
| Publik | Informasi yang disetujui untuk publikasi | Review publikasi |
| Internal | Jadwal audit, SOP internal | Autentikasi dan akses sesuai tugas |
| Terbatas | Temuan, CAPA, hasil verifikasi, dokumen internal | RBAC, scope, audit trail, ekspor terbatas |
| Sensitif/pribadi | Identitas pegawai, bukti yang berisi identitas pasien/data kesehatan, kredensial | Minimasi, masking, akses ketat, private storage, transfer/retensi terkontrol |

Jangan menganggap semua bukti audit bebas data kesehatan. Periksa alur dan contoh berkas secara aman tanpa menyalin data nyata ke laporan.

## Kontrol yang wajib ditinjau

### Identitas, autentikasi, sesi
- Gunakan identity source yang sudah ada; jangan membuat identity store kedua tanpa keputusan arsitektur.
- Periksa hashing password, reset password, session fixation, invalidasi logout, rotasi sesi setelah login/perubahan privilege, idle timeout, cookie Secure/HttpOnly/SameSite, dan HTTPS produksi.
- Evaluasi MFA/SSO sesuai kemampuan organisasi; jangan menganggap tersedia sebelum diverifikasi.
- Rate-limit login, reset password, verifikasi, dan endpoint sensitif; jangan membocorkan keberadaan akun.

### Otorisasi dan segregation of duties
- Gunakan Policy/Gate atau kontrol server-side setara untuk semua route/controller/action/API.
- Uji object-level authorization untuk audit, temuan, CAPA, bukti, pencarian, bulk action, unduhan, dan ekspor.
- Validasi role + permission + clinic scope + assignment scope + workflow state.
- Uji IDOR/BOLA dengan ID objek milik klinik lain.
- Jangan mempercayai `clinic_id`, `user_id`, `role`, `created_by`, `approved_by`, `status`, atau flag privileged dari browser tanpa validasi server.
- Cegah mass assignment; gunakan allow-list/Form Request.
- Admin teknis tidak otomatis berwenang mengubah hasil audit final.

### Input, output, API
- Validasi tipe, panjang, format, enum, rentang, relasi, dan batas ukuran di server.
- Gunakan ORM/query parameterization; jangan gabungkan input tak tepercaya ke SQL.
- Encode output sesuai konteks untuk mencegah XSS; hindari HTML mentah dari input.
- Terapkan CSRF untuk state-changing request berbasis cookie.
- Batasi CORS pada origin yang diperlukan; jangan wildcard bersama credentials.
- Terapkan authentication, authorization, rate limit, payload limits, pagination, timeout, dan error handling aman pada API.
- Periksa open redirect, SSRF, path traversal, insecure deserialization, dan kebocoran stack trace.

### Bukti, file upload/download, ekspor
- Simpan bukti di private storage; hindari public URL atau nama path yang dapat ditebak.
- Nama storage acak; validasi ukuran, ekstensi allow-list, MIME dan file signature bila relevan. Jangan percaya `Content-Type` dari klien saja.
- Pertimbangkan malware scanning sesuai fasilitas; catat sebagai PARTIAL/NOT TESTED jika belum ada.
- Setiap unduhan/preview/thumbnail/OCR harus melalui otorisasi pengguna, objek, scope klinik/penugasan, dan workflow.
- Ekspor menerapkan otorisasi yang sama dengan layar sumber, allow-list kolom, dan minimisasi data pribadi.
- Cegah formula injection pada CSV/XLSX untuk nilai yang diawali karakter formula seperti `=`, `+`, `-`, `@`, sesuai library/format.
- Batasi akses, lokasi sementara, retensi, dan penghapusan berkas sesuai kebijakan yang disahkan.

### Database, storage, backup
- Akun aplikasi PostgreSQL bukan superuser dan hanya memiliki privilege minimum.
- Batasi jaringan/port database dan storage; jangan expose tanpa kebutuhan.
- Gunakan constraints, foreign keys, transaksi, dan migrasi aman.
- Verifikasi TLS dan encryption-at-rest berdasarkan konfigurasi aktual; jangan klaim terenkripsi tanpa bukti.
- Backup memiliki akses terbatas, integritas, retensi, dan uji restore di lingkungan terpisah. Jangan restore dengan menimpa production.
- Hindari menyalin database production ke dev/test; jika perlu, masking/anonymization harus teruji.

### Logging, audit trail, privasi
- Catat actor, action, target, waktu, hasil, dan request/correlation ID untuk peristiwa penting.
- Jangan log password, token, cookie, session ID, secret, atau data kesehatan yang tidak diperlukan.
- Audit trail append-oriented dan perubahan/penghapusan dibatasi; akses log juga diawasi.
- Redaksi data sensitif pada error/log; jangan menampilkan stack trace, SQL, atau konfigurasi internal ke pengguna.
- Sediakan proses respons insiden/eskalasi berdasarkan kebijakan PPKP; jangan mengarang tenggat hukum.

### Konfigurasi, dependensi, deployment
- Debug off pada produksi; rahasia tidak masuk repository, frontend bundle, log, screenshot, prompt, atau laporan.
- Periksa file permissions, Docker/container privileges, exposed ports, reverse proxy headers, HTTPS, CORS, trusted hosts, volume mounts, dan web server serving `.env`/dump.
- Audit dependensi dan jalankan `composer audit` jika didukung; gunakan static analysis dan secret scanning bila tersedia.
- Jangan mengubah firewall, secret, DNS, production service, atau deployment tanpa izin dan rollback plan.
- Jangan menjalankan destructive scan, exploit, atau load test pada production tanpa persetujuan eksplisit dan scope aman.

## Risk rating dan keputusan rilis
Nilai Critical/High/Medium/Low dengan mempertimbangkan kerahasiaan, integritas, ketersediaan, privasi, dampak lintas-klinik, dan integritas audit. Setiap temuan mencakup ID, komponen, lingkungan, bukti tersanitasi, dampak, likelihood, mitigasi, owner, due date, retest, dan residual risk. Critical/High yang belum ditangani menjadi release blocker kecuali pejabat berwenang membuat keputusan risiko tertulis. Agent tidak boleh menerima risiko atas nama organisasi.

## Status hasil
`PASS`, `FAIL`, `PARTIAL`, `NOT TESTED`, `NOT APPLICABLE`, atau `NEEDS OWNER DECISION`. Nyatakan cakupan, metode, lingkungan, tanggal, keterbatasan, dan bukti. Jangan klaim “aman 100%”, “bebas kerentanan”, “lulus OWASP”, atau “patuh regulasi” berdasarkan pemeriksaan terbatas.
