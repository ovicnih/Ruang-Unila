# BAB 2. DESKRIPSI UMUM PERANGKAT LUNAK

---

## 2.1 Deskripsi Umum Sistem

**Ruang Unila** adalah sistem informasi berbasis web yang dikembangkan khusus untuk lingkungan Universitas Lampung. Sistem ini berfungsi sebagai portal informasi terpadu yang mengintegrasikan seluruh aspek kehidupan kampus dalam satu platform.

Sistem ini memungkinkan:
- Publikasi berita dan pengumuman resmi kampus
- Pengelolaan dan pendaftaran event secara online
- Database organisasi mahasiswa dan UKM
- Sistem kategori informasi: Populer, Kampus, UKM, Akademik, Event, Tren
- Manajemen pengguna dengan 3 level hak akses
- Sistem approval untuk konten yang dipublikasikan
- Statistik dan laporan kegiatan kampus

Sistem dikembangkan dengan pendekatan native PHP tanpa menggunakan framework eksternal agar mudah dipahami dan dimodifikasi oleh anggota tim pengembangan.

---

## 2.2 Karakteristik Pengguna

### 2.2.1 User Stories

#### ✅ Sebagai Mahasiswa:
```
Sebagai Mahasiswa, saya ingin:
1. Melihat berita dan pengumuman kampus terbaru
2. Mencari event yang sesuai dengan minat saya
3. Mendaftar event secara online tanpa harus datang
4. Melihat profil organisasi dan UKM
5. Mengupdate profil pribadi saya
6. Menerima notifikasi mengenai kegiatan baru
```

#### ✅ Sebagai Organisasi / UKM:
```
Sebagai Pengelola Organisasi, saya ingin:
1. Mempublikasikan event kegiatan kami
2. Melihat daftar peserta yang mendaftar
3. Mengkonfirmasi pembayaran pendaftaran
4. Melihat statistik jumlah pengunjung event
5. Mengedit dan menghapus konten yang kami buat
```

#### ✅ Sebagai Administrator:
```
Sebagai Administrator, saya ingin:
1. Mengelola seluruh pengguna sistem
2. Melakukan approval terhadap konten yang dipublikasikan
3. Melihat laporan statistik keseluruhan sistem
4. Mengelola kategori dan pengaturan sistem
5. Menghapus konten yang melanggar aturan
```

---

## 2.3 Batasan

### Batasan Fungsional:
1. Sistem tidak melakukan pembayaran otomatis, hanya verifikasi bukti transfer
2. Tidak ada fitur chat atau pesan antar pengguna
3. Upload file hanya mendukung format gambar (JPG, PNG)
4. Tidak ada fitur notifikasi push, hanya notifikasi di halaman
5. Laporan statistik hanya tersedia untuk level admin dan organisasi

### Batasan Teknis:
1. Sistem berjalan di web browser, tidak ada aplikasi mobile
2. Minimum resolusi layar yang didukung: 360px
3. Batas upload gambar: 2MB per file
4. Session login berlaku selama 24 jam
5. Sistem dioptimalkan untuk XAMPP dengan PHP 8.0+

---

## 2.4 Lingkungan Operasi

### Lingkungan Server:
| Komponen | Spesifikasi Minimum |
|---|---|
| Sistem Operasi | Windows 10 / Linux Ubuntu 20.04+ |
| Web Server | Apache 2.4+ |
| Database | MySQL 8.0+ / MariaDB 10.5+ |
| PHP Version | PHP 8.0 atau lebih tinggi |
| PHP Extensions | PDO, GD, CURL, JSON |
| RAM | Minimal 512 MB |
| Storage | Minimal 1 GB untuk file upload |

### Lingkungan Client:
| Komponen | Spesifikasi |
|---|---|
| Browser | Chrome 90+, Firefox 88+, Edge 90+, Safari 14+ |
| Resolusi Layar | Minimal 360px (mobile friendly) |
| Koneksi Internet | Minimal 1 Mbps |
| Javascript | Harus diaktifkan |

---

## 2.5 Referensi

1. Mata Kuliah Rekayasa Perangkat Lunak - Universitas Lampung
2. Buku "Software Engineering" - Ian Sommerville Edisi 10
3. Panduan Penulisan Dokumen SKPL - Jurusan Ilmu Komputer Unila
4. Design System Figma Ruang Unila
5. Framework CSS Modern Web Design Guidelines