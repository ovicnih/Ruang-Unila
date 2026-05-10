<?php
/**
 * Script untuk reset password user
 * Menghasilkan password hash yang kompatibel dengan sistem login
 */

// Konfigurasi database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'RuangUnila';

echo "RESET PASSWORDS DATABASE RuangUnila\n";
echo "====================================\n\n";

try {
    // Koneksi ke database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Daftar user yang akan direset
    $users = [
        'admin' => 'admin123',
        'himatif' => 'password123',
        'bem_unila' => 'password123',
        'mahasiswa1' => 'password123',
        'mahasiswa2' => 'password123'
    ];
    
    echo "[1] Reset passwords...\n\n";
    
    foreach ($users as $username => $plainPassword) {
        // Hash password dengan cara yang sama seperti register.php
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Update password di database
        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE username = :username");
        $stmt->execute([
            ':password' => $hashedPassword,
            ':username' => $username
        ]);
        
        echo "  ✅ $username → password: $plainPassword\n";
    }
    
    echo "\n[2] Verifikasi...\n\n";
    
    // Ambil semua user untuk verifikasi
    $result = $pdo->query("SELECT username, email, role, SUBSTRING(password, 1, 20) as pass_preview FROM users ORDER BY role, username");
    
    $currentRole = '';
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        if ($currentRole !== $row['role']) {
            $currentRole = $row['role'];
            echo "\n  [" . strtoupper($currentRole) . "]\n";
        }
        echo "    - {$row['username']} ({$row['email']}) - pass: {$row['pass_preview']}...\n";
    }
    
    echo "\n\n====================================\n";
    echo "SELESAI! Semua password sudah direset.\n\n";
    
    echo "Silakan test login:\n";
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