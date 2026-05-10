<?php
/**
 * Script untuk fix role di database
 * Mengubah role dari 'student'/'organisation' ke 'mahasiswa'/'organisasi'
 * dan mengubah struktur ENUM di tabel users
 */

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'RuangUnila';

echo "FIX ROLES DATABASE RuangUnila\n";
echo "==============================\n\n";

try {
    // Koneksi ke database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "[1] Mengubah struktur tabel users...\n";
    
    // Ubah ENUM role dari 'student'/'organisation' ke 'mahasiswa'/'organisasi'
    $pdo->exec("ALTER TABLE users MODIFY role ENUM('admin','organisasi','mahasiswa') NOT NULL DEFAULT 'mahasiswa'");
    echo "    OK - Struktur tabel users diubah!\n\n";
    
    echo "[2] Mengupdate role 'student' ke 'mahasiswa'...\n";
    $stmt = $pdo->exec("UPDATE users SET role = 'mahasiswa' WHERE role = 'student'");
    echo "    OK - $stmt baris diubah dari 'student' ke 'mahasiswa'\n\n";
    
    echo "[3] Mengupdate role 'organisation' ke 'organisasi'...\n";
    $stmt = $pdo->exec("UPDATE users SET role = 'organisasi' WHERE role = 'organisation'");
    echo "    OK - $stmt baris diubah dari 'organisation' ke 'organisasi'\n\n";
    
    echo "[4] Verifikasi data users...\n";
    $users = $pdo->query("SELECT user_id, username, email, role, full_name FROM users ORDER BY role, user_id")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "    Role saat ini:\n";
    $currentRole = '';
    foreach ($users as $user) {
        if ($currentRole !== $user['role']) {
            $currentRole = $user['role'];
            echo "\n    [" . strtoupper($currentRole) . "]\n";
        }
        echo "      - {$user['username']} ({$user['email']})\n";
    }
    
    echo "\n\n==============================\n";
    echo "SELESAI! Role sudah diperbaiki.\n\n";
    
    echo "Data login:\n";
    echo "  - Mahasiswa: mahasiswa1 / password123\n";
    echo "  - Organisasi: himatif / password123\n";
    echo "  - Admin: admin / admin123\n\n";
    
} catch (PDOException $e) {
    echo "\nERROR: {$e->getMessage()}\n";
    echo "\nPastikan:\n";
    echo "1. Database 'RuangUnila' sudah dibuat\n";
    echo "2. XAMPP/MySQL sudah berjalan\n";
    echo "3. Username dan password database benar\n";
}
?>