# Ruang Unila - Workspace Rules

## 🎯 Project Overview
Ruang Unila is a campus news and event management system. All code in this workspace must follow these specific project rules to ensure consistency and quality.

## 🛠️ Technology Stack
- **Backend**: PHP 8.1+ (Modular Native)
- **Database**: MySQL 8.0 (PDO Only)
- **Frontend**: Vanilla CSS (No Frameworks), Vanilla JS
- **UI/UX Standard**: Ikuti desain Figma [MPI Design](https://www.figma.com/design/Ny5pPVvqp7tetrEN0TY29p/MPI?node-id=0-1&p=f&t=8XMzt2rjdV45QwSR-0)

## 📋 Coding Standards

### 1. PHP Backend
- **Database Access**: Always use the `Database` class and `PDO` with prepared statements. **NEVER** use `mysqli` or direct string concatenation in queries.
- **Security**:
  - Always validate CSRF tokens on POST requests using `verifyCSRFToken()`.
  - Sanitize all user-generated content using `htmlspecialchars()` before outputting.
  - Use `cleanInput()` for all incoming data.
- **Architecture**: Keep business logic in `classes/` and UI controllers in `modules/`.

### 2. CSS Styling
- **Design Tokens**: Always use CSS variables defined in `assets/css/style.css`.
  - Primary Color: `var(--color-primary)`
  - Background: `var(--color-background)`
- **Responsiveness**: Follow a mobile-first approach. Use the provided utility classes for grid and flex layouts.
- **Naming**: Use hyphenated classes (e.g., `.news-card`, `.event-grid`) consistent with the current stylesheet.

### 3. Modularitas & Ukuran File
- **Batas Baris**: Jaga agar setiap file kode tidak lebih dari 500 baris. Jika sudah mendekati batas, bagi kode ke dalam modul-modul kecil atau extract logic ke class baru.

## 📄 Manajemen Dokumentasi
Wajib menggunakan dan selalu memperbarui tiga file utama secara progresif saat menulis kode:
1. **README.md**: Visi proyek dan panduan cepat.
2. **PLANNING.md**: Arsitektur teknis dan tech stack.
3. **TASK.md**: Daftar tugas spesifik dan progress pengerjaan.

## 🔄 Workflow & Prosedur
1. **Satu Tugas Per Pesan**: Selesaikan satu tugas kecil sampai tuntas sebelum pindah ke tugas berikutnya. Jangan berikan banyak instruksi sekaligus.
2. **Dokumentasi Progresif**: Tulis komentar dan dokumentasi teknis secara langsung saat menulis kode, jangan ditunda.
3. **Unit Testing**: Setiap fungsi baru yang dibuat harus disertai dengan unit test untuk memastikan fungsionalitasnya berjalan baik.

## 🏗️ Fitur & Kategori Wajib
- **6 Kategori Berita**: Populer, Kampus, UKM, Akademik, Event, dan Tren.
- **Setting Profile**: Fitur untuk mengubah data diri dan foto profil user.
- **Flow Event**: Alur lengkap (Publikasi Event -> Pendaftaran Peserta -> Sistem Pembayaran/Upload bukti/Konfirmasi).

## 🧰 Recommended Skills for this Project
Gunakan skills berikut secara proaktif untuk menjaga standar kualitas:
- **UI/UX**: `frontend-design`, `ui-tokens`, `iconsax-library`, `animejs-animation`.
- **Backend & Database**: `php-pro`, `sql-optimization-patterns`, `senior-fullstack`.
- **Security & Performance**: `security-auditor`, `web-performance-optimization`, `systematic-debugging`.
- **SEO**: `seo-audit`, `schema-markup`.
