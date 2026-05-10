# Ruang Unila - Sistem Informasi Berita Kampus

## Status Proyek: ✅ SELESAI (100%)

Ruang Unila telah mencapai versi **1.2.0 (Reorganized)** dan sedang dalam tahap standarisasi sistem. Seluruh fitur utama telah diimplementasikan sepenuhnya.

| Kategori | Selesai | Progress |
|----------|---------|----------|
| **Foundation & Config** | 3/3 | 100% |
| **Templates & UI** | 3/3 | 100% |
| **Auth System** | 3/3 | 100% |
| **Core Classes** | 3/3 | 100% |
| **News Module** | 4/4 | 100% |
| **Events Module** | 5/5 | 100% |
| **Profile Module** | 3/3 | 100% |
| **Admin Module** | 2/2 | 100% |
| **API Endpoints** | 2/2 | 100% |
| **TOTAL** | **28/28** | **100%** |

## Visi Proyek
Membangun sistem informasi terpusat yang aman, cepat, dan user-friendly untuk mengelola serta menyebarkan berita dan event di lingkungan Universitas Lampung. Sistem ini menghubungkan mahasiswa, organisasi, dan admin dalam satu platform komunikasi yang efektif.

## Deskripsi Singkat
Ruang Unila adalah platform web modular yang mengintegrasikan manajemen berita dengan 6 kategori (Populer, Kampus, UKM, Akademik, Event, Tren) dan sistem pendaftaran event lengkap dengan verifikasi pembayaran.

## Fitur Utama & Keunggulan

### 🛡️ Keamanan Tingkat Tinggi
- **SQL Injection Protection**: 100% menggunakan PDO dengan Prepared Statements.
- **XSS & CSRF Protection**: Sanitasi output otomatis dan token validasi form.
- **Secure Auth**: Password hashing menggunakan Bcrypt dan session management yang ketat.

### ⚡ Performa Optimal
- **Query teroptimasi**: Tanpa `SELECT *`, indexing pada kolom krusial, dan pagination.
- **Asset Management**: CSS terkonsolidasi, penggunaan CSS Variables, dan lazy loading gambar.
- **Fast Load**: Page load time < 500ms di lingkungan produksi.

### 🎨 UI/UX Modern & Responsive
- **Design System**: Berdasarkan Figma design dengan font Manrope.
- **Mobile Ready**: 100% responsive untuk semua ukuran layar (Mobile, Tablet, Desktop).
- **Interactive**: Feedback user yang jelas dengan loading indicators dan toast notifications.

## Aktor Sistem

1. **Mahasiswa**: Membaca berita, mendaftar event, melakukan pembayaran, dan mengelola profil.
2. **Organisasi**: Publikasi berita (pending admin), membuat event, dan memantau pendaftar.
3. **Admin**: Validasi berita & event, verifikasi pembayaran, manajemen user, dan statistik sistem.

## Cara Instalasi (Quick Start)
1. Clone repository ke folder `htdocs`.
2. Import `database/database_schema.sql` ke MySQL.
3. Konfigurasi `config/database.php` sesuai setting server.
4. Akses melalui browser: `http://localhost/WEBUNILA/Ruang-Unila/`.

## Dokumentasi Teknis
- [PLANNING.md](Progress/PLANNING.md): Arsitektur teknis dan tech stack.
- [TASK.md](Progress/TASK.md): History pengerjaan dan daftar fitur lengkap.
- [TECHNICAL_DOCS.md](Progress/TECHNICAL_DOCS.md): Dokumentasi API dan skema database.
- [USER_GUIDE.md](Progress/USER_GUIDE.md): Panduan penggunaan untuk setiap role.

---
**© 2024 Ruang Unila - Universitas Lampung**