<?php
/**
 * Modul Manage Events - Ruang Unila
 * 
 * Modul ini menangani pengelolaan event oleh organisasi/admin.
 */

// Define APP_ROOT jika belum didefinisikan
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

// Include konfigurasi dan fungsi
require_once APP_ROOT . '/config/constants.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/includes/functions.php';

// Include classes
require_once APP_ROOT . '/classes/User.php';
require_once APP_ROOT . '/classes/Event.php';

// Inisialisasi
$session = Session::getInstance();
$event = new Event();

// Cek login dan role
if (!$session->isLoggedIn()) {
    $session->setFlash('error', 'Anda harus login untuk mengelola event.');
    header('Location: ' . url('modules/auth/login.php'));
    exit;
}

$userRole = $session->getUserRole();
$userId = $session->getUserId();

// Cegah mahasiswa akses halaman ini
if ($userRole === ROLE_STUDENT) {
    $session->setFlash('error', 'Anda tidak memiliki akses ke halaman ini.');
    header('Location: ' . url());
    exit;
}

// Proses aksi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $eventId = (int)($_POST['event_id'] ?? 0);
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Validasi CSRF
    if (!verifyCSRFToken($csrfToken)) {
        $session->setFlash('error', 'Token keamanan tidak valid.');
        header('Location: ' . url('modules/events/manage.php'));
        exit;
    }
    
    // Ambil data event
    $eventItem = $event->getById($eventId);
    if (!$eventItem) {
        $session->setFlash('error', 'Event tidak ditemukan.');
    } else {
        // Cek akses (Admin bisa semua, Organisasi hanya miliknya)
        if ($userRole === ROLE_ADMIN || $eventItem['organizer_id'] === $userId) {
            switch ($action) {
                case 'publish':
                    $event->updateStatus($eventId, 'upcoming');
                    $session->setFlash('success', 'Event berhasil dipublikasikan!');
                    break;
                case 'cancel':
                    $event->updateStatus($eventId, 'cancelled');
                    $session->setFlash('success', 'Event berhasil dibatalkan.');
                    break;
                case 'delete':
                    $event->delete($eventId);
                    $session->setFlash('success', 'Event berhasil dihapus.');
                    break;
            }
        } else {
            $session->setFlash('error', 'Anda tidak memiliki akses untuk event ini.');
        }
    }
    
    header('Location: ' . url('modules/events/manage.php'));
    exit;
}

// Filter dan Pagination
$statusFilter = isset($_GET['status']) ? cleanInput($_GET['status']) : null;
$page = getCurrentPage();
$perPage = 10;

if ($userRole === ROLE_ADMIN) {
    $result = $event->getAll($page, $perPage, $statusFilter);
    $statusCounts = $event->countByStatus();
} else {
    $result = $event->getByOrganizer($userId, $page, $perPage, $statusFilter);
    $statusCounts = $event->countByStatusByOrganizer($userId);
}

