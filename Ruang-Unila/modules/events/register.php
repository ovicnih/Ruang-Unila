<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
/**
 * Modul Pendaftaran Event - Ruang Unila
 * 
 * Form pendaftaran event dengan validasi kuota dan deadline.
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

// Require login sebagai mahasiswa
requireLogin();

if (!hasRole(ROLE_STUDENT)) {
    setFlashMessage('error', 'Hanya mahasiswa yang bisa mendaftar event.');
    redirect('modules/events/list.php');
}

// Inisialisasi
$event = new Event();
$errors = [];

// Validasi parameter ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID event tidak valid.');
    redirect('modules/events/list.php');
}

$eventId = (int) $_GET['id'];

// Ambil data event
$eventData = $event->getById($eventId);

if (!$eventData) {
    setFlashMessage('error', 'Event tidak ditemukan.');
    redirect('modules/events/list.php');
}

// Cek status user sudah terdaftar
$isRegistered = $event->isUserRegistered($eventId, $_SESSION['user_id']);

if ($isRegistered) {
    setFlashMessage('info', 'Anda sudah terdaftar di event ini.');
    redirect('modules/events/detail.php?id=' . $eventId);
}

// Cek deadline
$now = new DateTime();
$deadline = new DateTime($eventData['registration_deadline']);

if ($deadline < $now) {
    setFlashMessage('error', 'Pendaftaran event sudah ditutup.');
    redirect('modules/events/detail.php?id=' . $eventId);
}

// Cek kuota
$remainingQuota = $event->getRemainingQuota($eventId);

if ($remainingQuota <= 0) {
    setFlashMessage('error', 'Kuota event sudah penuh.');
    redirect('modules/events/detail.php?id=' . $eventId);
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid.';
    }
    
    // Validasi input
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Ambil data participant dari form
    $participantName = sanitize($_POST['participant_name'] ?? '');
    $participantEmail = sanitize($_POST['participant_email'] ?? '');
    
    // Validasi participant name & email
    if (empty($participantName)) {
        $errors[] = 'Nama lengkap wajib diisi.';
    }
    if (empty($participantEmail)) {
        $errors[] = 'Email wajib diisi.';
    } elseif (!filter_var($participantEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    
    // Insert ke database (hanya jika tidak ada error validasi)
    if (count($errors) === 0) {
        $db = Database::getInstance();
        
        // Generate nomor pendaftaran
        $registrationNumber = 'REG-' . date('Ymd') . '-' . str_pad($eventId, 4, '0', STR_PAD_LEFT) . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Untuk EVENT GRATIS: langsung insert dan konfirmasi
        if ($eventData['fee'] == 0) {
            $sql = "INSERT INTO event_registrations (event_id, user_id, registration_number, participant_name, participant_email, notes, status, payment_status) 
                    VALUES (:event_id, :user_id, :registration_number, :participant_name, :participant_email, :notes, 'confirmed', 'paid')";
            
            $params = [
                ':event_id' => $eventId,
                ':user_id' => $_SESSION['user_id'],
                ':registration_number' => $registrationNumber,
                ':participant_name' => $participantName,
                ':participant_email' => $participantEmail,
                ':notes' => $notes
            ];
        
            $stmt = $db->executeQuery($sql, $params);
            
            if ($stmt) {
                setFlashMessage('success', 'Pendaftaran berhasil! Nomor pendaftaran: ' . $registrationNumber);
                redirect('modules/events/detail.php?id=' . $eventId);
            } else {
                $errors[] = 'Gagal mendaftar. Silakan coba lagi.';
            }
        } 
        // Untuk EVENT BERBAYAR: simpan data di session, insert setelah upload bukti
        else {
            // Simpan data pendaftaran di session
            $_SESSION['pending_registration'] = [
                'event_id' => $eventId,
                'user_id' => $_SESSION['user_id'],
                'registration_number' => $registrationNumber,
                'participant_name' => $participantName,
                'participant_email' => $participantEmail,
                'notes' => $notes,
                'fee' => $eventData['fee'],
                'event_title' => $eventData['title'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Redirect ke halaman payment
            redirect('modules/events/payment.php?reg=' . $registrationNumber);
        }
    }
}

// Set page title
$pageTitle = 'Daftar Event: ' . $eventData['title'];

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0; max-width: 800px; margin: 0 auto;">
    <!-- Breadcrumb -->
    <nav style="margin-bottom: 24px; font-size: 14px;">
        <a href="<?= url('modules/events/list.php') ?>" style="color: var(--color-text-muted);">Event</a>
        <span style="margin: 0 8px; color: var(--color-text-muted);">/</span>
        <a href="<?= url('modules/events/detail.php?id=' . $eventId) ?>" style="color: var(--color-text-muted);"><?= htmlspecialchars($eventData['title']) ?></a>
        <span style="margin: 0 8px; color: var(--color-text-muted);">/</span>
        <span style="color: var(--color-text-primary);">Pendaftaran</span>
    </nav>
    
    <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 32px;">
        Pendaftaran Event
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
        <!-- Form -->
        <div style="background: white; padding: 32px; border-radius: 12px;">
            <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 24px;">Form Pendaftaran</h2>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Nama Lengkap *</label>
                    <input type="text" name="participant_name" value="<?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>" 
                           class="form-input" required>
                    <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                        Nama akan ditampilkan di sertifikat dan daftar peserta
                    </p>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Email *</label>
                    <input type="email" name="participant_email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" 
                           class="form-input" required>
                    <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                        Email akan digunakan untuk konfirmasi dan informasi event
                    </p>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Catatan (Opsional)</label>
                    <textarea name="notes" class="form-input" rows="4" 
                              placeholder="Tambahkan catatan jika ada..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                </div>
                
                <div style="background: var(--color-bg); padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <input type="checkbox" id="terms" required>
                        <label for="terms" style="font-size: 14px;">Saya menyetujui syarat dan ketentuan pendaftaran</label>
                    </div>
                    <p style="font-size: 12px; color: var(--color-text-muted); margin: 0;">
                        Dengan mendaftar, saya menyatakan bahwa data yang diberikan adalah benar dan saya bersedia mengikuti seluruh rangkaian acara.
                    </p>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Konfirmasi Pendaftaran
                </button>
            </form>
        </div>
        
        <!-- Event Summary -->
        <div>
            <div style="background: white; padding: 24px; border-radius: 12px; position: sticky; top: 24px;">
                <h3 style="font-family: var(--font-heading); font-size: 16px; margin-bottom: 16px;">Ringkasan Event</h3>
                
                <div style="border-radius: 8px; overflow: hidden; margin-bottom: 16px; height: 150px;">
                    <img src="<?= $eventData['image'] ? url('assets/images/uploads/events/' . $eventData['image']) : url('assets/images/placeholder.jpg') ?>" 
                         alt="<?= htmlspecialchars($eventData['title']) ?>"
                         style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                
                <h4 style="margin-bottom: 12px;"><?= htmlspecialchars($eventData['title']) ?></h4>
                
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px; margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <?= formatDate($eventData['event_date'], 'd M Y') ?>, <?= date('H:i', strtotime($eventData['event_date'])) ?> WIB
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <?= htmlspecialchars($eventData['location']) ?>
                    </div>
                </div>
                
                <div style="border-top: 1px solid var(--color-border); padding-top: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: var(--color-text-muted);">Biaya</span>
                        <span style="font-weight: 700; color: var(--color-primary);">
                            <?= $eventData['fee'] > 0 ? 'Rp ' . formatNumber($eventData['fee']) : 'Gratis' ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--color-text-muted);">Kuota Tersisa</span>
                        <span style="font-weight: 600;"><?= $remainingQuota ?> peserta</span>
                    </div>
                </div>
                
                <?php if ($eventData['fee'] > 0): ?>
                    <div style="margin-top: 16px; padding: 12px; background: var(--color-warning-light); border-radius: 8px; font-size: 12px;">
                        <strong>Catatan:</strong> Setelah mendaftar, Anda perlu melakukan pembayaran untuk mengkonfirmasi pendaftaran.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>