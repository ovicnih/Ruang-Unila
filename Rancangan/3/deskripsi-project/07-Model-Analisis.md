# BAB 3.4 MODEL ANALISIS SISTEM

---

## 7.1 Pendekatan Analisis

Sistem ini dianalisis menggunakan pendekatan **Object Oriented Analysis (OOA)** dengan mengacu pada standar UML (Unified Modeling Language).

### Tahapan Analisis:
1. ✅ Identifikasi aktor dan use case
2. ✅ Identifikasi kelas dan objek utama
3. ✅ Identifikasi relasi antar objek
4. ✅ Pemodelan alur kerja sistem
5. ✅ Pemodelan aliran data
6. ✅ Perancangan struktur database

---

## 7.2 Daftar Aktor Sistem

| Aktor | Deskripsi | Hak Akses |
|---|---|---|
| 👤 **Mahasiswa** | Pengguna umum | Baca konten, daftar event |
| 🏢 **Organisasi** | Pengelola UKM / Lembaga | Buat konten, kelola event |
| 👑 **Administrator** | Pengelola sistem | Akses penuh seluruh fitur |
| 💻 **Sistem** | Proses otomatis sistem | Background proses |

---

## 7.3 Objek Utama Sistem

| Objek | Atribut Utama |
|---|---|
| **User** | id, nama, email, password, role, nim, avatar |
| **News** | id, judul, konten, gambar, penulis, kategori |
| **Event** | id, judul, deskripsi, tanggal, lokasi, biaya |
| **Category** | id, nama, slug, icon |
| **Participant** | id, id event, id user, bukti bayar, status |

---

## 7.4 Prinsip Perancangan

✅ **Single Responsibility Principle**: Setiap kelas hanya memiliki satu tanggung jawab  
✅ **Open/Closed Principle**: Sistem dapat dikembangkan tanpa mengubah kode yang ada  
✅ **Dependency Inversion**: Modul tingkat tinggi tidak bergantung pada modul rendah  
✅ **Separation of Concerns**: Antarmuka terpisah dari logika bisnis  
✅ **DRY (Don't Repeat Yourself)**: Tidak ada duplikasi kode  

---

## 7.5 Metodologi Pengembangan

Sistem dikembangkan dengan **metodologi Waterfall** dengan tahapan:
1. Analisis Kebutuhan
2. Perancangan Sistem
3. Implementasi Kode
4. Pengujian Sistem
5. Deployment dan Maintenance

Semua tahapan terdokumentasi dengan baik sesuai standar Rekayasa Perangkat Lunak.

---

## 7.6 Asumsi dan Kendala

### Asumsi:
- Semua pengguna memiliki email Unila yang valid
- Koneksi internet stabil untuk mengakses sistem
- Pengguna memiliki pengetahuan dasar menggunakan web browser

### Kendala:
- Waktu pengembangan terbatas (1 semester)
- Sumber daya pengembang terbatas (4 orang)
- Tidak ada akses ke API SIAKAD Unila