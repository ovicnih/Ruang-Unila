# BAB 3.4.1 DIAGRAM USE CASE

---

## 8.1 Diagram Use Case Keseluruhan

```mermaid
flowchart TD
    subgraph Sistem Ruang Unila
        M[Mahasiswa]
        O[Organisasi]
        A[Administrator]

        %% Use Case Mahasiswa
        M --> UC1[Lihat Beranda]
        M --> UC2[Lihat Berita]
        M --> UC3[Lihat Event]
        M --> UC4[Cari Konten]
        M --> UC5[Daftar Event]
        M --> UC6[Upload Bukti Bayar]
        M --> UC7[Kelola Profil]
        M --> UC8[Login Sistem]

        %% Use Case Organisasi
        O --> UC8
        O --> UC9[Buat Berita]
        O --> UC10[Edit Berita]
        O --> UC11[Buat Event]
        O --> UC12[Edit Event]
        O --> UC13[Lihat Daftar Peserta]
        O --> UC14[Konfirmasi Pembayaran]
        O --> UC15[Lihat Statistik]

        %% Use Case Administrator
        A --> UC8
        A --> UC16[Approval Konten]
        A --> UC17[Kelola Pengguna]
        A --> UC18[Manajemen Kategori]
        A --> UC19[Lihat Laporan]
        A --> UC20[Pengaturan Sistem]
        A --> UC21[Hapus Konten]
    end
```

---

## 8.2 Daftar Use Case Berdasarkan Aktor

| Aktor | Jumlah Use Case | Daftar Aksi |
|---|---|---|
| 🎓 Mahasiswa | 8 | Lihat, Cari, Daftar, Profil |
| 🏢 Organisasi | 11 | Manajemen konten, Event, Peserta |
| 👑 Administrator | 13 | Kontrol penuh seluruh sistem |

✅ Total Use Case: **21 aksi**

---

## 8.3 Spesifikasi Use Case Detail

### UC05: Daftar Event
| Keterangan | Detail |
|---|---|
| Aktor | Mahasiswa |
| Prekondisi | Pengguna sudah login, Event masih buka |
| Alur Utama | 1. Pengguna buka halaman detail event<br>2. Klik tombol "Daftar"<br>3. Sistem menampilkan form konfirmasi<br>4. Pengguna konfirmasi pendaftaran<br>5. Sistem menyimpan data peserta |
| Postkondisi | Pengguna terdaftar sebagai peserta event |
| Alur Alternatif | Jika event sudah penuh → tampilkan pesan error |

### UC14: Konfirmasi Pembayaran
| Keterangan | Detail |
|---|---|
| Aktor | Organisasi |
| Prekondisi | Ada peserta yang sudah upload bukti bayar |
| Alur Utama | 1. Buka halaman daftar peserta<br>2. Lihat bukti pembayaran<br>3. Klik tombol "Verifikasi"<br>4. Sistem mengubah status peserta |
| Postkondisi | Peserta status menjadi "Terverifikasi" |

---

## 8.4 Generalisasi dan Hubungan

✅ Semua aktor melakukan use case `Login Sistem`  
✅ Organisasi memiliki seluruh hak akses Mahasiswa  
✅ Administrator memiliki seluruh hak akses Organisasi  
✅ Tidak ada use case yang dapat diakses tanpa autentikasi yang sesuai