<?php
/**
 * Modul Upload Foto Profil - Ruang Unila
 * 
 * Upload dan kelola foto profil user.
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

// Ambil data user saat ini
$sql = "SELECT user_id, full_name, profile_photo FROM users WHERE user_id = :user_id LIMIT 1";
$user = $db->fetchOne($sql, [':user_id' => $userId]);

if (!$user) {
    setFlashMessage('error', 'Data user tidak ditemukan.');
    redirect('modules/profile/index.php');
}

// Proses upload foto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid.';
    }
    
    // Validasi action
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload') {
        // Validasi file upload
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'File foto wajib diupload.';
        } else {
            $file = $_FILES['photo'];
            
            // Validasi tipe file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file['type'], $allowedTypes)) {
                $errors[] = 'Format foto harus JPG atau PNG.';
            }
            
            // Validasi ukuran file (max 2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Ukuran foto maksimal 2MB.';
            }
            
            // Validasi upload error
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Gagal mengupload foto. Silakan coba lagi.';
            }
        }
        
        // Jika tidak ada error, proses upload
        if (count($errors) === 0) {
            // Generate nama file unik
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
            $uploadPath = APP_ROOT . '/assets/images/uploads/profile/';
            
            // Buat direktori jika belum ada
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // Hapus foto lama jika ada
            if ($user['profile_photo']) {
                $oldFile = $uploadPath . $user['profile_photo'];
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            
            // Upload file baru
            if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
                // Update database
                $updateSql = "UPDATE users SET profile_photo = :photo, updated_at = NOW() WHERE user_id = :user_id";
                $stmt = $db->executeQuery($updateSql, [
                    ':photo' => $filename,
                    ':user_id' => $userId
                ]);
                
                if ($stmt) {
                    // Update session
                    $_SESSION['user_photo'] = $filename;
                    
                    setFlashMessage('success', 'Foto profil berhasil diupload!');
                    redirect('modules/profile/index.php');
                } else {
                    $errors[] = 'Gagal menyimpan data. Silakan coba lagi.';
                    // Hapus file yang sudah diupload jika gagal update database
                    if (file_exists($uploadPath . $filename)) {
                        unlink($uploadPath . $filename);
                    }
                }
            } else {
                $errors[] = 'Gagal menyimpan foto. Silakan coba lagi.';
            }
        }
    } elseif ($action === 'delete') {
        // Hapus foto
        if ($user['profile_photo']) {
            $uploadPath = APP_ROOT . '/assets/images/uploads/profile/';
            $oldFile = $uploadPath . $user['profile_photo'];
            
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
            
            // Update database
            $updateSql = "UPDATE users SET profile_photo = NULL, updated_at = NOW() WHERE user_id = :user_id";
            $stmt = $db->executeQuery($updateSql, [':user_id' => $userId]);
            
            if ($stmt) {
                // Update session
                $_SESSION['user_photo'] = null;
                
                setFlashMessage('success', 'Foto profil berhasil dihapus!');
                redirect('modules/profile/index.php');
            } else {
                $errors[] = 'Gagal menghapus foto. Silakan coba lagi.';
            }
        } else {
            $errors[] = 'Tidak ada foto untuk dihapus.';
        }
    }
}

// Set page title
$pageTitle = 'Ubah Foto Profil';

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0; max-width: 600px; margin: 0 auto;">
    <!-- Breadcrumb -->
    <nav style="margin-bottom: 24px; font-size: 14px;">
        <a href="<?= url('modules/profile/index.php') ?>" style="color: var(--color-text-muted);">Profil</a>
        <span style="margin: 0 8px; color: var(--color-text-muted);">/</span>
        <span style="color: var(--color-text-primary);">Ubah Foto</span>
    </nav>
    
    <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 32px;">
        Ubah Foto Profil
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
    
    <!-- Current Photo -->
    <div style="background: white; padding: 32px; border-radius: 12px; margin-bottom: 24px; text-align: center;">
        <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 24px;">Foto Saat Ini</h2>
        
        <div style="width: 150px; height: 150px; border-radius: 50%; overflow: hidden; margin: 0 auto 24px; border: 4px solid var(--color-border);">
            <?php if ($user['profile_photo']): ?>
                <img src="<?= url('assets/images/uploads/profile/' . $user['profile_photo']) ?>" 
                     alt="Foto Profil"
                     style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: var(--color-primary); color: white; font-size: 64px; font-weight: 700;">
                    <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($user['profile_photo']): ?>
        <form method="POST" style="margin: 0;">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-danger btn-sm" 
                    onclick="return confirm('Apakah Anda yakin ingin menghapus foto profil?')">
                Hapus Foto
            </button>
        </form>
        <?php endif; ?>
    </div>
    
    <!-- Upload Form -->
    <div style="background: white; padding: 32px; border-radius: 12px;">
        <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 24px;">Upload Foto Baru</h2>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="upload">
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Pilih Foto *</label>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/jpg" 
                       class="form-input" required id="photoInput">
                <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 8px;">
                    Format: JPG, PNG. Maksimal 2MB.
                </p>
            </div>
            
            <!-- Preview -->
            <div id="preview-area" style="display: none; margin-bottom: 24px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Preview</label>
                <div style="width: 150px; height: 150px; border-radius: 50%; overflow: hidden; border: 4px solid var(--color-border);">
                    <img id="preview-image" src="" alt="Preview" 
                         style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    Upload Foto
                </button>
                <a href="<?= url('modules/profile/index.php') ?>" class="btn btn-outline">
                    Batal
                </a>
            </div>
        </form>
    </div>
    
    <!-- Tips -->
    <div style="background: var(--color-primary-light); padding: 20px; border-radius: 12px; margin-top: 24px;">
        <h3 style="font-size: 14px; color: var(--color-primary); margin-bottom: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            Tips Foto Profil
        </h3>
        <ul style="font-size: 13px; color: var(--color-text-secondary); margin: 0; padding-left: 20px;">
            <li style="margin-bottom: 8px;">Gunakan foto dengan wajah terlihat jelas</li>
            <li style="margin-bottom: 8px;">Rasio 1:1 (persegi) akan terlihat lebih baik</li>
            <li style="margin-bottom: 8px;">Resolusi minimal 200x200 pixel</li>
            <li>Hindari foto yang mengandung SARA atau konten tidak pantas</li>
        </ul>
    </div>
</div>

<script>
// Preview image before upload
document.getElementById('photoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-image').src = e.target.result;
            document.getElementById('preview-area').style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        document.getElementById('preview-area').style.display = 'none';
    }
});
</script>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>