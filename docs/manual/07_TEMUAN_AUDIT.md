# Temuan Audit

## 1. Kegunaan
Melihat dan menindaklanjuti temuan hasil form pemeriksaan (ketidaksesuaian terhadap elemen penilaian instrumen).

## 2. Route
| Halaman | URL |
|---------|-----|
| Daftar temuan | `/findings` |
| Detail temuan | `/findings/{id}` |

Permission: `audit.finding.view` (buat dari form: `audit.finding.create`)

## 3. Siapa yang Menggunakan
| Peran | Akses |
|---------|-----|
| Auditor | Temuan dari penugasan yang diikutinya |
| Kepala Klinik / Auditi | Hanya temuan kliniknya |
| Ketua Tim Mutu / Ketua Tim Audit | Pantau sesuai wewenang |
| Super Admin | Akses sistem; tidak menutup hasil final sebagai admin sistem |

## 4. Cara Pengisian & Penggunaan

### 4.1 Asal temuan (jalur utama)
1. Auditor mengisi **nilai kesesuaian** dan **rekomendasi** di form pelaksanaan.
2. EP **tidak sesuai** atau **sesuai sebagian** otomatis muncul di **Temuan Audit**.
3. Status awal: **Dalam CAPA**. Kasus CAPA sudah terhubung — klinik tidak menunggu tombol “buat temuan” di lapangan.

### 4.2 Melihat temuan
1. Buka menu **Temuan Audit**.
2. Filter status, keparahan, atau klinik (sesuai cakupan akun).
3. Buka detail: kriteria (EP), rekomendasi, tautan CAPA.

### 4.3 Penutupan
Temuan **tidak tertutup** saat klinik mengunggah bukti.  
Penutupan terjadi setelah auditor yang ditugaskan memvalidasi tindak lanjut sebagai efektif (di modul CAPA).

## 5. Status yang dipakai di layar
Dalam CAPA → Menunggu verifikasi → Selesai.

Temuan `major` / `critical` yang masih terbuka tampil sebagai prioritas di dashboard audit.
