# Security Threat Model — Template

## A. Metadata
- Sistem/modul, versi/commit, environment, tanggal, penilai, pemilik proses.
- Cakupan, pengecualian, akses yang diberikan, dan jenis data yang digunakan.

## B. Aset dan aliran data
| Aset/aliran | Klasifikasi | Sumber | Tujuan | Akses yang diizinkan | Retensi/owner decision |
|---|---|---|---|---|---|
| Temuan audit | Terbatas | Auditor | Klinik/verifikator | RBAC + scope | Perlu keputusan |
| Bukti audit | Mungkin sensitif | Klinik/auditor | Private storage | Otorisasi per objek | Perlu keputusan |
| CAPA/verifikasi | Terbatas | PIC/verifikator | Database | Workflow + scope | Perlu keputusan |
| Laporan ekspor | Mengikuti sumber | SIMMAK | Pengguna berwenang | Scope sama dengan sumber | Perlu keputusan |

Lengkapi dari implementasi aktual; tabel ini bukan inventaris final.

## C. Trust boundaries
Petakan minimal browser↔Laravel; klinik A↔klinik B; Laravel↔PostgreSQL; aplikasi↔private storage; aplikasi↔identity provider; aplikasi↔notifikasi/ekspor; dev/staging↔production; admin teknis↔pemilik proses.

## D. Threats/abuse cases (STRIDE + privacy/operational risk)
| ID | Threat | Aset/dampak | Kontrol ada | Mitigasi/test | Risiko residual/status |
|---|---|---|---|---|---|
| TM-01 | IDOR/BOLA: user klinik A mengganti ID dan membaca temuan klinik B | Kebocoran lintas-klinik | Periksa aktual | Feature tests cross-clinic | |
| TM-02 | Request buatan melompati approval/verifikasi atau memalsukan status | Integritas hasil audit | Periksa transition service | Test status tampering | |
| TM-03 | File upload spoofed/berbahaya | Bukti/storage | Periksa upload validation | File-type/size/path tests | |
| TM-04 | Download/preview tanpa izin | Kebocoran bukti | Periksa private storage/routes | Anonymous/cross-clinic/revoked-access tests | |
| TM-05 | Ekspor mengandung data berlebih atau formula injection | Privasi/keamanan | Periksa export policy | Allow-list, masking, formula tests | |
| TM-06 | Secret masuk source control/log/prompt | Pengambilalihan layanan | Periksa konfigurasi tanpa membuka nilai | Secret scanning/redaction | |
| TM-07 | Admin teknis mengubah hasil final | Integritas audit | Periksa amendment workflow | Separation-of-duties tests | |
| TM-08 | Data sensitif tersalin ke backup/dev tanpa masking | Privasi | Periksa data flow | Data-copy/backup/restore review | |
| TM-09 | Mass assignment menaikkan role/scope/approver | Privilege escalation | Periksa request/model | Negative tests | |
| TM-10 | Error/notification/log membocorkan data pribadi atau konfigurasi | Privasi/infrastruktur | Periksa redaction | Log/error review | |

Tambahkan ancaman berdasarkan repository, konfigurasi, deployment, dan proses aktual. Jangan menyatakan mitigasi ada hanya karena nama middleware/menu tersedia.

## E. Risk decisions
| ID | Keputusan | Alasan | Pemilik berwenang | Tanggal/masa berlaku | Mitigasi sementara |
|---|---|---|---|---|---|

## F. Exit criteria
- Data flows dan trust boundaries penting terpetakan.
- Ancaman lintas-klinik, workflow, bukti, ekspor, kredensial, log, backup, dan privilege escalation dinilai.
- Critical/High dimitigasi dan diuji atau rilis diblokir/ada pengecualian formal.
- Risiko residual memiliki owner decision.
- Threat model diperbarui saat arsitektur, data, role, storage, API, atau deployment berubah.
