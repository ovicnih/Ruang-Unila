# BAB 3.4.3 DIAGRAM ACTIVITY

---

## 10.1 Alur Kerja Utama Sistem

### Diagram Activity: Pendaftaran Event
```mermaid
flowchart TD
    start([Start]) --> A[Buka Halaman Event]
    A --> B{Sudah Login?}
    B -->|Tidak| C[Redirect ke Login]
    C --> D[Login Sistem]
    D --> A
    B -->|Ya| E[Klik Tombol Daftar]
    E --> F{Event Masih Buka?}
    F -->|Tidak| G[Tampilkan Pesan Error]
    G --> end([End])
    F -->|Ya| H[Form Konfirmasi]
    H --> I[Konfirmasi Pendaftaran]
    I --> J{Simpan Data Peserta}
    J -->|Berhasil| K[Upload Bukti Pembayaran]
    K --> L[Menunggu Verifikasi]
    L --> M[Organisasi Verifikasi]
    M --> N[Status: Terverifikasi]
    N --> end
```

---

## 10.2 Alur Publikasi Konten

```mermaid
flowchart TD
    start([Start]) --> A[Organisasi Login]
    A --> B[Buat Konten Baru]
    B --> C[Isi Form Konten]
    C --> D[Upload Gambar]
    D --> E[Simpan Sebagai Draft]
    E --> F[Ajukan Publikasi]
    F --> G[Admin Menerima Notifikasi]
    G --> H{Approval Admin}
    H -->|Ditolak| I[Kembalikan dengan Catatan]
    I --> J[Organisasi Edit Ulang]
    J --> F
    H -->|Disetujui| K[Konten Tayang Publik]
    K --> end([End])
```

---

## 10.3 Daftar Diagram Activity

| No | Nama Alur | Jumlah Langkah |
|---|---|---|
| 1 | Alur Login Sistem | 5 langkah |
| 2 | Alur Pendaftaran Event | 12 langkah |
| 3 | Alur Publikasi Konten | 10 langkah |
| 4 | Alur Verifikasi Pembayaran | 7 langkah |
| 5 | Alur Manajemen Pengguna | 8 langkah |

✅ Total 5 diagram activity utama

---

## 10.4 Aturan Alur Sistem
✅ Setiap alur memiliki pengecekan kondisi di setiap langkah  
✅ Setiap error ditangani dengan pesan yang jelas  
✅ Tidak ada alur yang buntu tanpa penanganan  
✅ Semua aksi membutuhkan autentikasi yang sesuai  
✅ Setiap perubahan data tercatat di log sistem

---

## 10.5 Skenario Error Handling

Setiap alur memiliki penanganan untuk:
❌ Koneksi database gagal  
❌ Hak akses tidak cukup  
❌ Data tidak ditemukan  
❌ Input user tidak valid  
❌ Batasan sistem terlampaui