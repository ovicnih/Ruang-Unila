# Active Context - Ruang Unila

## Current Work Focus
- Bug fixes dan improvement untuk akses user berdasarkan role
- Database XAMPP sudah berhasil di-setup
- Siap untuk Fase 7: Integrasi & Testing

## Recent Changes
- ✅ Fixed permission halaman registrations.php - Organisasi sekarang bisa akses
- ✅ Added constant ROLE_ORGANIZATION di constants.php (American spelling alias)
- ✅ Fixed fungsi redirect() - Handle URL lengkap agar tidak double
- ✅ Fixed fungsi url() - Handle URL lengkap agar tidak double
- ✅ Created halaman my-registrations.php untuk mahasiswa lihat pendaftaran
- ✅ Removed emoticon dari tampilan (sesuai request user)
- ✅ Fixed error kolom verified_at - Removed from query (tidak ada di DB)
- ✅ Added error handling dan validation di my-registrations.php
- ✅ Implementasi Database.php dengan PDO dan prepared statements (singleton pattern)
- ✅ Implementasi constants.php dengan konfigurasi lengkap (227 baris)
- ✅ Implementasi session.php dengan keamanan (CSRF, flash messages, regenerate ID)
- ✅ Implementasi functions.php dengan utility functions (287 baris)
- ✅ Implementasi CSS (style.css) dari Figma design (527 baris)
- ✅ Semua rules (.clinerules) sudah dibaca dan dipahami
- ✅ Checkpoint documentation (TASK.md, PLANNING.md, README.md)
- ✅ Modul Auth login.php dengan validasi lengkap
- ✅ Modul Auth register.php dengan validasi lengkap
- ✅ Modul Auth logout.php dengan session destroy
- ✅ Classes User.php dengan CRUD operations
- ✅ Classes News.php dengan CRUD operations
- ✅ Classes Event.php dengan CRUD operations (271 baris)
- ✅ Modul News list.php dengan pagination dan kategori
- ✅ Modul News detail.php dengan view counter
- ✅ Modul News create.php dengan validasi dan upload gambar
- ✅ Modul News manage.php dengan admin controls
- ✅ Implementasi header.php dari Figma design
- ✅ Implementasi footer.php dari Figma design
- ✅ Update index.php dengan routing dan homepage
- ✅ Modul events/list.php - Daftar event dengan filter dan pagination (~200 baris)
- ✅ Modul events/detail.php - Detail event dengan countdown dan tombol pendaftaran (~280 baris)
- ✅ Modul events/register.php - Form pendaftaran event dengan validasi kuota dan deadline (~250 baris)
- ✅ Modul events/payment.php - Upload bukti pembayaran dengan preview (~230 baris)
- ✅ Modul profile/my-registrations.php - Daftar pendaftaran mahasiswa (~280 baris)

## Current Work Focus
- Database sudah berhasil di-setup di XAMPP
- Semua tabel dan data dummy sudah ada
- Siap untuk Fase 7: Integrasi & Testing

## Next Steps
- Test alur lengkap user journey
- Fix bugs dan issues
- Security testing
- Performance testing
- Update memory bank dan dokumentasi
- Fase 8: Dokumentasi & Deployment

## Important Patterns
- **Modular MVC-like architecture**: Model (classes/), View (includes/), Controller (modules/)
- **Prepared statements untuk keamanan**: Semua query menggunakan PDO
- **File maksimal 500 baris**: Semua file dipecah jika mendekati batas ✅ DITERAPKAN
- **Lazy loading untuk gambar**: Untuk halaman Populer dan Tren
- **6 Kategori Wajib**: Populer, Kampus, UKM, Akademik, Event, Tren
- **Desain dari Figma**: Font Manrope, warna primary #9E0000, background #F8F9FA

## Rules yang Harus Diterapkan
### Aturan Keamanan
1. Prepared Statements (PDO) untuk semua query database ✅
2. Validasi & Sanitasi semua input user ✅
3. Dilarang tulis password/API key di kode ✅
4. Otentikasi berlapis: Admin ≠ Organisasi ≠ Mahasiswa ✅

### Aturan Performa
1. Dilarang SELECT * - ambil kolom spesifik ✅
2. CSS efisien dengan Manrope font ✅
3. Lazy loading untuk gambar
4. Loop bersih tanpa rekursif berat ✅

### Golden Rules
1. File maksimal 500 baris ✅ DITERAPKAN di semua file baru
2. Update README.md, PLANNING.md, TASK.md ✅
3. Satu tugas per pesan ✅
4. Dokumentasi progresif (komentar inline) ✅
5. Unit test untuk setiap fungsi baru

### Konteks Spesifik Proyek
1. 6 kategori wajib: Populer, Kampus, UKM, Akademik, Event, Tren ✅
2. Setting Profile: User bisa ubah data diri dan foto profil
3. Flow Event: Publikasi → Pendaftaran → Pembayaran (Upload bukti/Konfirmasi) ✅ SEBAGIAN
4. Desain dari Figma: https://www.figma.com/design/Ny5pPVvqp7tetrEN0TY29p/MPI ✅

