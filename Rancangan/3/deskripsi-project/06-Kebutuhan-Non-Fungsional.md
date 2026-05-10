# BAB 3.3 KESESUAIAN NON FUNGSIONAL

---

## 6.1 Kebutuhan Performa

| Parameter | Nilai Minimum |
|---|---|
| ⏱️ Waktu muat halaman | < 2 detik |
| 📊 Jumlah pengguna bersamaan | 100 pengguna |
| 🔄 Waktu respon server | < 500 ms |
| 💾 Penggunaan memori server | < 256 MB |
| 📈 Throughput database | 100 query/detik |

✅ Semua kriteria ini sudah diuji dengan tools benchmarking

---

## 6.2 Kebutuhan Keamanan

✅ **SQL Injection Protection**: Semua query menggunakan Prepared Statements PDO  
✅ **XSS Protection**: Semua input user disanitasi sebelum ditampilkan  
✅ **CSRF Protection**: Setiap form memiliki token unik  
✅ **Password Hashing**: Menggunakan `password_hash()` dengan algoritma BCRYPT  
✅ **Session Security**: Session timeout 24 jam, tidak ada penyimpanan password plaintext  
✅ **Role Access Control**: Pembatasan akses ketat antar level pengguna  
✅ **File Upload Validation**: Validasi tipe dan ukuran file gambar  

---

## 6.3 Kebutuhan Usability

| Kriteria | Nilai |
|---|---|
| 🖱️ Jumlah klik untuk aksi utama | Maksimal 3 klik |
| 📝 Waktu belajar pengguna | < 10 menit |
| 📱 Kompatibilitas browser | Chrome 90+, Firefox 88+, Edge 90+ |
| 📏 Resolusi minimum | 360px (mobile) |
| 👁️ Kontras teks | Minimal 4.5:1 (standar WCAG 2.0) |

---

## 6.4 Kebutuhan Reliability

✅ **Uptime Sistem**: 99.5% dalam 1 bulan  
✅ **Waktu Recovery**: < 1 jam jika terjadi kegagalan  
✅ **Backup Database**: Otomatis setiap 24 jam  
✅ **Error Handling**: Tidak ada pesan error mentah yang ditampilkan ke user  
✅ **Log System**: Semua aktivitas user tercatat di database  

---

## 6.5 Kebutuhan Maintainability

✅ ✅ Modularitas: Setiap fitur terpisah dalam modul sendiri  
✅ ✅ Kode Dokumentasi: Setiap fungsi memiliki komentar penjelasan  
✅ ✅ Ukuran File: Maksimal 500 baris per file  
✅ ✅ Standar Kode: Mengikuti PSR-12 untuk PHP  
✅ ✅ Version Control: Menggunakan Git untuk seluruh perubahan kode  

---

## 6.6 Kebutuhan Portability

✅ Dapat berjalan di XAMPP, WAMP, dan LAMP  
✅ Tidak membutuhkan ekstensi PHP khusus selain standar  
✅ Dapat di-deploy di hosting manapun dengan PHP 8.0+  
✅ Tidak terikat pada sistem operasi tertentu  
✅ Database kompatibel dengan MySQL dan MariaDB