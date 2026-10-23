# Audit Internal

## 1. Kegunaan
Merencanakan dan melaksanakan audit internal klinik: program, penugasan pegawai, form pemeriksaan sesuai instrumen, lalu temuan dan laporan.

## 2. Route Utama
| Halaman | URL |
|---------|-----|
| Standar & Instrumen | `/audit/standards` |
| Program Audit | `/audit/programs` |
| Penugasan Auditor | `/audit/assignments` |
| Pelaksanaan Audit | `/audit/executions` |
| Form pemeriksaan | `/audit/executions/{id}/checklist` |
| Laporan | `/audit/reports` |

Permission: `audit.view`, plus izin langkah (`audit.assignment.manage`, `audit.execution.execute`, …)

## 3. Siapa yang Menggunakan
| Peran | Akses |
|---------|-----|
| Ketua Tim Audit / Ketua Tim Mutu | Program, penugasan, pantau pelaksanaan |
| Auditor | Hanya pelaksanaan yang ditugaskan; isi form EP |
| Kepala Klinik / Auditi | Lihat hasil kliniknya; tidak mengisi EP |
| Super Admin | Akses sistem; bukan pejabat penutup hasil audit |

Rincian layar: `docs/UI_PELAKSANAAN_AUDIT_TEMUAN_CAPA.md`.

## 4. Cara Pengisian & Penggunaan

### 4.1 Penugasan
1. Buka **Penugasan Auditor**.
2. Isi kode, judul, program yang sudah disetujui.
3. Pilih **klinik/unit yang diaudit**, **ketua tim (nama pegawai)**, tanggal, dan anggota tim.
4. Ajukan dan aktifkan setelah deklarasi konflik selesai.

### 4.2 Pelaksanaan (form instrumen)
1. Buka **Pelaksanaan Audit** — hanya penugasan yang Anda ikuti.
2. Buat eksekusi: pilih penugasan aktif dan versi instrumen.
3. **Mulai Audit**, lalu isi per EP:
   - **Nilai kesesuaian** (sesuai / sesuai sebagian / tidak sesuai / tidak berlaku)
   - **Rekomendasi** (wajib jika tidak sesuai atau sesuai sebagian)
4. Kolom EP, elemen penilaian, dan kelengkapan bukti berasal dari instrumen — jangan diubah di lapangan.
5. **Simpan Tabel**. EP tidak sesuai otomatis menjadi temuan dan CAPA untuk klinik.
6. Angka progres adalah kelengkapan pengisian, bukan skor kepatuhan Kemenkes.

### 4.3 Laporan Audit
1. Buka **Laporan Audit** setelah siklus berjalan.
2. Unduh PDF/Excel sesuai wewenang. Dokumen ini bukti audit internal PPKP, bukan sertifikat akreditasi.

## 5. Hubungan Modul Lain
Penugasan → Pelaksanaan → Temuan → CAPA → Laporan → Dashboard.
