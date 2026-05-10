-- ============================================
-- DATABASE: ruangunila
-- Sistem Informasi Berita Kampus Universitas Lampung
-- Untuk XAMPP
-- ============================================
-- Cara Penggunaan:
-- 1. Buka XAMPP Control Panel
-- 2. Start Apache dan MySQL
-- 3. Buka browser → http://localhost/phpmyadmin
-- 4. Klik "New" di sidebar kiri
-- 5. Buat database baru dengan nama: ruangunila
-- 6. Klik database "ruangunila"
-- 7. Klik tab "SQL"
-- 8. Copy-paste seluruh isi file ini
-- 9. Klik "Go" atau tekan Ctrl+Enter
-- ============================================

USE ruangunila;

-- ============================================
-- 1. TABEL USERS (Pengguna)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'organisasi', 'mahasiswa') NOT NULL DEFAULT 'mahasiswa',
    profile_photo VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. TABEL NEWS (Berita)
-- ============================================
CREATE TABLE IF NOT EXISTS news (
    news_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT DEFAULT NULL,
    category ENUM('populer', 'kampus', 'ukm', 'akademik', 'event', 'tren') NOT NULL,
    author_id INT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('draft', 'pending', 'published', 'rejected') NOT NULL DEFAULT 'draft',
    rejection_reason TEXT DEFAULT NULL,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_author (author_id),
    INDEX idx_views (views),
    INDEX idx_published_at (published_at),
    
    FOREIGN KEY (author_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    status ENUM('pending', 'upcoming', 'ongoing', 'completed', 'cancelled', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_event_date (event_date),
    INDEX idx_deadline (registration_deadline),
    INDEX idx_organizer (organizer_id),
    
    FOREIGN KEY (organizer_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. TABEL EVENT_REGISTRATIONS (Pendaftaran Event)
-- ============================================
CREATE TABLE IF NOT EXISTS event_registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    registration_number VARCHAR(50) NOT NULL UNIQUE,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    notes TEXT DEFAULT NULL,
    payment_proof VARCHAR(255) DEFAULT NULL,
    payment_status ENUM('unpaid', 'pending_verification', 'paid', 'rejected') NOT NULL DEFAULT 'unpaid',
    payment_verified_at TIMESTAMP NULL DEFAULT NULL,
    payment_rejection_reason TEXT DEFAULT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_event (event_id),
    INDEX idx_user (user_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_status (status),
    UNIQUE KEY unique_registration (event_id, user_id),
    
    FOREIGN KEY (event_id) REFERENCES events(event_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- INSERT DATA DUMMY: ORGANISASI
-- Password: password123 (bcrypt hash)
-- ============================================
INSERT INTO users (username, email, password, full_name, role, phone, status) VALUES
('himatif', 'himatif@ruangunila.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HIMATIF Unila', 'organisasi', '081234567890', 'active'),
('bem_unila', 'bem@ruangunila.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BEM Unila', 'organisasi', '081234567891', 'active');

-- ============================================
-- INSERT DATA DUMMY: MAHASISWA
-- Password: password123 (bcrypt hash)
-- ============================================
INSERT INTO users (username, email, password, full_name, role, phone, status) VALUES
('mahasiswa1', 'mhs1@ruangunila.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Santoso', 'mahasiswa', '081234567892', 'active'),
('mahasiswa2', 'mhs2@ruangunila.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Siti Aminah', 'mahasiswa', '081234567893', 'active');

-- ============================================
-- INSERT DATA DUMMY: BERITA CONTOH
-- ============================================
INSERT INTO news (title, content, excerpt, category, author_id, status, views, published_at) VALUES
('Selamat Datang di Ruang Unila', 'Ruang Unila adalah sistem informasi berita kampus Universitas Lampung. Platform ini menyediakan informasi terkini seputar kegiatan kampus, UKM, akademik, dan event.', 'Platform informasi kampus Unila', 'kampus', 1, 'published', 150, NOW()),
('Pendaftaran UKM Futsal Dibuka', 'UKM Futsal Universitas Lampung membuka pendaftaran anggota baru untuk semester ganjil 2026. Segera daftarkan diri kamu!', 'Pendaftaran anggota baru UKM Futsal', 'ukm', 2, 'published', 89, NOW()),
('Seminar Nasional Teknologi Informasi', 'HIMATIF mengadakan seminar nasional tentang perkembangan AI dan Machine Learning. Gratis untuk mahasiswa Unila!', 'Seminar AI & ML gratis', 'event', 2, 'published', 234, NOW()),
('Jadwal UAS Semester Genap 2025/2026', 'Berikut adalah jadwal Ujian Akhir Semester untuk semester genap tahun akademik 2025/2026.', 'Jadwal UAS terbaru', 'akademik', 1, 'published', 567, NOW()),
('Tips Mengerjakan Skripsi', 'Berikut adalah tips dan trik untuk menyelesaikan skripsi dengan efektif dan tepat waktu.', 'Tips sukses skripsi', 'tren', 3, 'published', 321, NOW());

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
INSERT INTO event_registrations (registration_number, event_id, user_id, payment_status, status) VALUES
('REG-20260401-0001-0001', 1, 4, 'paid', 'confirmed'),
('REG-20260401-0001-0002', 1, 5, 'pending_verification', 'pending'),
('REG-20260401-0002-0001', 2, 4, 'paid', 'confirmed'),
('REG-20260401-0003-0001', 3, 5, 'unpaid', 'pending');

-- ============================================
-- SELESAI! 
-- Database ruangunila siap digunakan di XAMPP.
-- ============================================
-- Login credentials:
--   admin / admin123 (Admin)
--   himatif / password123 (Organisasi)
--   bem_unila / password123 (Organisasi)
--   mahasiswa1 / password123 (Mahasiswa)
--   mahasiswa2 / password123 (Mahasiswa)
-- ============================================