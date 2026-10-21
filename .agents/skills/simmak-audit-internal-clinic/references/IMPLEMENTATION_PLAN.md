# Implementation Plan and Gates

## Gate 0 — Discovery/read-only
- Catat Git status, branch, perubahan lokal, framework/runtime, routes, migrations, models, policies, tests, deployment, storage, logs, and CI.
- Jalankan baseline tests tanpa reset DB; pisahkan kegagalan lama.
- Identifikasi sumber autentikasi, RBAC, clinic master, design system, storage, export, notification.
- Buat gap report, data classification, threat model, dan daftar keputusan terbuka.
- Jangan mengubah file sebelum baseline dan scope disepakati.

## Gate 1 — Design
- Finalisasi PRD, ERD, permission matrix, workflow, UI/UX, security baseline, acceptance tests.
- Verifikasi regulasi dari sumber resmi dan tandai sumber yang belum diverifikasi.
- Definisikan migration strategy, rollback, backup/restore dan deployment plan.

## Gate 2 — Foundation
- Integrasikan auth, RBAC, clinic/assignment scopes, audit trail, private storage.
- Buat migrasi additive/reversible dan test pada database terpisah.
- Uji object-level authorization sebelum membangun fitur utama.

## Gate 3 — Audit execution vertical slice
- Program/jadwal → penugasan → instrumen/checklist → respons/bukti → temuan.
- Tambahkan UI, validasi, policy, audit trail, dan feature tests bersamaan.

## Gate 4 — CAPA and closure
- Rencana tindakan, PIC, tenggat, bukti, verifikasi efektivitas, reopen/amendment.
- Terapkan separation of duties dan transisi status eksplisit.

## Gate 5 — Reports and operations
- Dashboard berdasarkan data aktual; PDF/Excel dengan scope dan privacy controls.
- Notifikasi tanpa data sensitif berlebihan; logging dan observability aman.

## Gate 6 — Security, regression, release readiness
- Jalankan acceptance/security test plan, migration upgrade test, backup/restore di non-production.
- Critical/High unresolved menjadi release blocker kecuali ada keputusan risiko formal pemilik berwenang.
- Laporkan perubahan file, perintah/test aktual, hasil, known limitations, rollback, dan keputusan terbuka.

## Setiap fase
- Satu vertical slice pada satu waktu.
- Jangan reset DB atau menghapus data; jangan overwrite perubahan pengguna.
- Daftar file terdampak dan risiko sebelum perubahan luas.
- Setelah implementasi tinjau Git diff dan tes regresi.
