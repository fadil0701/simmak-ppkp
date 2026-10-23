# CAPA (Corrective & Preventive Action)

## 1. Kegunaan
Klinik menindaklanjuti temuan audit: rencana tindakan, unggah bukti, lalu auditor memvalidasi kesesuaian tindak lanjut.

## 2. Route
| Halaman | URL |
|---------|-----|
| Daftar CAPA | `/capa` |
| Detail CAPA | `/capa/{id}` |

Permission: `capa.view` / `capa.manage` / `audit.action.update`; validasi: `audit.capa.verify`

## 3. Siapa yang Menggunakan
| Peran | Akses |
|---------|-----|
| Auditi, Kepala Klinik/Unit | Tindak lanjut dan unggah bukti kliniknya |
| Auditor yang ditugaskan | Validasi kesesuaian tindak lanjut; bukan PIC klinik |
| PIC kasus | Tidak dapat memvalidasi kasusnya sendiri |
| Admin sistem | Tidak memvalidasi atau menutup hasil final |

## 4. Cara Pengisian & Penggunaan

### 4.1 Membuka kasus
Kasus utama dibuat otomatis saat auditor menyimpan EP tidak sesuai. Buka dari **CAPA** atau tautan di temuan.

### 4.2 Analisis akar masalah (opsional)
Di detail CAPA, isi **5 Why** dan/atau **Fishbone**, lalu simpan akar masalah.

### 4.3 Tindak lanjut klinik
1. Tambah tindakan korektif/pencegahan dan PIC jika perlu.
2. Isi **uraian tindak lanjut**.
3. **Unggah bukti** (PDF, gambar, atau berkas Office, maksimal 10 MB).
4. Kirim bukti. Status menjadi **Verifikasi**. Temuan **belum selesai**.

### 4.4 Validasi auditor
1. Auditor yang ditugaskan membuka kasus setelah bukti ada.
2. Isi tanggal, hasil, dan catatan:
   - **Efektif / diterima** → CAPA dan temuan **Selesai**
   - **Perlu perbaikan** atau **Tidak efektif** → kembali ke tindakan; klinik mengunggah bukti baru
3. Auditi tidak melihat tombol validasi.

## 5. Status Umum
Tindakan → Verifikasi → Selesai  

Melewati target tanpa selesai = **Terlambat** (indikator di daftar/dashboard, status tidak diubah otomatis).
