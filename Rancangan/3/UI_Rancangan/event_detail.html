<?php
/**
 * Modul Detail Event - Ruang Unila
 * 
 * Menampilkan detail lengkap event dengan tombol pendaftaran.
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

// Debug mode: tampilkan informasi pendaftaran
if (isset($_GET['debug']) && $_GET['debug'] === 'event_register') {
    error_log("DEBUG detail.php:");
    error_log("  Event ID: $eventId");
    error_log("  User ID: " . ($_SESSION['user_id'] ?? 'null'));
    error_log("  isRegistered: " . ($isRegistered ? 'true' : 'false'));
    error_log("  Session keys: " . implode(', ', array_keys($_SESSION)));
}

// Hitung statistik
$remainingQuota = $event->getRemainingQuota($eventId);
$participantCount = $event->countParticipants($eventId);

// Hitung countdown
$now = new DateTime();
$deadline = new DateTime($eventData['registration_deadline']);
$eventDate = new DateTime($eventData['event_date']);

if ($deadline < $now) {
    $countdown = 'Pendaftaran ditutup';
    $countdownClass = 'closed';
    $canRegister = false;
} else {
    $diff = $now->diff($deadline);
    if ($diff->days == 0) {
        $countdown = 'Hari ini';
    } elseif ($diff->days == 1) {
        $countdown = 'Besok';
    } else {
        $countdown = $diff->days . ' hari lagi';
    }
    $countdownClass = $diff->days <= 3 ? 'urgent' : 'normal';
    $canRegister = $remainingQuota > 0;
}

// Set page title
$pageTitle = $eventData['title'];

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0;">
    <!-- Breadcrumb -->
    <nav style="margin-bottom: 24px; font-size: 14px;">
        <a href="<?= url('modules/events/list.php') ?>" style="color: var(--color-text-muted);">Event</a>
        <span style="margin: 0 8px; color: var(--color-text-muted);">/</span>
        <span style="color: var(--color-text-primary);"><?= htmlspecialchars($eventData['title']) ?></span>
    </nav>
    
    <div style="display: grid; grid-template-columns: 1fr 350px; gap: 32px; max-width: 100%; overflow: hidden;">
        <!-- Main Content -->
        <div style="min-width: 0;">
            <!-- Event Image -->
            <div style="border-radius: 12px; overflow: hidden; margin-bottom: 24px; height: 400px; background: <?= $eventData['image'] ? 'transparent' : 'linear-gradient(135deg, #16a34a 0%, #22c55e 100%)' ?>;">
                <?php if ($eventData['image']): ?>
                    <img src="<?= url('assets/images/uploads/events/' . $eventData['image']) ?>" 
                         alt="<?= htmlspecialchars($eventData['title']) ?>"
                         style="width: 100%; height: 100%; object-fit: cover; max-width: 100%;">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: 700;">
                        Tidak Ada Gambar
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Event Title -->
            <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 16px;">
                <?= htmlspecialchars($eventData['title']) ?>
            </h1>
            
            <!-- Event Meta -->
            <div style="display: flex; flex-wrap: wrap; gap: 24px; margin-bottom: 24px; padding: 20px; background: white; border-radius: 12px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 48px; height: 48px; background: var(--color-primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--color-text-muted);">Tanggal & Waktu</div>
                        <div style="font-weight: 600;"><?= formatDate($eventData['event_date'], 'd F Y') ?></div>
                        <div style="font-size: 14px; color: var(--color-text-muted);"><?= date('H:i', strtotime($eventData['event_date'])) ?> WIB</div>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 48px; height: 48px; background: var(--color-primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--color-text-muted);">Lokasi</div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($eventData['location']) ?></div>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 48px; height: 48px; background: var(--color-primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--color-text-muted);">Peserta</div>
                        <div style="font-weight: 600;"><?= $participantCount ?> / <?= $eventData['max_participants'] ?></div>
                        <div style="font-size: 14px; color: var(--color-success);"><?= $remainingQuota ?> kuota tersisa</div>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 48px; height: 48px; background: var(--color-primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--color-text-muted);">Biaya</div>
                        <div style="font-weight: 600; color: var(--color-primary);">
                            <?= $eventData['fee'] > 0 ? 'Rp ' . formatNumber($eventData['fee']) : 'Gratis' ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Event Description -->
            <div style="background: white; padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 16px;">Deskripsi Event</h2>
                <div style="line-height: 1.8; color: var(--color-text-secondary);">
                    <?= nl2br(htmlspecialchars($eventData['description'])) ?>
                </div>
            </div>
            
            <!-- Organizer Info -->
            <div style="background: white; padding: 24px; border-radius: 12px;">
                <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 16px;">Diselenggarakan Oleh</h2>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 56px; height: 56px; background: var(--color-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; font-weight: 700;">
                        <?= strtoupper(substr($eventData['organizer_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 16px;"><?= htmlspecialchars($eventData['organizer_name']) ?></div>
                        <div style="color: var(--color-text-muted); font-size: 14px;">Organisasi</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div>
            <!-- Registration Card -->
            <div style="background: white; padding: 24px; border-radius: 12px; position: sticky; top: 24px; margin-bottom: 24px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 4px;">Deadline Pendaftaran</div>
                    <div style="font-weight: 700; font-size: 18px; color: var(--color-primary);">
                        <?= formatDate($eventData['registration_deadline'], 'd F Y') ?>
                    </div>
                    <div style="display: inline-block; margin-top: 8px; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background: <?= $countdownClass === 'urgent' ? 'var(--color-danger)' : ($countdownClass === 'closed' ? 'var(--color-text-muted)' : 'var(--color-success)') ?>; color: white;">
                        <?= $countdown ?>
                    </div>
                </div>
                
                <div style="border-top: 1px solid var(--color-border); padding-top: 20px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="color: var(--color-text-muted);">Harga Tiket</span>
                        <span style="font-weight: 700; color: var(--color-primary);">
                            <?= $eventData['fee'] > 0 ? 'Rp ' . formatNumber($eventData['fee']) : 'Gratis' ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--color-text-muted);">Kuota Tersisa</span>
                        <span style="font-weight: 600; color: <?= $remainingQuota > 0 ? 'var(--color-success)' : 'var(--color-danger)' ?>;">
                            <?= $remainingQuota ?> peserta
                        </span>
                    </div>
                </div>
                
                 <?php if ($isRegistered): ?>
                    <div style="background: var(--color-success); color: white; padding: 12px; border-radius: 8px; text-align: center; margin-bottom: 12px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Anda Sudah Terdaftar
                    </div>
                    <div style="text-align: center; font-size: 12px; color: var(--color-text-muted); margin-bottom: 12px;">
                        (Debug: Event ID <?= $eventId ?>, User ID <?= $_SESSION['user_id'] ?? 'null' ?>)
                    </div>
                    <a href="<?= url('modules/events/registrations.php?id=' . $eventId) ?>" class="btn btn-outline" style="width: 100%;">
                        Lihat Status Pendaftaran
                    </a>
                    
                <?php elseif (!$canRegister): ?>
                    <button class="btn btn-secondary" style="width: 100%;" disabled>
                        <?= $remainingQuota <= 0 ? 'Kuota Penuh' : 'Pendaftaran Ditutup' ?>
                    </button>
                    
                <?php else: ?>
                    <a href="<?= url('modules/events/register.php?id=' . $eventId) ?>" class="btn btn-primary" style="width: 100%;">
                        Daftar Sekarang
                    </a>
                <?php endif; ?>
                
                <!-- Share Buttons -->
                <div style="border-top: 1px solid var(--color-border); padding-top: 20px; margin-top: 20px;">
                    <div style="font-size: 14px; color: var(--color-text-muted); margin-bottom: 12px; text-align: center;">Bagikan Event</div>
                    <div style="display: flex; gap: 8px; justify-content: center;">
                        <button onclick="shareToWhatsApp()" class="btn btn-sm btn-outline" style="flex: 1;">
                            WhatsApp
                        </button>
                        <button onclick="copyLink()" class="btn btn-sm btn-outline" style="flex: 1;">
                            Salin Link
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Event Stats -->
            <div style="background: white; padding: 24px; border-radius: 12px;">
                <h3 style="font-family: var(--font-heading); font-size: 16px; margin-bottom: 16px;">Statistik Event</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div style="text-align: center; padding: 16px; background: var(--color-bg); border-radius: 8px;">
                        <div style="font-size: 24px; font-weight: 700; color: var(--color-primary);"><?= $participantCount ?></div>
                        <div style="font-size: 12px; color: var(--color-text-muted);">Peserta</div>
                    </div>
                    <div style="text-align: center; padding: 16px; background: var(--color-bg); border-radius: 8px;">
                        <div style="font-size: 24px; font-weight: 700; color: var(--color-success);"><?= $remainingQuota ?></div>
                        <div style="font-size: 12px; color: var(--color-text-muted);">Kuota</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function shareToWhatsApp() {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent('<?= addslashes($eventData['title']) ?> - Ruang Unila');
    window.open(`https://wa.me/?text=${text}%20${url}`, '_blank');
}

function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        alert('Link berhasil disalin!');
    });
}
</script>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>