$eventList = $result['events'];
$totalPages = $result['total_pages'];
$csrfToken = generateCSRFToken();

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">Kelola Event</h1>
            <p style="color: var(--color-text-secondary);">
                <?= $userRole === ROLE_ADMIN ? 'Kelola semua event di sistem' : 'Kelola event organisasi Anda' ?>
            </p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="<?= url('modules/events/admin_payments.php') ?>" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                </svg>
                Verifikasi Pembayaran
            </a>
            <a href="<?= url('modules/events/create.php') ?>" class="btn btn-primary">+ Buat Event Baru</a>
        </div>
    </div>

    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 32px;">
        <?php foreach ($statusCounts as $status => $count): ?>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: var(--shadow-sm);">
                <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase;"><?= $status ?></div>
                <div style="font-size: 24px; font-weight: 700;"><?= $count ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Filter -->
    <div style="display: flex; gap: 12px; margin-bottom: 24px; overflow-x: auto; padding-bottom: 8px;">
        <a href="<?= url('modules/events/manage.php') ?>" class="btn <?= !$statusFilter ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Semua</a>
        <a href="<?= url('modules/events/manage.php?status=upcoming') ?>" class="btn <?= $statusFilter === 'upcoming' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Upcoming</a>
        <a href="<?= url('modules/events/manage.php?status=pending') ?>" class="btn <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Pending</a>
        <a href="<?= url('modules/events/manage.php?status=cancelled') ?>" class="btn <?= $statusFilter === 'cancelled' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Cancelled</a>
    </div>

    <!-- Table -->
    <div style="background: white; border-radius: 12px; box-shadow: var(--shadow-sm); overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: var(--color-bg); border-bottom: 1px solid var(--color-border-light);">
                    <th style="padding: 16px; font-size: 12px; color: var(--color-text-muted);">EVENT</th>
                    <th style="padding: 16px; font-size: 12px; color: var(--color-text-muted);">TANGGAL</th>
                    <th style="padding: 16px; font-size: 12px; color: var(--color-text-muted);">BIAYA</th>
                    <th style="padding: 16px; font-size: 12px; color: var(--color-text-muted);">STATUS</th>
                    <th style="padding: 16px; font-size: 12px; color: var(--color-text-muted); white-space: nowrap;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($eventList) > 0): ?>
                    <?php foreach ($eventList as $item): ?>
                        <tr style="border-bottom: 1px solid var(--color-border-light);">
                            <td style="padding: 16px;">
                                <div style="display: flex; gap: 12px; align-items: center;">
                                    <div style="width: 48px; height: 48px; background: url('<?= $item['image'] ? url('assets/images/uploads/events/'.$item['image']) : url('assets/images/placeholder.jpg') ?>') center/cover; border-radius: 6px;"></div>
                                    <div style="min-width: 0;">
                                        <div style="font-weight: 600; font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($item['title']) ?></div>
                                        <div style="font-size: 12px; color: var(--color-text-muted);"><?= htmlspecialchars($item['location']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 16px; font-size: 13px;">
                                <?= formatDate($item['event_date'], 'd M Y') ?>
                            </td>
                            <td style="padding: 16px; font-size: 13px;">
                                <?= $item['fee'] > 0 ? 'Rp '.formatNumber($item['fee']) : 'Gratis' ?>
                            </td>
                            <td style="padding: 16px;">
                                <span style="padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; background: var(--color-bg); color: var(--color-text-muted);">
                                    <?= strtoupper($item['status']) ?>
                                </span>
                            </td>
                            <td style="padding: 16px;">
                                <div style="display: flex; gap: 8px; flex-wrap: nowrap;">
                                    <a href="<?= url('modules/events/detail.php?id='.$item['event_id']) ?>" class="btn btn-secondary btn-sm" style="white-space: nowrap;">Lihat</a>
                                    <a href="<?= url('modules/events/edit.php?id='.$item['event_id']) ?>" class="btn btn-secondary btn-sm" style="white-space: nowrap;">Edit</a>
                                    
                                    <?php if ($item['fee'] > 0): ?>
                                        <a href="<?= url('modules/events/admin_payments.php') ?>" class="btn btn-outline btn-sm" style="color: var(--color-warning); border-color: var(--color-warning); white-space: nowrap;">Peserta</a>
                                    <?php endif; ?>
                                    
                                    <?php if ($userRole === ROLE_ADMIN && $item['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="action" value="publish">
                                            <input type="hidden" name="event_id" value="<?= $item['event_id'] ?>">
                                            <button type="submit" class="btn btn-primary btn-sm" style="white-space: nowrap;">Verify</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Hapus event ini?')">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="event_id" value="<?= $item['event_id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-error); border-color: var(--color-error); white-space: nowrap;">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="padding: 32px; text-align: center; color: var(--color-text-muted);">Belum ada event.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include APP_ROOT . '/includes/footer.php'; ?>
