<?php
// Fix Passwords - RuangUnila
// Jalankan: php fix_passwords.php atau akses via browser
// Script ini akan meng-hash ulang semua password yang belum di-hash

echo "FIX PASSWORDS - RuangUnila\n";
echo "=========================\n\n";

try {
    // Koneksi database
    $pdo = new PDO('mysql:host=localhost;dbname=RuangUnila', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "[1] Koneksi database berhasil!\n\n";
    
    // Cek semua user
    $stmt = $pdo->query("SELECT user_id, username, email, password, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "[2] Ditemukan " . count($users) . " user:\n\n";
    
    $fixedCount = 0;
    
    foreach ($users as $user) {
        $userId = $user['user_id'];
        $username = $user['username'];
        $email = $user['email'];
        $currentPassword = $user['password'];
        $role = $user['role'];
        
        // Cek apakah password sudah di-hash (bcrypt hash dimulai dengan $2y$)
        if (strpos($currentPassword, '$2y$') === 0) {
            echo "   [OK] $username ($email) - Password sudah di-hash\n";
        } else {
            echo "   [FIX] $username ($email) - Password belum di-hash, meng-hash ulang...\n";
            
            // Tentukan password default berdasarkan role
            if ($username === 'admin') {
                $newPassword = 'admin123';
            } elseif (strpos($username, 'mahasiswa') !== false) {
                $newPassword = 'password123';
            } else {
                $newPassword = 'password123'; // Default untuk organisasi
            }
            
            // Hash password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Update database
            $updateStmt = $pdo->prepare("UPDATE users SET password = :password WHERE user_id = :user_id");
            $updateStmt->execute([
                ':password' => $hashedPassword,
                ':user_id' => $userId
            ]);
            
            echo "      Password baru: $newPassword\n";
            echo "      Hash: $hashedPassword\n";
            
            $fixedCount++;
        }
    }
    
    echo "\n";
    echo "=========================\n";
    echo "HASIL:\n";
    echo "  Total user: " . count($users) . "\n";
    echo "  Password di-fix: $fixedCount\n";
    echo "\n";
    
    if ($fixedCount > 0) {
        echo "SELESAI! Password sudah di-hash ulang.\n";
        echo "Login credentials:\n";
        echo "  admin / admin123\n";
        echo "  himatif / password123\n";
        echo "  bem_unila / password123\n";
        echo "  mahasiswa1 / password123\n";
        echo "  mahasiswa2 / password123\n";
    } else {
        echo "SELESAI! Semua password sudah dalam format bcrypt.\n";
        echo "Jika masih tidak bisa login, coba:\n";
        echo "  1. Clear browser cache\n";
        echo "  2. Restart Apache\n";
        echo "  3. Cek error log Apache\n";
    }
    
} catch (PDOException $e) {
    echo "\nERROR: {$e->getMessage()}\n";
}
?>