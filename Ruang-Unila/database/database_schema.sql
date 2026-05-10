-- ============================================
-- DATABASE: RuangUnila
-- Sistem Informasi Berita Kampus Universitas Lampung
-- ============================================
-- Cara Penggunaan:
-- 1. Buka Laragon → Menu → MySQL → phpMyAdmin
-- 2. Klik "New" di sidebar kiri
-- 3. Buat database baru dengan nama: RuangUnila
-- 4. Klik tab "SQL"
-- 5. Copy-paste seluruh isi file ini
-- 6. Klik "Go" atau tekan Ctrl+Enter
-- ============================================

USE RuangUnila;

-- ============================================
-- 1. TABEL USERS (Pengguna)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'organisation', 'student') NOT NULL DEFAULT 'student',
    profile_photo VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- 2. TABEL NEWS (Berita)
-- ============================================
CREATE TABLE IF NOT EXISTS news (
    news_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    category ENUM('populer', 'kampus', 'ukm', 'akademik', 'event', 'tren') NOT NULL,
    author_id INT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('draft', 'pending', 'published', 'rejected') NOT NULL DEFAULT 'draft',
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL DEFAULT NULL,
    
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_author (author_id),
    INDEX idx_views (views),
    INDEX idx_published_at (published_at),
    
    FOREIGN KEY (author_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 3. TABEL EVENTS (Event/Kegiatan)
-- ============================================
CREATE TABLE IF NOT EXISTS events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(200) NOT NULL,
    event_date DATETIME NOT NULL,
    registration_deadline DATETIME NOT NULL,
    max_participants INT NOT NULL DEFAULT 100,
    fee DECIMAL(10,2) DEFAULT 0.00,
    organizer_id INT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_event_date (event_date),
    INDEX idx_deadline (registration_deadline),
    INDEX idx_organizer (organizer_id),
    
    FOREIGN KEY (organizer_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 4. TABEL EVENT_REGISTRATIONS (Pendaftaran Event)
-- ============================================
CREATE TABLE IF NOT EXISTS event_registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    payment_proof VARCHAR(255) DEFAULT NULL,
    payment_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    
    INDEX idx_event (event_id),
    INDEX idx_user (user_id),
    INDEX idx_payment_status (payment_status),
    UNIQUE KEY unique_registration (event_id, user_id),
    
    FOREIGN KEY (event_id) REFERENCES events(event_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 5. TABEL CATEGORIES (Kategori)
-- ============================================
CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug)
) ENGINE=InnoDB;

-- ============================================
-- INSERT DATA DEFAULT: 6 KATEGORI WAJIB
-- ============================================
INSERT INTO categories (name, slug, description) VALUES
('Populer', 'populer', 'Berita dengan views tertinggi'),
('Kampus', 'kampus', 'Informasi umum kampus Universitas Lampung'),
('UKM', 'ukm', 'Kegiatan unit kegiatan mahasiswa'),
('Akademik', 'akademik', 'Informasi akademik dan perkuliahan'),
('Event', 'event', 'Berita seputar event dan kegiatan'),
('Tren', 'tren', 'Berita trending dan topik hangat');

-- ============================================
-- INSERT DATA DUMMY: ADMIN DEFAULT
-- Password: admin123 (bcrypt hash)
-- ============================================
INSERT INTO users (username, email, password, full_name, role, status) VALUES
('admin', 'admin@ruangunila.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 'active');

-- ============================================
-- INSERT DATA DUMMY: USER CONTOH
-- Password: password123 (bcrypt hash)
-- ============================================
INSERT INTO users (username, email, password, full_name, role, status) VALUES
('himatif', 'himatif@ruangunila.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HIMATIF Unila', 'organisation', 'active'),
('bem_unila', 'bem@ruangunila.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BEM Unila', 'organisation', 'active'),
('mahasiswa1', 'mhs1@ruangunila.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Santoso', 'student', 'active'),
('mahasiswa2', 'mhs2@ruangunila.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Siti Aminah', 'student', 'active');

-- ============================================
-- INSERT DATA DUMMY: BERITA CONTOH
-- ============================================
INSERT INTO news (title, content, category, author_id, status, views, published_at) VALUES
('Selamat Datang di Ruang Unila', 'Ruang Unila adalah sistem informasi berita kampus Universitas Lampung. Platform ini menyediakan informasi terkini seputar kegiatan kampus, UKM, akademik, dan event.', 'kampus', 1, 'published', 150, NOW()),
('Pendaftaran UKM Futsal Dibuka', 'UKM Futsal Universitas Lampung membuka pendaftaran anggota baru untuk semester ganjil 2026. Segera daftarkan diri kamu!', 'ukm', 2, 'published', 89, NOW()),
('Seminar Nasional Teknologi Informasi', 'HIMATIF mengadakan seminar nasional tentang perkembangan AI dan Machine Learning. Gratis untuk mahasiswa Unila!', 'event', 2, 'published', 234, NOW()),
('Jadwal UAS Semester Genap 2025/2026', 'Berikut adalah jadwal Ujian Akhir Semester untuk semester genap tahun akademik 2025/2026.', 'akademik', 1, 'published', 567, NOW()),
('Tips Mengerjakan Skripsi', 'Berikut adalah tips dan trik untuk menyelesaikan skripsi dengan efektif dan tepat waktu.', 'tren', 3, 'published', 321, NOW());

-- ============================================
-- INSERT DATA DUMMY: EVENT CONTOH
-- ============================================
INSERT INTO events (title, description, location, event_date, registration_deadline, max_participants, fee, organizer_id, status) VALUES
('Seminar AI & Machine Learning', 'Seminar nasional tentang perkembangan Artificial Intelligence dan Machine Learning di Indonesia.', 'Graha Unila, Lt. 3', '2026-04-15 09:00:00', '2026-04-10 23:59:59', 200, 0.00, 2, 'upcoming'),
('Workshop Web Development', 'Workshop praktis membuat website modern dengan PHP dan MySQL.', 'Lab Komputer FT, Ruang 101', '2026-04-20 13:00:00', '2026-04-18 23:59:59', 30, 50000.00, 2, 'upcoming'),
('Futsal Competition 2026', 'Kompetisi futsal antar fakultas se-Universitas Lampung.', 'GOR Unila', '2026-05-01 08:00:00', '2026-04-25 23:59:59', 16, 100000.00, 3, 'upcoming');

-- ============================================
-- INSERT DATA DUMMY: REGISTRASI EVENT
-- ============================================
INSERT INTO event_registrations (event_id, user_id, payment_status) VALUES
(1, 4, 'verified'),
(1, 5, 'pending'),
(2, 4, 'verified'),
(3, 5, 'pending');

-- ============================================
-- SELESAI! 
-- Database RuangUnila siap digunakan.
-- Login credentials:
--   admin / admin123 (Admin)
--   himatif / password123 (Organisasi)
--   mahasiswa1 / password123 (Mahasiswa)
-- ============================================
</task_progress>
- [x] Periksa file README.md yang ada
- [x] Periksa file PLANNING.md yang ada
- [x] Periksa file TASK.md yang ada
- [x] Perbarui README.md dengan visi proyek lengkap
- [x] Perbarui PLANNING.md dengan arsitektur teknis detail
- [x] Perbarui TASK.md dengan daftar tugas terperinci
- [x] Berikan ringkasan langkah pertama
- [x] Buat file SQL script database
- [ ] Buat database RuangUnila di MySQL
- [ ] Verifikasi database berhasil dibuat
</write_to_file>