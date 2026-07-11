# Akreditasi FKTP / FKTL

## 1. Kegunaan
Memantau kesiapan akreditasi klinik: struktur bab/standar, self assessment, analisis kesenjangan, survei simulasi, dan repositori bukti.

## 2. Route Utama
| Halaman | URL |
|---------|-----|
| Dashboard akreditasi | `/accreditation` |
| Self Assessment | `/accreditation/self-assessment` |
| Analisis Kesenjangan | `/accreditation/gaps` |
| Survei Simulasi | `/accreditation/mock-survey` |
| Repositori Bukti | `/accreditation/evidence` |
| Standar | `/accreditation/standards` |

Permission: `accreditation.view` / `accreditation.manage`

## 3. Siapa yang Menggunakan
| Role | Akses |
|------|-------|
| super-admin, admin-mutu, pj-mutu, tim-akreditasi | View + Manage |
| kepala-klinik, staff | View |
| auditor | Tidak |

Landing page khusus **tim-akreditasi**: `/accreditation`.

## 4. Cara Pengisian & Penggunaan

### 4.1 Dashboard
1. Buka **Akreditasi**.
2. Baca skor kesiapan, progres per bab, ringkasan gap.
3. Gunakan tree **Struktur Akreditasi** (kiri) untuk navigasi bab/standar.
4. Pantau linimasa menuju survei dan aktivitas terkini.

### 4.2 Self Assessment
1. Buka **Self Assessment**.
2. Isi skor/penilaian per standar (per bab).
3. Simpan. Skor keseluruhan mempengaruhi dashboard kesiapan.

### 4.3 Analisis Kesenjangan (Gap)
1. Buka **Gaps**.
2. Filter severity: kritikal / mayor / minor.
3. Tindak lanjuti gap (status, PIC) hingga ditutup.
4. Gap kritikal diprioritaskan sebelum survei.

### 4.4 Survei Simulasi (Mock Survey)
1. Buka **Mock Survey**.
2. Catat tanggal, surveor, skor, temuan simulasi.
3. Gunakan hasil untuk perbaikan sebelum survei resmi.

### 4.5 Repositori Bukti
1. Buka **Evidence**.
2. Untuk setiap item bukti yang diperlukan, **tautkan dokumen DMS** yang sudah published.
3. Status berubah menjadi terunggah/terverifikasi.
4. Kelengkapan upload mempengaruhi skor kesiapan.

### 4.6 Standar
1. Buka **Standards** untuk melihat hierarki bab → standar FKTP/FKTL.
2. Digunakan sebagai acuan pengisian SA dan bukti.

## 5. Alur Kerja Disarankan
Persiapan → Self Assessment → Gap Analysis → Upload Bukti → Mock Survey → Perbaikan → Survei Akreditasi.
