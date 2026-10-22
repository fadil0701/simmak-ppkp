# ADR-0013 — Unit/klinik sasaran audit dan katalog peran

Status: Accepted  
Date: 2026-10-22

## Decision
- Satu unit `clinic_type = pusat` bernama Kantor Pusat (Manajemen) tidak menjadi objek audit (`is_auditable = false`). Perannya memantau kegiatan audit dan mengelola sasaran.
- Klinik utama/pratama adalah sasaran audit dan dapat ditambah, dinonaktifkan, atau dihapus (kecuali unit pusat).
- Master jabatan tidak menentukan role. Role resmi: super-admin, admin-sistem, kepala-pusat, kasubbag-tu, ketua-tim-mutu, ketua-tim-audit, ketua-tim-akreditasi, auditor, kepala-klinik, auditi.
- Peran lama (`admin-mutu`, `pj-mutu`, `tim-akreditasi`, `staff`) tetap ada sebagai alias kompatibilitas tes/akun, dipetakan ke peran baru.

## Consequence
Hak akses tetap permission + scope klinik + alur kerja. Super Admin punya akses teknis penuh; Admin Sistem tidak otomatis menyetujui hasil audit.
