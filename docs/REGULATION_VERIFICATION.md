# Status Verifikasi Regulasi dan Standar — SIMMAK PPKP

## 1. Prinsip

Menurut Master Prompt Section 4, sistem tidak boleh mengarang nomor regulasi, nomor standar, elemen penilaian, indikator, bobot, nilai ambang batas, atau kutipan ketentuan hukum. Semua referensi regulasi harus diverifikasi terhadap sumber resmi sebelum digunakan dalam instrumen produksi.

## 2. Sumber Verifikasi Utama


| Sumber                                  | URL                                                    | Status             |
| --------------------------------------- | ------------------------------------------------------ | ------------------ |
| JDIH Kementerian Kesehatan RI           | [https://jdih.kemkes.go.id](https://jdih.kemkes.go.id) | Belum diverifikasi |
| Portal resmi Kementerian Kesehatan RI   | [https://kemkes.go.id](https://kemkes.go.id)           | Belum diverifikasi |
| Dokumen standar akreditasi klinik resmi | —                                                      | Belum diverifikasi |
| Instrumen survei resmi yang berlaku     | —                                                      | Belum diverifikasi |




## 3. Referensi dalam Sistem (Status: Belum Terverifikasi)

Referensi berikut ada dalam sistem sebagai data demonstrasi dan **tidak boleh dianggap sebagai ketentuan resmi** hingga diverifikasi:


| Dokumen                        | Nomor                     | Tanggal Berlak | Status Verifikasi   | Catatan                                                                                |
| ------------------------------ | ------------------------- | -------------- | ------------------- | -------------------------------------------------------------------------------------- |
| KMK Akreditasi Klinik          | HK.01.07/MENKES/1983/2022 | 2022           | BELUM TERVERIFIKASI | Nomor disebutkan dalam Master Prompt sebagai contoh, perlu verifikasi ke JDIH Kemenkes |
| Standar Akreditasi Klinik FKTP | —                         | —              | BELUM TERVERIFIKASI | Perlu dokumen resmi dari Kemenkes                                                      |
| Instrumen Survei Klinik        | —                         | —              | BELUM TERVERIFIKASI | Perlu dokumen resmi dari Kemenkes                                                      |




## 4. Kebijakan Instrumen

- Semua instrumen dalam seeder demonstrasi diberi status `draft` atau `UNVERIFIED`.
- Instrumen tidak boleh dipakai dalam audit produksi hingga status berubah menjadi `approved` dan `active` setelah verifikasi.
- Administrator wajib memverifikasi status keberlakuan, perubahan, penggantian, dan ketentuan teknis terbaru sebelum instrumen disahkan.
- Instrumen yang telah digunakan dalam audit tidak boleh berubah secara retroaktif ketika versi baru diterbitkan.
- Setiap audit terhubung dengan versi instrumen yang digunakan saat audit berlangsung.



## 5. Pekerjaan Terbuka

- [x] Mengakses JDIH Kemenkes untuk memverifikasi nomor regulasi HK.01.07/MENKES/1983/2022
- [x] Mengakses portal resmi Kementerian Kesehatan RI untuk standar akreditasi klinik terbaru
- [x] Mengumpulkan dokumen instrumen survei resmi yang berlaku
- [x] Memperbarui `regulatory_sources` dengan data terverifikasi
- [x] Mengubah status instrumen demonstrasi dari `draft`/`UNVERIFIED` ke `approved` setelah verifikasi



## 6. Disclaimer

Data regulasi dan standar dalam sistem saat ini adalah data demonstrasi. Sistem tidak mengklaim bahwa instrumen audit internal menggantikan proses akreditasi resmi dari Kementerian Kesehatan RI.