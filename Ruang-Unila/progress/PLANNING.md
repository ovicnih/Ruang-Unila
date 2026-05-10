# PLANNING - Arsitektur Teknis Ruang Unila

## Status Implementasi Saat Ini

### ✅ SELESAI (100%)

#### Config Files
| File | Status | Baris | Fitur |
|------|--------|-------|-------|
| config/database.php | ✅ Selesai | 279 | PDO Database class, singleton pattern, prepared statements |
| config/constants.php | ✅ Selesai | 227 | Semua konstanta sistem, 6 kategori wajib, UI/UX constants |
| config/session.php | ✅ Selesai | 267 | Session management, CSRF protection, flash messages |

#### Include Files
| File | Status | Baris | Fitur |
|------|--------|-------|-------|
| includes/header.php | ✅ Selesai | 107 | Navigasi dinamis, auth check, search bar |
| includes/footer.php | ✅ Selesai | 60 | Informasi copyright, link cepat |
| includes/functions.php | ✅ Selesai | 287 | Sanitasi, validasi, password, redirect, tanggal, upload |

#### Classes
| File | Status | Fitur |
|------|--------|-------|
| classes/User.php | ✅ Selesai | CRUD User, Auth logic, Profile management |
| classes/News.php | ✅ Selesai | CRUD News, Category filtering, View counting |
| classes/Event.php | ✅ Selesai | CRUD Event, Registration logic, Payment verification |

#### Auth Module
| File | Status | Fitur |
|------|--------|-------|
| modules/auth/login.php | ✅ Selesai | Login multi-role, CSRF protection |
| modules/auth/register.php | ✅ Selesai | Registrasi mahasiswa/organisasi |
| modules/auth/logout.php | ✅ Selesai | Session destruction |

#### News Module
| File | Status | Fitur |
|------|--------|-------|
| modules/news/list.php | ✅ Selesai | Daftar berita dengan filter kategori |
| modules/news/detail.php | ✅ Selesai | Detail konten, related news |
| modules/news/create.php | ✅ Selesai | Editor berita, image upload |
| modules/news/admin.php | ✅ Selesai | Validasi berita oleh admin |

#### Events Module
| File | Status | Fitur |
|------|--------|-------|
| modules/events/list.php | ✅ Selesai | Daftar event mendatang |
| modules/events/detail.php | ✅ Selesai | Detail event, countdown deadline |
| modules/events/register.php | ✅ Selesai | Form pendaftaran peserta |
| modules/events/payment.php | ✅ Selesai | Upload bukti pembayaran |
| modules/events/admin.php | ✅ Selesai | Verifikasi pembayaran admin |

#### Profile Module
| File | Status | Fitur |
|------|--------|-------|
| modules/profile/index.php | ✅ Selesai | Dashboard user, statistik |
| modules/profile/edit.php | ✅ Selesai | Edit data diri & password |
| modules/profile/upload.php | ✅ Selesai | Upload/Hapus foto profil |

#### Admin Module
| File | Status | Fitur |
|------|--------|-------|
| modules/admin/dashboard.php | ✅ Selesai | Statistik global, quick actions |
| modules/admin/settings.php | ✅ Selesai | Konfigurasi sistem |

---

## Tech Stack

### Backend
- **Language**: PHP 8.x (Native/Modular)
- **Database**: MySQL 8.0
- **Security**: PDO dengan Prepared Statements, CSRF Protection, Password Hashing (Bcrypt)

### Frontend
- **HTML5**: Semantic markup
- **CSS3**: Custom design system dengan CSS Variables
- **JavaScript**: Vanilla JS untuk interaktivitas & validation
- **Design Style**: Modern, Clean, Professional (Figma inspired)

## Struktur Folder Proyek

```
Ruang-Unila/
├── Progress/         # Dokumentasi & Progress (README, TASK, PLANNING)
├── database/         # File SQL & Skema database
├── Scratch/          # File temporary, test, & debug scripts
├── config/           # Konfigurasi sistem
├── includes/         # Template & fungsi utility
├── assets/           # Statis assets (CSS, JS, Images)
├── modules/          # Modul fitur aplikasi
├── classes/          # Business logic (OOP)
├── api/              # REST API endpoints
├── index.php         # Halaman utama
└── .htaccess         # URL rewriting
```

## Keamanan & Performa

### Keamanan:
1. **SQL Injection**: Proteksi 100% menggunakan PDO Prepared Statements.
2. **XSS**: Sanitasi output dengan `htmlspecialchars()`.
3. **CSRF**: Token unik per session untuk setiap form submission.
4. **Auth**: Session management yang ketat dengan role-based access control.

### Performa:
1. **Database**: Query dioptimasi (tanpa SELECT *), indexing pada kolom krusial.
2. **Assets**: CSS terkonsolidasi, penggunaan CSS variables untuk efisiensi.
3. **Images**: Lazy loading implemented untuk berita dan event.

## Dokumentasi Pendukung
- **README.md**: Panduan cepat & visi proyek.
- **TASK.md**: Daftar fitur & progress pengerjaan.
- **TECHNICAL_DOCS.md**: Dokumentasi teknis mendalam (API, Schema).
- **USER_GUIDE.md**: Panduan penggunaan untuk user.