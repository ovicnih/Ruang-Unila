# BAB 3.1 KESESUAIAN ANTARMUKA

---

## 4.1 Storyboard Awal Sistem

### Alur Navigasi Pengguna
```
Halaman Utama → Pilih Kategori → Lihat Detail → Login → Aksi Pengguna
      ↓
   Navigasi Utama
┌─────────────────────────┐
│ Beranda │ Berita │ Event │ Profil │ Login
└─────────────────────────┘
```

### Struktur Halaman
| Bagian | Deskripsi |
|---|---|
| **Header** | Logo sistem, menu navigasi, tombol login, pencarian |
| **Hero Section** | Informasi unggulan, berita terpopuler |
| **Main Content** | Daftar konten dengan layout grid |
| **Sidebar** | Kategori, statistik, trending |
| **Footer** | Informasi kampus, link penting |

---

## 4.2 Design System

### Warna Utama
| Warna | Kode Hex | Penggunaan |
|---|---|---|
| 🔴 Merah Utama | `#9E0000` | Brand color, tombol utama |
| ⚫ Hitam | `#121212` | Teks utama |
| ⚪ Abu Muda | `#F5F5F5` | Background |
| 🔘 Abu | `#666666` | Teks sekunder |

### Tipografi
```
Font Utama: Manrope (Google Fonts)
- Heading: 700 / 600
- Body: 400 / 500
- Skala ukuran: 12px, 14px, 16px, 18px, 24px, 32px
```

---

## 4.3 Layout Responsif

| Breakpoint | Tampilan |
|---|---|
| < 480px | Mobile (1 kolom) |
| 481px - 768px | Tablet (2 kolom) |
| 769px - 1200px | Desktop (3 kolom) |
| > 1200px | Full HD (4 kolom) |

✅ Semua elemen antarmuka dapat diakses di segala ukuran layar

---

## 4.4 Aturan Antarmuka
1. ✅ Konsistensi warna dan tipografi di seluruh halaman
2. ✅ Semua tombol memiliki feedback hover dan aktif
3. ✅ Pesan error dan sukses ditampilkan dengan jelas
4. ✅ Loading state ditampilkan saat proses berlangsung
5. ✅ Tidak ada elemen yang tumpang tindih
6. ✅ Kontras teks dan background memenuhi standar WCAG

---

## 4.5 Mockup dan Wireframe

### Halaman Utama
```
┌─────────────────────────────────────────┐
│ LOGO                [SEARCH]     LOGIN  │
├─────────────────────────────────────────┤
│              HERO SECTION               │
├───────────────────┬─────────────────────┤
│                   │                     │
│   BERITA UTAMA    │    SIDEBAR          │
│                   │  - Kategori         │
│                   │  - Trending         │
│                   │  - Statistik        │
├───────────────────┴─────────────────────┤
│               FOOTER                    │
└─────────────────────────────────────────┘
```

✅ Semua antarmuka mengikuti desain dari Figma yang telah disetujui