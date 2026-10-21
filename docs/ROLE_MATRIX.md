# Matriks Role dan Permission — SIMMAK Audit Internal

## 1. Role Awal

Berikut adalah role awal yang dapat dikonfigurasi. Role bukan struktur jabatan yang otomatis berlaku, tetapi rancangan awal yang dapat disesuaikan dengan kebijakan PPKP.

| No | Role | Deskripsi |
|----|------|-----------|
| 1 | Administrator Sistem (`super-admin`) | Akses penuh semua modul dan fitur |
| 2 | Kepala PPKP (`kepala-ppkp`) | Pimpinan berwenang, review dan persetujuan akhir |
| 3 | Koordinator Audit Internal (`koordinator-audit`) | Manajemen program dan penugasan audit |
| 4 | Ketua Tim Auditor (`ketua-tim-auditor`) | Manajemen tim, deklarasi independensi dan konflik |
| 5 | Auditor Internal (`auditor`) | Eksekusi audit, checklist, temuan (scope penugasan) |
| 6 | Reviewer Audit (`reviewer-audit`) | Review hasil eksekusi sebelum finalisasi |
| 7 | Verifikator Tindak Lanjut (`verifikator-tindak-lanjut`) | Verifikasi CAPA dan efektivitas |
| 8 | Kepala Klinik (`kepala-klinik`) | View data klinik sendiri, review temuan kliniknya |
| 9 | PIC Tindak Lanjut (`pic-tindak-lanjut`) | Eksekusi rencana CAPA |
| 10 | Pengamat / Pembaca Laporan (`pengamat`) | View-only laporan dan dashboard |

## 2. Permission Granular

Permission menggunakan konvensi `modul.aksi` dengan guard `web`.

### Audit Dashboard
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.dashboard.view` | Mengakses dashboard audit internal | super-admin, koordinator-audit, kepala-ppkp, kepala-klinik |

### Audit Program
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.program.view` | Melihat daftar program audit | semua role audit |
| `audit.program.create` | Membuat program audit baru | super-admin, koordinator-audit, pj-mutu |
| `audit.program.update` | Mengubah program audit | super-admin, koordinator-audit, pj-mutu |
| `audit.program.submit` | Mengajukan program untuk review | koordinator-audit, pj-mutu |
| `audit.program.approve` | Menyetujui program audit | super-admin, kepala-ppkp (segregasi tugas: tidak boleh yang membuat) |

### Audit Assignment
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.assignment.view` | Melihat penugasan audit | super-admin, koordinator-audit, kepala-klinik, auditor |
| `audit.assignment.manage` | Mengelola penugasan dan tim | super-admin, koordinator-audit, ketua-tim-auditor |

### Audit Standard / Instrument
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.standard.view` | Melihat standar dan instrumen | semua role audit |
| `audit.standard.manage` | Mengelola standar dan versi instrumen | super-admin, admin-mutu, pj-mutu |
| `audit.standard.review` | Review versi instrumen | admin-mutu, pj-mutu |
| `audit.standard.publish` | Menerbitkan versi instrumen | super-admin, admin-mutu |

### Audit Execution
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.execution.view` | Melihat eksekusi audit | semua role audit |
| `audit.execution.execute | Menjalankan eksekusi (checklist) | auditor |
| `audit.execution.submit` | Menyerahkan eksekusi untuk review | auditor |

### Audit Evidence
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.evidence.view` | Melihat daftar bukti audit | semua role audit |
| `audit.evidence.upload` | Mengunggah bukti audit | auditor, pj-mutu |
| `audit.evidence.download` | Mengunduh bukti audit | semua role audit (dengan otorisasi objek) |

### Audit Finding
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.finding.view` | Melihat temuan audit | semua role audit |
| `audit.finding.create` | Membuat temuan baru | auditor, pj-mutu |
| `audit.finding.update` | Mengubah temuan | auditor (scope sendiri), pj-mutu |
| `audit.finding.review` | Review temuan | reviewer-audit, kepala-klinik |
| `audit.finding.close` | Menutup temuan | kepala-klinik, verifikator-tindak-lanjut |
| `audit.finding.reopen` | Membuka kembali temuan | verifikator-tindak-lanjut, super-admin |

### CAPA
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.capa.view` | Melihat CAPA | semua role audit |
| `audit.capa.update` | Mengubah rencana CAPA | pic-tindak-lanjut, pj-mutu |
| `audit.capa.verify` | Memverifikasi CAPA | verifikator-tindak-lanjut |

### Reporting
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.report.publish` | Mengekspor laporan PDF/Excel | super-admin, admin-mutu, pj-mutu, kepala-klinik, kepala-ppkp |

### Audit Trail
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.log.view` | Melihat audit trail | super-admin, admin-mutu |

### Configuration
| Permission | Deskripsi | Role Default |
|------------|-----------|--------------|
| `audit.configuration.manage` | Manajemen konfigurasi audit | super-admin |

## 3. Prinsip Otorisasi

1. **Gabungan permission**: Hak akses adalah gabungan semua permission yang dimiliki melalui role.
2. **Scope klinik**: Pengguna hanya dapat mengakses objek klinik dalam scope yang ditetapkan (`user_clinic_scopes`).
3. **Penugasan auditor**: Auditor hanya dapat mengakses objek audit sesuai penugasan yang sah.
4. **Segregasi tugas**: Pimpinan tidak boleh mengubah bukti atau temuan yang telah difinalisasi. Admin sistem tidak otomatis memiliki kewenangan mengubah hasil audit.
5. **Verifikasi independen**: Pihak yang membuat temuan tidak boleh melakukan verifikasi akhir atas temuannya sendiri.
6. **Setiap operasi sensitif dicek di backend**: Hiding menu tidak cukup sebagai pengamanan.

## 4. Verifikasi Akses URL

Pengguna tidak dapat mengakses objek klinik lain dengan mengganti ID pada URL. Setiap controller memeriksa `organization_id` dan clinic scope sebelum mengembalikan data.