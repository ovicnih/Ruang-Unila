# Dokumentasi Teknis - Ruang Unila

## 📋 Daftar Isi
1. [Overview](#overview)
2. [Arsitektur Sistem](#arsitektur-sistem)
3. [Database Schema](#database-schema)
4. [API Endpoints](#api-endpoints)
5. [Classes & Methods](#classes--methods)
6. [Security](#security)
7. [Design System](#design-system)
8. [Configuration](#configuration)
9. [Deployment](#deployment)

---

## Overview

**Ruang Unila** adalah sistem informasi berita kampus Universitas Lampung dengan fitur:
- Manajemen berita dengan 6 kategori wajib (Populer, Kampus, UKM, Akademik, Event, Tren)
- Sistem event dengan pendaftaran mahasiswa dan verifikasi pembayaran oleh admin
- Autentikasi multi-role (Admin, Organisasi, Mahasiswa)
- Profile management dengan upload/delete foto profil
- Dashboard statistik untuk admin dan organisasi

**Tech Stack:**
- **Backend:** PHP 8.1+ (Native Modular)
- **Database:** MySQL 8.0 (PDO with Prepared Statements)
- **Frontend:** Semantic HTML5, CSS3 (CSS Variables), Vanilla JS
- **Server Environment:** Apache (XAMPP) with .htaccess URL rewriting

---

## Arsitektur Sistem

### Struktur Folder
```
Ruang-Unila/
├── Progress/            # Dokumentasi & Progress (README, PLANNING, TASK)
├── database/            # File SQL & Skema database
├── Scratch/             # File temporary, test, & debug scripts
├── api/                 # Endpoints untuk integrasi mobile
├── assets/              # Statis assets
│   ├── css/            # Stylesheets (Konsolidasi di style.css)
│   ├── js/             # Vanilla JS (Validation, UI)
│   └── images/         # Logo, placeholder, upload folders
├── classes/            # Business Logic (Model-like)
│   ├── User.php        # User Management & Auth
│   ├── News.php        # News CRUD & Views counting
│   └── Event.php       # Event CRUD & Registrations
├── config/             # System Configuration
│   ├── database.php    # PDO Connection class
│   ├── constants.php   # System-wide constants
│   └── session.php     # Session & Security handling
├── includes/           # Shared Templates & Helpers
│   ├── header.php      # Main Navigation
│   ├── footer.php      # Copyright & Scripts
│   └── functions.php   # Essential Utility functions
├── modules/            # UI Controllers (Feature folders)
│   ├── admin/          # Admin Dashboard & Settings
│   ├── auth/           # Login, Register, Logout
│   ├── events/         # Events CRUD & Payment flow
│   ├── news/           # News CRUD & Moderation
│   ├── profile/        # Profile & Photo management
└── index.php           # Landing Page (Entry Point)
```

---

## Database Schema

### Tabel: users
| Field | Type | Description |
|-------|------|-------------|
| user_id | INT (PK) | Unique ID |
| username | VARCHAR(50) | Unique login name |
| email | VARCHAR(100) | Unique contact email |
| password | VARCHAR(255) | Bcrypt hashed |
| full_name | VARCHAR(100) | Full display name |
| role | ENUM | admin, organisasi, mahasiswa |
| profile_photo | VARCHAR(255) | Filename |
| phone | VARCHAR(20) | Contact number |
| bio | TEXT | User biography |
| status | ENUM | active, inactive |

### Tabel: news
| Field | Type | Description |
|-------|------|-------------|
| news_id | INT (PK) | Unique ID |
| title | VARCHAR(200) | Headline |
| content | TEXT | Full article body |
| category | ENUM | 6 core categories |
| author_id | INT (FK) | Link to users |
| status | ENUM | draft, pending, published, rejected |
| views | INT | View counter |

---

## Design System

Ruang Unila menggunakan **Custom Design System** yang didefinisikan melalui CSS Variables di `assets/css/style.css`.

### Color Palette
- `--color-primary`: #9E0000 (Unila Red)
- `--color-background`: #F8F9FA
- `--color-text-primary`: #191C1D
- `--color-border`: #E8BDB6

### Typography
- **Heading**: 'Newsreader' (Serif) - Memberikan kesan formal & terpercaya.
- **Body**: 'Manrope' (Sans-serif) - Memberikan keterbacaan tinggi di layar.

---

## Security Implementation

1. **SQL Injection**: Proteksi 100% menggunakan `PDO::prepare` dan `PDO::execute`. Tidak ada query string concatenation.
2. **XSS Protection**: Semua output user disaring melalui `htmlspecialchars()` dan `strip_tags()` di `functions.php`.
3. **CSRF Protection**: Token unik digenerate per session dan divalidasi pada setiap request POST (`verifyCSRFToken()`).
4. **Password Security**: Menggunakan standar industri `password_hash()` dengan algoritma Bcrypt.
5. **Session Guard**: Session ID diregenerasi secara berkala, cookie diset ke `HttpOnly` dan `SameSite=Lax`.

---

## Deployment Checklist

- [ ] Pastikan PHP version >= 8.1.
- [ ] Buat database `ruangunila` dan jalankan `database/database_schema.sql`.
- [ ] Update `APP_URL` di `config/constants.php`.
- [ ] Atur permissions folder `assets/images/uploads/` agar writable (775/777).
- [ ] Disable `display_errors` di production environment.

---
**Versi:** 1.2.0 (Reorganized)
**Terakhir Diperbarui:** 9 Mei 2026
**Author:** Antigravity AI