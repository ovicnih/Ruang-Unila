<?php
/**
 * Modul Pembayaran Event - Ruang Unila
 * 
 * Upload bukti pembayaran untuk event berbayar.
 * 
 * @package RuangUnila
 * @subpackage Modules/Events
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
$event = new Event();
$db = Database::getInstance();
$errors = [];
$success = false;

// Validasi parameter registration number
if (!isset($_GET['reg']) || empty($_GET['reg'])) {
    setFlashMessage('error', 'Nomor pendaftaran tidak valid.');
    redirect('modules/events/list.php');
}

$registrationNumber = sanitize($_GET['reg']);

// Cek apakah ada data pending di session (flow baru)
$pendingReg = $_SESSION['pending_registration'] ?? null;

// Jika ada data pending di session, gunakan data tersebut
if ($pendingReg && $pendingReg['registration_number'] === $registrationNumber && $pendingReg['user_id'] == $_SESSION['user_id']) {
    $registration = $pendingReg;
    $isPendingRegistration = true;
} 
// Jika tidak, cek di database (flow lama - untuk kompatibilitas)
else {
    $sql = "SELECT er.*, e.title, e.fee, e.event_date, e.location
            FROM event_registrations er
            JOIN events e ON er.event_id = e.event_id
            WHERE er.registration_number = :reg_number AND er.user_id = :user_id";
    
    $registration = $db->fetchOne($sql, [
        ':reg_number' => $registrationNumber,
        ':user_id' => $_SESSION['user_id']
    ]);
    
    if (!$registration) {
        setFlashMessage('error', 'Pendaftaran tidak ditemukan.');
        redirect('modules/events/list.php');
    }
    
    $isPendingRegistration = false;
    
    // Cek apakah sudah upload bukti
    if ($registration['payment_status'] === 'paid' || $registration['payment_status'] === 'pending_verification') {
        setFlashMessage('info', 'Pembayaran sudah diupload/dikonfirmasi.');
        redirect('modules/events/detail.php?id=' . $registration['event_id']);
    }
}

// Proses upload bukti pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid.';
    }
    
    // Validasi file upload
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Bukti pembayaran wajib diupload.';
    } else {
        $file = $_FILES['payment_proof'];
        
        // Validasi tipe file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = 'Format file harus JPG atau PNG.';
        }
        
        // Validasi ukuran file (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran file maksimal 2MB.';
        }
        
        // Validasi upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Gagal mengupload file. Silakan coba lagi.';
        }
    }
    
    // Jika tidak ada error, proses upload
    if (count($errors) === 0) {
        // Generate nama file unik
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'payment_' . $registrationNumber . '_' . time() . '.' . $extension;
        $uploadPath = APP_ROOT . '/assets/images/uploads/payments/';
        
        // Buat direktori jika belum ada
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        // Upload file
        if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
            // Flow baru: data dari session - insert ke database
            if ($isPendingRegistration && $pendingReg) {
                $sql = "INSERT INTO event_registrations 
                        (event_id, user_id, registration_number, participant_name, participant_email, notes, 
                         payment_proof, payment_status, status, created_at) 
                        VALUES (:event_id, :user_id, :registration_number, :participant_name, :participant_email, :notes, 
                                :payment_proof, 'pending_verification', 'pending', NOW())";
                
                $params = [
                    ':event_id' => $pendingReg['event_id'],
                    ':user_id' => $pendingReg['user_id'],
                    ':registration_number' => $pendingReg['registration_number'],
                    ':participant_name' => $pendingReg['participant_name'],
                    ':participant_email' => $pendingReg['participant_email'],
                    ':notes' => $pendingReg['notes'] ?? '',
                    ':payment_proof' => $filename
                ];
                
                try {
                    $stmt = $db->executeQuery($sql, $params);
                    
                    if ($stmt) {
                        // Hapus session pending
                        unset($_SESSION['pending_registration']);
                        
                        $success = true;
                        setFlashMessage('success', 'Pendaftaran dan pembayaran berhasil! Menunggu verifikasi admin.');
                        redirect('modules/events/detail.php?id=' . $pendingReg['event_id']);
                    } else {
                        echo "<h1>Query failed without exception</h1>";
                        die();
                    }
                } catch (Exception $e) {
                    echo "<h1>Debug Database Error</h1>";
                    echo "<pre>";
                    echo "Error Message: " . $e->getMessage() . "\n";
                    echo "Error Code: " . $e->getCode() . "\n";
                    echo "\nSQL: $sql\n";
                    print_r($params);
                    echo "</pre>";
                    die();
                }
            } 
            // Flow lama: data sudah di database - update
            else {
                $updateSql = "UPDATE event_registrations 
                              SET payment_proof = :proof, 
                                  payment_status = 'pending_verification',
                                  updated_at = NOW()
                              WHERE registration_id = :id";
                
                $stmt = $db->executeQuery($updateSql, [
                    ':proof' => $filename,
                    ':id' => $registration['registration_id']
                ]);
                
                if ($stmt) {
                    $success = true;
                    setFlashMessage('success', 'Bukti pembayaran berhasil diupload! Menunggu verifikasi admin.');
                    redirect('modules/events/detail.php?id=' . $registration['event_id']);
                } else {
                    $errors[] = 'Gagal menyimpan data. Silakan coba lagi.';
                }
            }
        } else {
            $errors[] = 'Gagal mengupload file. Silakan coba lagi.';
        }
    }
}

// Set page title
$pageTitle = 'Pembayaran Event';

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0; max-width: 800px; margin: 0 auto;">
    <!-- Breadcrumb -->
    <nav style="margin-bottom: 24px; font-size: 14px;">
        <a href="<?= url('modules/events/list.php') ?>" style="color: var(--color-text-muted);">Event</a>
        <span style="margin: 0 8px; color: var(--color-text-muted);">/</span>
        <a href="<?= url('modules/events/detail.php?id=' . $registration['event_id']) ?>" style="color: var(--color-text-muted);"><?= htmlspecialchars($registration['event_title'] ?? $registration['title'] ?? 'Detail Event') ?></a>
        <span style="margin: 0 8px; color: var(--color-text-muted);">/</span>
        <span style="color: var(--color-text-primary);">Pembayaran</span>
    </nav>
    
    <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 32px;">
        Upload Bukti Pembayaran
    </h1>
    
    <!-- Error Messages -->
    <?php if (count($errors) > 0): ?>
        <div style="background: var(--color-danger); color: white; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px;">
        <!-- Form Upload -->
        <div style="background: white; padding: 32px; border-radius: 12px;">
            <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 24px;">Form Pembayaran</h2>
            
            <!-- Payment Info -->
            <div style="background: var(--color-bg); padding: 20px; border-radius: 8px; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span style="color: var(--color-text-muted);">Nomor Pendaftaran</span>
                    <span style="font-weight: 700; font-family: monospace;"><?= htmlspecialchars($registration['registration_number']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span style="color: var(--color-text-muted);">Total Pembayaran</span>
                    <span style="font-weight: 700; color: var(--color-primary); font-size: 20px;">
                        Rp <?= formatNumber($registration['fee']) ?>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--color-text-muted);">Status</span>
                    <span style="font-weight: 600; color: var(--color-warning);">
                        Menunggu Pembayaran
                    </span>
                </div>
            </div>
            
            <!-- Participant Info (untuk flow baru) -->
            <?php if ($isPendingRegistration): ?>
            <div style="background: var(--color-primary-light); padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                <h4 style="font-size: 14px; color: var(--color-primary); margin-bottom: 12px;">Data Peserta:</h4>
                <div style="display: flex; flex-direction: column; gap: 4px; font-size: 14px;">
                    <div><strong>Nama:</strong> <?= htmlspecialchars($registration['participant_name']) ?></div>
                    <div><strong>Email:</strong> <?= htmlspecialchars($registration['participant_email']) ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Bank Transfer Info -->
            <div style="background: var(--color-primary-light); padding: 20px; border-radius: 8px; margin-bottom: 24px;">
                <h3 style="font-size: 16px; margin-bottom: 12px; color: var(--color-primary);">Transfer ke Rekening:</h3>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Bank</span>
                        <strong>Bank BRI</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>No. Rekening</span>
                        <strong>1234-5678-9012-3456</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Atas Nama</span>
                        <strong>Ruang Unila</strong>
                    </div>
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                        Upload Bukti Pembayaran *
                    </label>
                    <input type="file" name="payment_proof" accept="image/jpeg,image/png,image/jpg" 
                           class="form-input" required>
                    <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 8px;">
                        Format: JPG, PNG. Maksimal 2MB.
                    </p>
                </div>
                
                <!-- Preview Area -->
                <div id="preview-area" style="display: none; margin-bottom: 24px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Preview</label>
                    <img id="preview-image" src="" alt="Preview" style="max-width: 100%; border-radius: 8px; border: 1px solid var(--color-border);">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Upload Bukti Pembayaran
                </button>
            </form>
        </div>
        
        <!-- Event Summary -->
        <div>
            <div style="background: white; padding: 24px; border-radius: 12px; position: sticky; top: 24px;">
                <h3 style="font-family: var(--font-heading); font-size: 16px; margin-bottom: 16px;">Detail Event</h3>
                
                <h4 style="margin-bottom: 12px;"><?= htmlspecialchars($registration['event_title'] ?? $registration['title']) ?></h4>
                
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px; margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <?= isset($registration['event_date']) ? formatDate($registration['event_date'], 'd M Y') : '-' ?>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <?= htmlspecialchars($registration['location']) ?>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div style="border-top: 1px solid var(--color-border); padding-top: 16px;">
                    <h4 style="font-size: 14px; margin-bottom: 12px;">Instruksi:</h4>
                    <ol style="font-size: 13px; color: var(--color-text-muted); margin: 0; padding-left: 20px;">
                        <li style="margin-bottom: 8px;">Transfer sesuai nominal yang tertera</li>
                        <li style="margin-bottom: 8px;">Simpan bukti transfer</li>
                        <li style="margin-bottom: 8px;">Upload foto/screenshot bukti transfer</li>
                        <li>Tunggu verifikasi dari admin (1x24 jam)</li>
                    </ol>
                </div>
                
                <!-- Contact -->
                <div style="margin-top: 16px; padding: 12px; background: var(--color-bg); border-radius: 8px; font-size: 12px;">
                    <strong>Butuh bantuan?</strong><br>
                    Hubungi: admin@ruangunila.com
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Preview image before upload
document.querySelector('input[name="payment_proof"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-image').src = e.target.result;
            document.getElementById('preview-area').style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>