## Files yang Sudah Diimplementasi
### Config Files (3/3)
- config/database.php - PDO Database class dengan singleton pattern
- config/constants.php - Semua konstanta sistem (227 baris)
- config/session.php - Session management dengan keamanan

### Include Files (4/4) ✅ LENGKAP
- includes/functions.php - Utility functions (287 baris)
- includes/header.php - Template header dari Figma (✅ Selesai)
- includes/footer.php - Template footer dari Figma (✅ Selesai)
- includes/sidebar.php - Template sidebar

### Asset Files (2/3) ✅ BERTAMBAH
- assets/css/style.css - Main stylesheet dari Figma (527 baris)
- assets/js/main.js - JavaScript interaktivitas (~350 baris)

### Module Files (12/17) ✅ BERTAMBAH
- modules/auth/login.php - Login dengan validasi (✅ Selesai)
- modules/auth/register.php - Register dengan validasi (✅ Selesai)
- modules/auth/logout.php - Logout dengan session destroy (✅ Selesai)
- modules/news/list.php - Daftar berita dengan pagination (✅ Selesai)
- modules/news/detail.php - Detail berita dengan view counter (✅ Selesai)
- modules/news/create.php - Buat berita dengan upload (✅ Selesai)
- modules/news/manage.php - Kelola berita dengan admin controls (✅ Selesai)
- modules/events/list.php - Daftar event dengan filter (✅ Selesai, ~200 baris)
- modules/events/detail.php - Detail event dengan countdown (✅ Selesai, ~280 baris)
- modules/events/register.php - Form pendaftaran event (✅ Selesai, ~250 baris)
- modules/events/payment.php - Upload bukti pembayaran (✅ Selesai, ~230 baris)
- modules/profile/my-registrations.php - Daftar pendaftaran mahasiswa (✅ Selesai, ~280 baris)

### Class Files (3/5)
- classes/User.php - CRUD operations untuk user (✅ Selesai)
- classes/News.php - CRUD operations untuk news (✅ Selesai)
- classes/Event.php - CRUD operations untuk events (✅ Selesai, 271 baris)

### Entry Point (1/1) ✅ LENGKAP
- index.php - Homepage dengan routing dan integrasi data (✅ Selesai)

## Progress Fase 4 (Events)
| Task | Status | Baris |
|------|--------|-------|
| 4.1 Class Event | ✅ Selesai | 271 |
| 4.2 Modul Daftar Event (list.php) | ✅ Selesai | ~200 |
| 4.3 Modul Detail Event (detail.php) | ✅ Selesai | ~280 |
| 4.4 Modul Pendaftaran (register.php) | ✅ Selesai | ~250 |
| 4.5 Modul Pembayaran (payment.php) | ✅ Selesai | ~230 |
| 4.6 Modul Buat Event (create.php) | ✅ Selesai | ~350 |
| 4.7 Modul Validasi Admin (admin.php) | ✅ Selesai | ~400 |
| 4.8 Modul Verifikasi Pembayaran (admin_payments.php) | ✅ Selesai | ~450 |
| 4.9 Modul Status Pendaftaran (registrations.php) | ✅ Selesai | ~300 |

## FASE 4 SELESAI 100% ✅

## Progress Fase 5 (Profile)
| Task | Status | Baris |
|------|--------|-------|
| 5.1 Modul Lihat Profil (profile/index.php) | ✅ Selesai | ~280 |
| 5.2 Modul Edit Profil (profile/edit.php) | ✅ Selesai | ~250 |
| 5.3 Modul Upload Foto Profil (profile/photo.php) | ✅ Selesai | ~280 |
| 5.4 Modul Ubah Password (profile/password.php) | ✅ Selesai | ~200 |
| 5.5 Modul Pendaftaran Saya (profile/my-registrations.php) | ✅ Selesai | ~280 |

## FASE 5 SELESAI 100% ✅ (5 modul)

## Semua File Baru < 500 Baris ✅
- events/list.php: ~200 baris
- events/detail.php: ~280 baris
- events/register.php: ~250 baris
- events/payment.php: ~230 baris
- profile/my-registrations.php: ~280 baris

## Bug Fixes Terbaru
1. **Permission Issue**: Organisasi tidak bisa akses registrations.php → Fixed dengan tambah ROLE_ORGANIZATION
2. **URL Redirect Issue**: redirect() dan url() menghasilkan URL ganda → Fixed with cek http:// prefix
3. **Database Column Issue**: verified_at tidak ada di tabel → Removed from query
4. **HTML Rendering Issue**: Kode tercetak langsung ke layar → Fixed dengan validasi data dan output buffering
5. **Emoticon Issue**: User minta hapus emoticon → Replaced dengan text label