# Security Test Plan — SIMMAK Audit Internal Klinik

## Aturan aman
Gunakan local/test/staging dan data sintetis. Jangan jalankan exploit/destructive scan, migration production, atau tes invasif terhadap production tanpa izin tertulis, scope jelas, dan rollback plan. Catat hasil sebagai PASS / FAIL / PARTIAL / NOT TESTED / NOT APPLICABLE.

## 1. Authentication and session
- [ ] Halaman/API terlindungi tidak bisa diakses anonim.
- [ ] Login/reset/logout dan invalidasi sesi bekerja sesuai identity source yang ada.
- [ ] Cookie flags, HTTPS, session rotation, idle timeout, dan production config diverifikasi.
- [ ] Rate limit endpoint login/reset/sensitif; pesan tidak membocorkan keberadaan akun.
- [ ] CSRF aktif untuk state-changing browser requests berbasis cookie.

## 2. Authorization and scope — wajib
- [ ] Role tanpa permission ditolak.
- [ ] Permission tanpa clinic/assignment scope yang sesuai ditolak.
- [ ] Mengganti ID audit/temuan/CAPA/bukti pada URL/body/query/API/export tidak membuka objek klinik lain (IDOR/BOLA).
- [ ] API, bulk action, export, queue/job, dan scheduled task menerapkan scope yang benar.
- [ ] `clinic_id`, `created_by`, role, status final, approver, dan flag privileged tidak dapat diubah lewat mass assignment.
- [ ] Separation of duties mencegah self-approval/self-verification bila diwajibkan.
- [ ] Admin teknis tidak otomatis bisa mengubah hasil final.
- [ ] Pencabutan akses berlaku sesuai desain cache/session.

## 3. Workflow and integrity
- [ ] Status hanya berubah melalui transisi yang sah; request langsung tidak melompati approval/verifikasi.
- [ ] Hasil final tidak ditimpa diam-diam; reopen/amendment menyimpan alasan dan before/after.
- [ ] Operasi multi-langkah konsisten saat kegagalan; transaksi/constraint diuji.
- [ ] Audit trail mencatat actor/waktu/action/target/hasil dan tidak dapat diedit user biasa.

## 4. Input, output, API
- [ ] Validasi server menolak tipe/ukuran/rentang/enum tidak valid.
- [ ] ORM/parameterized query digunakan; SQL injection diuji aman.
- [ ] Stored/reflected XSS diuji pada temuan, komentar, nama file, dan impor.
- [ ] CORS hanya origin yang dibutuhkan; API punya auth, scope, rate limit, payload limits, pagination, error handling aman.
- [ ] Path traversal/open redirect/SSRF relevan dinilai.
- [ ] Production error tidak menampilkan stack trace, secret, SQL, atau konfigurasi.

## 5. Evidence upload/download and exports
- [ ] Bukti tidak dapat diakses lewat public URL tanpa otorisasi.
- [ ] Download/preview/thumbnail/OCR memeriksa object authorization, scope, assignment, dan workflow.
- [ ] File upload memvalidasi size, extension allow-list, MIME/signature relevan, random storage name, dan mencegah overwrite/path traversal.
- [ ] File berukuran berlebih atau tipe tidak diizinkan ditolak.
- [ ] Export menerapkan scope sumber, allow-list kolom, minimisasi/masking data pribadi.
- [ ] CSV/XLSX formula injection ditangani sesuai library.
- [ ] Temporary export files punya akses/retensi terbatas.
- [ ] Malware scanning ditandai PARTIAL/NOT TESTED jika belum tersedia.

## 6. Secrets, database, logs, backup, deployment
- [ ] Secret scanning tidak menemukan kredensial valid pada cakupan yang diperiksa; nilai secret tidak ditampilkan dalam laporan.
- [ ] `.env`, dump, credential, dan bukti tidak disajikan web server atau masuk Git.
- [ ] Debug off pada production.
- [ ] PostgreSQL app role bukan superuser dan punya privilege minimum.
- [ ] Port DB/storage hanya terbuka untuk jaringan yang dibutuhkan.
- [ ] Log tidak berisi password/token/cookie/session ID/secret/data pasien tak perlu.
- [ ] Backup access/retensi ditinjau dan restore diuji di lingkungan terpisah.
- [ ] HTTPS, proxy headers, secure cookies, trusted hosts, CORS, volume mounts, dan file permissions diverifikasi.

## 7. Dependency and code analysis
- [ ] `composer audit` dijalankan jika didukung; catat versi dan hasil.
- [ ] Test/lint/static analysis yang tersedia dijalankan.
- [ ] Secret/dependency scanner digunakan jika tersedia dan disetujui.
- [ ] Dependensi baru dinilai kebutuhan, advisory, pemeliharaan, dan kompatibilitas.

## 8. Evidence report
| ID | Kontrol/test | Environment | Bukti tersanitasi | Hasil | Risiko | Remediasi/owner | Retest |
|---|---|---|---|---|---|---|---|

Laporan harus mencantumkan scope, commit, environment, tes dijalankan/tidak dijalankan, hasil Critical/High/Medium/Low, keterbatasan, dan residual risk. Pemeriksaan terbatas bukan jaminan bebas kerentanan atau sertifikasi.
