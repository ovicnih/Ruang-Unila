<?php
// Setup Database RuangUnila
// Jalankan: php setup_database.php atau akses via browser

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'RuangUnila';

echo "SETUP DATABASE RuangUnila\n";
echo "========================\n\n";

try {
    // Koneksi MySQL
    echo "[1] Koneksi ke MySQL...\n";
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "    OK - Koneksi berhasil!\n\n";

    // Buat Database
    echo "[2] Membuat database '$database'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE $database");
    echo "    OK - Database siap!\n\n";

    // Tabel users
    echo "[3] Membuat tabel users...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin','organisation','student') NOT NULL DEFAULT 'student',
        profile_photo VARCHAR(255) DEFAULT NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_role (role)
    ) ENGINE=InnoDB");
    echo "    OK - Tabel users!\n\n";

    // Tabel news
    echo "[4] Membuat tabel news...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS news (
        news_id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        category ENUM('populer','kampus','ukm','akademik','event','tren') NOT NULL,
        author_id INT NOT NULL,
        image VARCHAR(255) DEFAULT NULL,
        status ENUM('draft','pending','published','rejected') NOT NULL DEFAULT 'draft',
        views INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        published_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_category (category),
        INDEX idx_status (status),
        FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "    OK - Tabel news!\n\n";

    // Tabel events
    echo "[5] Membuat tabel events...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS events (
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
        status ENUM('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        FOREIGN KEY (organizer_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "    OK - Tabel events!\n\n";

    // Tabel event_registrations
    echo "[6] Membuat tabel event_registrations...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS event_registrations (
        registration_id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT NOT NULL,
        user_id INT NOT NULL,
        payment_proof VARCHAR(255) DEFAULT NULL,
        payment_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        verified_at TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY unique_reg (event_id, user_id),
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "    OK - Tabel event_registrations!\n\n";

    // Tabel categories
    echo "[7] Membuat tabel categories...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        category_id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL,
        slug VARCHAR(50) NOT NULL UNIQUE,
        description TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "    OK - Tabel categories!\n\n";

    // Insert Categories
    echo "[8] Insert 6 kategori...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO categories (name, slug, description) VALUES
            ('Populer','populer','Berita dengan views tertinggi'),
            ('Kampus','kampus','Informasi umum kampus'),
            ('UKM','ukm','Kegiatan unit kegiatan mahasiswa'),
            ('Akademik','akademik','Informasi akademik'),
            ('Event','event','Berita seputar event'),
            ('Tren','tren','Berita trending')");
        echo "    OK - 6 kategori inserted!\n\n";
    } else {
        echo "    SKIP - Kategori sudah ada\n\n";
    }

    // Insert Admin
    echo "[9] Insert admin...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username='admin'");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO users (username,email,password,full_name,role,status) 
            VALUES ('admin','admin@ruangunila.com','$hash','Administrator','admin','active')");
        echo "    OK - Admin: admin/admin123\n\n";
    } else {
        echo "    SKIP - Admin sudah ada\n\n";
    }

    // Insert Users
    echo "[10] Insert user contoh...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username!='admin'");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('password123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO users (username,email,password,full_name,role,status) VALUES
            ('himatif','himatif@ruangunila.com','$hash','HIMATIF Unila','organisation','active'),
            ('bem_unila','bem@ruangunila.com','$hash','BEM Unila','organisation','active'),
            ('mahasiswa1','mhs1@ruangunila.com','$hash','Budi Santoso','student','active'),
            ('mahasiswa2','mhs2@ruangunila.com','$hash','Siti Aminah','student','active')");
        echo "    OK - 4 user inserted!\n\n";
    } else {
        echo "    SKIP - User sudah ada\n\n";
    }

    // Insert News
    echo "[11] Insert berita contoh...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM news");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO news (title,content,category,author_id,status,views,published_at) VALUES
            ('Selamat Datang di Ruang Unila','Ruang Unila adalah sistem informasi berita kampus Unila.','kampus',1,'published',150,NOW()),
            ('Pendaftaran UKM Futsal Dibuka','UKM Futsal membuka pendaftaran anggota baru.','ukm',2,'published',89,NOW()),
            ('Seminar Nasional TI','Seminar tentang AI dan Machine Learning.','event',2,'published',234,NOW()),
            ('Jadwal UAS 2025/2026','Jadwal Ujian Akhir Semester.','akademik',1,'published',567,NOW()),
            ('Tips Mengerjakan Skripsi','Tips menyelesaikan skripsi dengan efektif.','tren',3,'published',321,NOW())");
        echo "    OK - 5 berita inserted!\n\n";
    } else {
        echo "    SKIP - Berita sudah ada\n\n";
    }

    // Insert Events
    echo "[12] Insert event contoh...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM events");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO events (title,description,location,event_date,registration_deadline,max_participants,fee,organizer_id,status) VALUES
            ('Seminar AI & ML','Seminar nasional AI','Graha Unila','2026-04-15 09:00:00','2026-04-10 23:59:59',200,0.00,2,'upcoming'),
            ('Workshop WebDev','Workshop PHP MySQL','Lab FT','2026-04-20 13:00:00','2026-04-18 23:59:59',30,50000.00,2,'upcoming'),
            ('Futsal 2026','Kompetisi futsal','GOR Unila','2026-05-01 08:00:00','2026-04-25 23:59:59',16,100000.00,3,'upcoming')");
        echo "    OK - 3 event inserted!\n\n";
    } else {
        echo "    SKIP - Event sudah ada\n\n";
    }

    // Insert Registrations
    echo "[13] Insert registrasi contoh...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM event_registrations");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO event_registrations (event_id,user_id,payment_status) VALUES
            (1,4,'verified'),(1,5,'pending'),(2,4,'verified'),(3,5,'pending')");
        echo "    OK - 4 registrasi inserted!\n\n";
    } else {
        echo "    SKIP - Registrasi sudah ada\n\n";
    }

    // Verifikasi
    echo "========================\n";
    echo "VERIFIKASI DATABASE\n";
    echo "========================\n";
    foreach (['users','news','events','event_registrations','categories'] as $t) {
        $c = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        echo "  $t: $c data\n";
    }
    echo "\nSELESAI! Database siap digunakan.\n";
    echo "Login: admin/admin123 | himatif/password123 | mahasiswa1/password123\n";

} catch (PDOException $e) {
    echo "\nERROR: {$e->getMessage()}\n";
}
?>
</parameter>
<task_progress>
- [x] Periksa file README.md yang ada
- [x] Periksa file PLANNING.md yang ada
- [x] Periksa file TASK.md yang ada
- [x] Perbarui README.md dengan visi proyek lengkap
- [x] Perbarui PLANNING.md dengan arsitektur teknis detail
- [x] Perbarui TASK.md dengan daftar tugas terperinci
- [x] Berikan ringkasan langkah pertama
- [x] Buat file SQL script database
- [x] Buat script PHP setup database
- [ ] Jalankan script PHP untuk buat database
- [ ] Verifikasi database berhasil dibuat
</task_progress>
</write_to_file>