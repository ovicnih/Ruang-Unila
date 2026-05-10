# BAB 1. PENDAHULUAN

---

## 1.1 Latar Belakang

Dokumen ini disusun sebagai Spesifikasi Kebutuhan Perangkat Lunak (SKPL / Software Requirements Specification - SRS) untuk sistem informasi **Ruang Unila**. Dokumen ini dibuat sebagai acuan resmi dalam:
- Tahapan analisis, desain, dan implementasi sistem
- Standar pengujian dan verifikasi fitur
- Dokumentasi teknis yang dapat dipertanggungjawabkan
- Acuan evaluasi oleh dosen penguji mata kuliah Rekayasa Perangkat Lunak
- Dasar pengembangan fitur selanjutnya

Sistem Ruang Unila dikembangkan untuk menjawab permasalahan utama di lingkungan Universitas Lampung:
1. Belum adanya platform informasi terpadu untuk seluruh kegiatan kampus
2. Informasi organisasi mahasiswa dan kegiatan UKM belum terpusat
3. Proses pendaftaran event kampus masih dilakukan secara manual
4. Kurangnya transparansi informasi antara pihak kampus dan mahasiswa
5. Belum adanya sistem review dan feedback untuk kegiatan akademik

---

## 1.2 Lingkup Masalah

### 1.2.1 Tujuan
- Membangun platform informasi digital terpadu untuk seluruh civitas akademika Unila
- Mempermudah distribusi informasi berita, pengumuman, dan event kampus
- Menyediakan sistem pendaftaran event yang terintegrasi dengan pembayaran
- Menciptakan ekosistem informasi yang transparan dan terukur
- Membuat sistem dengan hak akses bertingkat (Mahasiswa, Organisasi, Administrator)

### 1.2.2 Manfaat
✅ Untuk Mahasiswa:
- Mendapatkan informasi kampus secara real-time
- Mendaftar event dengan mudah tanpa harus datang langsung
- Mencari informasi kegiatan UKM dan organisasi mahasiswa

✅ Untuk Organisasi / UKM:
- Memiliki platform publikasi event mandiri
- Mengelola data peserta event secara terorganisir
- Melihat statistik jumlah pendaftar dan partisipan

✅ Untuk Pihak Kampus:
- Memantau seluruh kegiatan yang ada di lingkungan kampus
- Mendapatkan laporan statistik kegiatan mahasiswa
- Menyebarkan pengumuman resmi dengan cepat

### 1.2.3 Batasan
1. Sistem hanya dapat diakses oleh civitas akademika Universitas Lampung
2. Pembayaran event masih menggunakan pembayaran manual (upload bukti transfer)
3. Sistem tidak terintegrasi dengan SIAKAD Unila
4. Otentikasi menggunakan email internal Unila
5. Batas upload file maksimal 2MB per gambar

---

## 1.3 Definisi, Istilah dan Singkatan

| Istilah | Definisi |
|---|---|
| **Ruang Unila** | Nama sistem informasi yang sedang dikembangkan |
| **SKPL** | Spesifikasi Kebutuhan Perangkat Lunak |
| **SRS** | Software Requirements Specification |
| **MVC** | Model View Controller |
| **SDLC** | Software Development Life Cycle |
| **ERD** | Entity Relationship Diagram |
| **DFD** | Data Flow Diagram |
| **UML** | Unified Modeling Language |
| **Mahasiswa** | Pengguna level 1 - akses terbaca dan pendaftaran |
| **Organisasi** | Pengguna level 2 - akses publikasi dan manajemen event |
| **Admin** | Pengguna level 3 - akses penuh seluruh sistem |
| **UKM** | Unit Kegiatan Mahasiswa |
| **BEM** | Badan Eksekutif Mahasiswa |

---

## 1.4 Aturan Penomoran

Dokumen ini menggunakan aturan penomoran sebagai berikut:
1. Bab utama menggunakan nomor urut (1, 2, 3, ...)
2. Sub bab menggunakan penomoran bertingkat (1.1, 1.2, 1.2.1, ...)
3. Setiap diagram memiliki kode unik sesuai bab dan nomor urut
4. Tabel diberi nomor urut sesuai bab
5. Semua referensi antar bagian menggunakan nomor bab yang sesuai
6. Penomoran bersifat permanen dan tidak berubah selama masa pengembangan