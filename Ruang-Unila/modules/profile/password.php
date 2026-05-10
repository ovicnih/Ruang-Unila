<?php
/**
 * Modul Ubah Password - Ruang Unila
 * 
 * Form ubah password user.
 * 
 * @package RuangUnila
 * @subpackage Modules/Profile
 * @version 1.0.0
 */

// Define APP_ROOT jika belum didefinisikan
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

// Cegah akses langsung
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Include konfigurasi
require_once APP_ROOT . '/config/constants.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/includes/functions.php';

// Include classes
require_once APP_ROOT . '/classes/User.php';
require_once APP_ROOT . '/classes/News.php';
require_once APP_ROOT . '/classes/Event.php';

// Require login
requireLogin();

// Inisialisasi
$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$errors = [];

// Ambil data user saat ini (hanya password hash)
$sql = "SELECT user_id, password FROM users WHERE user_id = :user_id LIMIT 1";
$user = $db->fetchOne($sql, [':user_id' => $userId]);

if (!$user) {
    setFlashMessage('error', 'Data user tidak ditemukan.');
    redirect('modules/profile/index.php');
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid.';
    }
    
    // Ambil input
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validasi password saat ini
    if (empty($currentPassword)) {
        $errors[] = 'Password saat ini wajib diisi.';
    } elseif (!verifyPassword($currentPassword, $user['password'])) {
        $errors[] = 'Password saat ini tidak sesuai.';
    }
    
    // Validasi password baru
    if (empty($newPassword)) {
        $errors[] = 'Password baru wajib diisi.';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'Password baru minimal 8 karakter.';
    } elseif (strlen($newPassword) > 50) {
        $errors[] = 'Password baru maksimal 50 karakter.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $newPassword)) {
        $errors[] = 'Password baru harus mengandung huruf besar, huruf kecil, dan angka.';
    }
    
    // Validasi konfirmasi password
    if (empty($confirmPassword)) {
        $errors[] = 'Konfirmasi password wajib diisi.';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'Konfirmasi password tidak sesuai dengan password baru.';
    }
    
    // Cek apakah password baru sama dengan password lama
    if (verifyPassword($newPassword, $user['password'])) {
        $errors[] = 'Password baru tidak boleh sama dengan password lama.';
    }
    
    // Jika tidak ada error, update database
    if (count($errors) === 0) {
        $hashedPassword = hashPassword($newPassword);
        
        $updateSql = "UPDATE users SET password = :password, updated_at = NOW() WHERE user_id = :user_id";
        $stmt = $db->executeQuery($updateSql, [
            ':password' => $hashedPassword,
            ':user_id' => $userId
        ]);
        
        if ($stmt) {
            setFlashMessage('success', 'Password berhasil diubah!');
            redirect('modules/profile/index.php');
        } else {
            $errors[] = 'Gagal mengubah password. Silakan coba lagi.';
        }
    }
}

// Set page title
$pageTitle = 'Ubah Password';

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0; max-width: 600px; margin: 0 auto;">
    <!-- Breadcrumb -->
    <nav style="margin-bottom: 24px; font-size: 14px;">
        <a href="<?= url('modules/profile/index.php') ?>" style="color: var(--color-text-muted);">Profil</a>
        <span style="margin: 0 8px; color: var(--color-text-muted);">/</span>
        <span style="color: var(--color-text-primary);">Ubah Password</span>
    </nav>
    
    <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 32px;">
        Ubah Password
    </h1>
    
    <!-- Error Messages -->
    <?php if (count($errors) > 0): ?>
        <div style="background: var(--color-danger); color: white; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
            <strong>Terdapat kesalahan:</strong>
            <ul style="margin: 8px 0 0 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Form -->
    <div style="background: white; padding: 32px; border-radius: 12px; margin-bottom: 24px;">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Password Saat Ini *</label>
                <input type="password" name="current_password" 
                       class="form-input" placeholder="Masukkan password saat ini" required
                       style="margin-bottom: 4px;">
                <p style="font-size: 12px; color: var(--color-text-muted);">
                    Masukkan password Anda saat ini untuk verifikasi
                </p>
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Password Baru *</label>
                <input type="password" name="new_password" 
                       class="form-input" placeholder="Masukkan password baru" required
                       style="margin-bottom: 4px;">
                <p style="font-size: 12px; color: var(--color-text-muted);">
                    Minimal 8 karakter, harus mengandung huruf besar, huruf kecil, dan angka
                </p>
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Konfirmasi Password Baru *</label>
                <input type="password" name="confirm_password" 
                       class="form-input" placeholder="Masukkan ulang password baru" required>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    Ubah Password
                </button>
                <a href="<?= url('modules/profile/index.php') ?>" class="btn btn-outline">
                    Batal
                </a>
            </div>
        </form>
    </div>
    
    <!-- Tips -->
    <div style="background: var(--color-primary-light); padding: 20px; border-radius: 12px;">
        <h3 style="font-size: 14px; color: white; margin-bottom: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            Tips Keamanan Password
        </h3>
        <ul style="font-size: 13px; color: white; margin: 0; padding-left: 20px; opacity: 0.9;">
            <li style="margin-bottom: 8px;">Gunakan kombinasi huruf besar, huruf kecil, angka, dan simbol</li>
            <li style="margin-bottom: 8px;">Minimal 8 karakter, lebih panjang lebih baik</li>
            <li style="margin-bottom: 8px;">Jangan gunakan informasi pribadi (nama, tanggal lahir)</li>
            <li style="margin-bottom: 8px;">Jangan gunakan password yang sama dengan akun lain</li>
            <li>Ubah password secara berkala untuk keamanan</li>
        </ul>
    </div>
</div>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>