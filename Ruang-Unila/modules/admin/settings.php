<?php
/**
 * Pengaturan Sistem - Ruang Unila
 * 
 * Pengaturan umum aplikasi, konfigurasi email, upload,
 * maintenance mode, dan backup database.
 * 
 * @package RuangUnila
 * @subpackage Modules/Admin
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

// Require login sebagai admin
requireLogin();

if (!hasRole(ROLE_ADMIN)) {
    setFlashMessage('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect(url('index.php'));
}

// Inisialisasi
$session = Session::getInstance();
$db = Database::getInstance();
$pageTitle = 'Pengaturan Sistem';

// Backup Database
if (isset($_GET['backup'])) {
    $tables = ['users', 'news', 'events', 'event_registrations', 'categories', 'notifications'];
    $backup = "-- RuangUnila Database Backup\n";
    $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $backup .= "-- Table: $table\n";
        
        // Get table structure
        $createTable = $db->fetchOne("SHOW CREATE TABLE $table");
        if ($createTable) {
            $backup .= $createTable['Create Table'] . ";\n\n";
        }
        
        // Get table data
        $rows = $db->fetchAll("SELECT * FROM $table");
        if (count($rows) > 0) {
            $backup .= "INSERT INTO $table VALUES\n";
            $values = [];
            foreach ($rows as $row) {
                $escapedValues = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $escapedValues[] = 'NULL';
                    } else {
                        $escapedValues[] = "'" . addslashes($value) . "'";
                    }
                }
                $values[] = '(' . implode(', ', $escapedValues) . ')';
            }
            $backup .= implode(",\n", $values) . ";\n\n";
        }
    }
    
    // Set header untuk download
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename=ruangunila_backup_' . date('Y-m-d_His') . '.sql');
    echo $backup;
    exit;
}

// Proses simpan pengaturan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    // Validasi CSRF
    if (!$session->verifyCsrfToken($csrfToken)) {
        $session->setFlash('error', 'Token keamanan tidak valid.');
        redirect(url('modules/admin/settings.php'));
    }
    
    switch ($action) {
        case 'save_general':
            $appName = cleanInput($_POST['app_name'] ?? '');
            $appUrl = cleanInput($_POST['app_url'] ?? '');
            $maintenanceMode = isset($_POST['maintenance_mode']) ? 1 : 0;
            
            // Simpan ke file atau database
            $settingsContent = "<?php\n";
            $settingsContent .= "// Auto-generated settings\n";
            $settingsContent .= "define('APP_NAME_CUSTOM', '$appName');\n";
            $settingsContent .= "define('APP_URL_CUSTOM', '$appUrl');\n";
            $settingsContent .= "define('MAINTENANCE_MODE', $maintenanceMode);\n";
            $settingsContent .= "?>";
            
            file_put_contents(APP_ROOT . '/config/settings_custom.php', $settingsContent);
            $session->setFlash('success', 'Pengaturan umum berhasil disimpan!');
            break;
            
        case 'save_email':
            $smtpHost = cleanInput($_POST['smtp_host'] ?? '');
            $smtpPort = cleanInput($_POST['smtp_port'] ?? '');
            $smtpUser = cleanInput($_POST['smtp_user'] ?? '');
            $smtpPass = $_POST['smtp_pass'] ?? '';
            $smtpFrom = cleanInput($_POST['smtp_from'] ?? '');
            
            $emailContent = "<?php\n";
            $emailContent .= "// SMTP Configuration\n";
            $emailContent .= "define('SMTP_HOST', '$smtpHost');\n";
            $emailContent .= "define('SMTP_PORT', $smtpPort);\n";
            $emailContent .= "define('SMTP_USER', '$smtpUser');\n";
            $emailContent .= "define('SMTP_PASS', '$smtpPass');\n";
            $emailContent .= "define('SMTP_FROM', '$smtpFrom');\n";
            $emailContent .= "?>";
            
            file_put_contents(APP_ROOT . '/config/email.php', $emailContent);
            $session->setFlash('success', 'Konfigurasi email berhasil disimpan!');
            break;
            
        case 'save_upload':
            $maxSize = (int)($_POST['max_upload_size'] ?? 2);
            $allowedTypes = cleanInput($_POST['allowed_types'] ?? 'jpg,jpeg,png,gif');
            
            $uploadContent = "<?php\n";
            $uploadContent .= "// Upload Configuration\n";
            $uploadContent .= "define('MAX_UPLOAD_SIZE_MB', $maxSize);\n";
            $uploadContent .= "define('ALLOWED_EXTENSIONS', ['$allowedTypes']);\n";
            $uploadContent .= "?>";
            
            file_put_contents(APP_ROOT . '/config/upload.php', $uploadContent);
            $session->setFlash('success', 'Konfigurasi upload berhasil disimpan!');
            break;
            
        default:
            $session->setFlash('error', 'Aksi tidak valid.');
            break;
    }
    
    redirect(url('modules/admin/settings.php'));
}

// Baca pengaturan yang ada
$appName = APP_NAME ?? 'Ruang Unila';
$appUrl = APP_URL ?? 'http://localhost/WEBUNILA/Ruang-Unila';
$maintenanceMode = file_exists(APP_ROOT . '/maintenance.flag') ? 1 : 0;

// SMTP settings
$smtpHost = defined('SMTP_HOST') ? SMTP_HOST : '';
$smtpPort = defined('SMTP_PORT') ? SMTP_PORT : 587;
$smtpUser = defined('SMTP_USER') ? SMTP_USER : '';
$smtpFrom = defined('SMTP_FROM') ? SMTP_FROM : '';

// Upload settings
$maxUploadSize = defined('MAX_UPLOAD_SIZE_MB') ? MAX_UPLOAD_SIZE_MB : 2;
$allowedTypes = defined('ALLOWED_EXTENSIONS') ? implode(',', ALLOWED_EXTENSIONS) : 'jpg,jpeg,png,gif';

// Generate CSRF token
$csrfToken = $session->generateCsrfToken();

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0; max-width: 900px; margin: 0 auto;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">
                Pengaturan Sistem
            </h1>
            <p style="color: var(--color-text-secondary); font-size: 14px;">
                Konfigurasi dan pengaturan aplikasi Ruang Unila
            </p>
        </div>
        <a href="<?= url('modules/admin/settings.php?backup=1') ?>" class="btn btn-primary">
            💾 Backup Database
        </a>
    </div>

    <!-- Flash Message -->
    <?php
    $flashError = $session->getFlash('error');
    $flashSuccess = $session->getFlash('success');
    
    if ($flashError) {
        echo '<div class="alert alert-error">' . htmlspecialchars($flashError) . '</div>';
    }
    if ($flashSuccess) {
        echo '<div class="alert alert-success">' . htmlspecialchars($flashSuccess) . '</div>';
    }
    ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- Pengaturan Umum -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 24px;">Pengaturan Umum</h2>
            
            <form method="POST">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="save_general">
                
                <div class="form-group">
                    <label class="form-label">Nama Aplikasi</label>
                    <input type="text" name="app_name" value="<?= htmlspecialchars($appName) ?>" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">URL Aplikasi</label>
                    <input type="url" name="app_url" value="<?= htmlspecialchars($appUrl) ?>" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="maintenance_mode" <?= $maintenanceMode ? 'checked' : '' ?>>
                        <span style="font-weight: 600;">Maintenance Mode</span>
                    </label>
                    <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 8px;">
                        Aktifkan untuk menonaktifkan akses publik sementara
                    </p>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Pengaturan</button>
            </form>
        </div>

        <!-- Konfigurasi Email -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 24px;">Konfigurasi Email (SMTP)</h2>
            
            <form method="POST">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="save_email">
                
                <div class="form-group">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" name="smtp_host" value="<?= htmlspecialchars($smtpHost) ?>" class="form-input" placeholder="smtp.gmail.com">
                </div>
                
                <div class="form-group">
                    <label class="form-label">SMTP Port</label>
                    <input type="number" name="smtp_port" value="<?= $smtpPort ?>" class="form-input" placeholder="587">
                </div>
                
                <div class="form-group">
                    <label class="form-label">SMTP Username</label>
                    <input type="text" name="smtp_user" value="<?= htmlspecialchars($smtpUser) ?>" class="form-input" placeholder="email@gmail.com">
                </div>
                
                <div class="form-group">
                    <label class="form-label">SMTP Password</label>
                    <input type="password" name="smtp_pass" class="form-input" placeholder="••••••••">
                </div>
                
                <div class="form-group">
                    <label class="form-label">From Email</label>
                    <input type="email" name="smtp_from" value="<?= htmlspecialchars($smtpFrom) ?>" class="form-input" placeholder="noreply@ruangunila.com">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Konfigurasi</button>
            </form>
        </div>

        <!-- Konfigurasi Upload -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 24px;">Konfigurasi Upload</h2>
            
            <form method="POST">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="save_upload">
                
                <div class="form-group">
                    <label class="form-label">Maksimal Ukuran File (MB)</label>
                    <input type="number" name="max_upload_size" value="<?= $maxUploadSize ?>" class="form-input" min="1" max="50">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipe File Diizinkan</label>
                    <input type="text" name="allowed_types" value="<?= htmlspecialchars($allowedTypes) ?>" class="form-input" placeholder="jpg,jpeg,png,gif">
                    <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 8px;">
                        Pisahkan dengan koma (,)
                    </p>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Konfigurasi</button>
            </form>
        </div>

        <!-- Backup & Info -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 24px;">Backup & Info</h2>
            
            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 16px; margin-bottom: 12px;">Database Backup</h3>
                <p style="font-size: 14px; color: var(--color-text-secondary); margin-bottom: 16px;">
                    Download backup database dalam format SQL. File akan berisi struktur tabel dan data.
                </p>
                <a href="<?= url('modules/admin/settings.php?backup=1') ?>" class="btn btn-secondary" style="width: 100%;">
                    💾 Download Backup
                </a>
            </div>
            
            <div style="border-top: 1px solid var(--color-border); padding-top: 24px;">
                <h3 style="font-size: 16px; margin-bottom: 12px;">Informasi Sistem</h3>
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--color-text-muted);">PHP Version</span>
                        <span style="font-weight: 600;"><?= phpversion() ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--color-text-muted);">MySQL Version</span>
                        <span style="font-weight: 600;"><?= $db->fetchOne("SELECT VERSION() as v")['v'] ?? 'N/A' ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--color-text-muted);">Server Software</span>
                        <span style="font-weight: 600;"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--color-text-muted);">Upload Max Size</span>
                        <span style="font-weight: 600;"><?= ini_get('upload_max_filesize') ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--color-text-muted);">Post Max Size</span>
                        <span style="font-weight: 600;"><?= ini_get('post_max_size') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>