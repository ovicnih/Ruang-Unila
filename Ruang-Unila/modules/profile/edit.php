<?php
/**
 * Modul Edit Profil - Ruang Unila
 * 
 * Form edit data diri user.
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
$success = false;

// Ambil data user saat ini
$sql = "SELECT user_id, username, email, full_name, phone, bio, profile_photo
        FROM users 
        WHERE user_id = :user_id 
        LIMIT 1";

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
    
    // Ambil dan sanitasi input
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $bio = sanitize($_POST['bio'] ?? '');
    
    // Validasi nama lengkap
    if (empty($fullName)) {
        $errors[] = 'Nama lengkap wajib diisi.';
    } elseif (strlen($fullName) < 3) {
        $errors[] = 'Nama lengkap minimal 3 karakter.';
    } elseif (strlen($fullName) > 100) {
        $errors[] = 'Nama lengkap maksimal 100 karakter.';
    }
    
    // Validasi email
    if (empty($email)) {
        $errors[] = 'Email wajib diisi.';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Format email tidak valid.';
    } else {
        // Cek email unik (kecuali email user saat ini)
        $checkEmailSql = "SELECT COUNT(*) as count FROM users WHERE email = :email AND user_id != :user_id";
        $checkEmailResult = $db->fetchOne($checkEmailSql, [':email' => $email, ':user_id' => $userId]);
        
        if ($checkEmailResult && $checkEmailResult['count'] > 0) {
            $errors[] = 'Email sudah digunakan oleh user lain.';
        }
    }
    
    // Validasi telepon (opsional)
    if (!empty($phone)) {
        if (!preg_match('/^[0-9+\-\s]{10,15}$/', $phone)) {
            $errors[] = 'Format nomor telepon tidak valid.';
        }
    }
    
    // Validasi bio (opsional)
    if (strlen($bio) > 500) {
        $errors[] = 'Bio maksimal 500 karakter.';
    }
    
    // Jika tidak ada error, update database
    if (count($errors) === 0) {
        $updateSql = "UPDATE users 
                      SET full_name = :full_name, 
                          email = :email, 
                          phone = :phone, 
                          bio = :bio,
                          updated_at = NOW()
                      WHERE user_id = :user_id";
        
        $stmt = $db->executeQuery($updateSql, [
            ':full_name' => $fullName,
            ':email' => $email,
            ':phone' => $phone ?: null,
            ':bio' => $bio ?: null,
            ':user_id' => $userId
        ]);
        
        if ($stmt) {
            // Update session data
            $_SESSION['user_fullname'] = $fullName;
            $_SESSION['user_email'] = $email;
            
            setFlashMessage('success', 'Profil berhasil diperbarui!');
            redirect('modules/profile/index.php');
        } else {
            $errors[] = 'Gagal memperbarui profil. Silakan coba lagi.';
        }
    }
}

// Set page title
$pageTitle = 'Edit Profil';

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0; max-width: 800px; margin: 0 auto;">
    <!-- Breadcrumb -->
    <nav style="margin-bottom: 24px; font-size: 14px;">
        <a href="<?= url('modules/profile/index.php') ?>" style="color: var(--color-text-muted);">Profil</a>
        <span style="margin: 0 8px; color: var(--color-text-muted);">/</span>
        <span style="color: var(--color-text-primary);">Edit Profil</span>
    </nav>
    
    <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 32px;">
        Edit Profil
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
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px;">
            <!-- Main Form -->
            <div>
                <!-- Informasi Dasar -->
                <div style="background: white; padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                    <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 20px;">Informasi Dasar</h2>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Username</label>
                        <input type="text" value="<?= htmlspecialchars($user['username']) ?>" 
                               class="form-input" disabled style="background: var(--color-bg);">
                        <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                            Username tidak dapat diubah
                        </p>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Nama Lengkap *</label>
                        <input type="text" name="full_name" 
                               value="<?= htmlspecialchars($_POST['full_name'] ?? $user['full_name']) ?>" 
                               class="form-input" placeholder="Masukkan nama lengkap" required>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Email *</label>
                        <input type="email" name="email" 
                               value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>" 
                               class="form-input" placeholder="Masukkan email" required>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Nomor Telepon</label>
                        <input type="tel" name="phone" 
                               value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone']) ?>" 
                               class="form-input" placeholder="Contoh: 081234567890">
                        <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                            Opsional. Format: 08xxx atau +62xxx
                        </p>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Bio</label>
                        <textarea name="bio" class="form-input" rows="4" 
                                  placeholder="Ceritakan sedikit tentang diri Anda..."
                                  maxlength="500"><?= htmlspecialchars($_POST['bio'] ?? $user['bio']) ?></textarea>
                        <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                            Maksimal 500 karakter
                        </p>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Simpan Perubahan
                    </button>
                    <a href="<?= url('modules/profile/index.php') ?>" class="btn btn-outline">
                        Batal
                    </a>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div>
                <!-- Profile Photo -->
                <div style="background: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; text-align: center;">
                    <h3 style="font-family: var(--font-heading); font-size: 16px; margin-bottom: 16px;">Foto Profil</h3>
                    
                    <div style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; margin: 0 auto 16px; border: 4px solid var(--color-border);">
                        <?php if ($user['profile_photo']): ?>
                            <img src="<?= url('assets/images/uploads/profile/' . $user['profile_photo']) ?>" 
                                 alt="Foto Profil"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: var(--color-primary); color: white; font-size: 48px; font-weight: 700;">
                                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <a href="<?= url('modules/profile/photo.php') ?>" class="btn btn-outline btn-sm" style="width: 100%;">
                        Ubah Foto
                    </a>
                </div>
                
                <!-- Info -->
                <div style="background: var(--color-primary-light); padding: 20px; border-radius: 12px;">
                    <h3 style="font-size: 14px; color: white; margin-bottom: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        Catatan
                    </h3>
                    <ul style="font-size: 13px; color: white; margin: 0; padding-left: 20px; opacity: 0.9;">
                        <li style="margin-bottom: 8px;">Username tidak dapat diubah</li>
                        <li style="margin-bottom: 8px;">Email harus unik</li>
                        <li>Perubahan akan langsung tersimpan</li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</div>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>