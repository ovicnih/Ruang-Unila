<?php
/**
 * Pendaftaran Saya - Ruang Unila
 * 
 * Halaman mahasiswa untuk melihat daftar event yang diikuti, status pembayaran, dan akses e-ticket.
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

// Start output buffering
ob_start();

// Include konfigurasi
require_once APP_ROOT . '/config/constants.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/includes/functions.php';

// Include classes
require_once APP_ROOT . '/classes/User.php';
require_once APP_ROOT . '/classes/Event.php';

// Require login
requireLogin();

// Inisialisasi
$session = Session::getInstance();
$userId = $session->getUserId();
$db = Database::getInstance();
$pageTitle = 'Pendaftaran Saya';

// Cek apakah user adalah mahasiswa
if ($session->getUserRole() !== 'mahasiswa') {
    setFlashMessage('error', 'Halaman ini hanya untuk mahasiswa.');
    redirect('index.php');
}

// Ambil parameter filter
$paymentStatusFilter = isset($_GET['payment_status']) ? cleanInput($_GET['payment_status']) : null;
$page = getCurrentPage();
$perPage = ITEMS_PER_PAGE;

// Validasi payment status filter
$validPaymentStatuses = ['pending', 'unpaid', 'verified', 'rejected'];
if ($paymentStatusFilter && !in_array($paymentStatusFilter, $validPaymentStatuses)) {
    $paymentStatusFilter = null;
}

// Ambil data registrations user ini
$sql = "SELECT er.registration_id, er.registration_number, er.payment_status, er.created_at, er.payment_proof,
               e.event_id, e.title as event_title, e.event_date, e.location, e.fee, e.description
        FROM event_registrations er
        LEFT JOIN events e ON er.event_id = e.event_id
        WHERE er.user_id = :user_id";

$params = [':user_id' => $userId];

// Mapping filter ke nilai database yang sesungguhnya
if ($paymentStatusFilter === 'verified') {
    $sql .= " AND er.payment_status IN ('paid', 'verified')";
} elseif ($paymentStatusFilter === 'pending') {
    $sql .= " AND er.payment_status IN ('pending', 'pending_verification', 'unpaid')";
} elseif ($paymentStatusFilter === 'rejected') {
    $sql .= " AND er.payment_status = 'rejected'";
}

// Hitung total
$countSql = str_replace(
    "SELECT er.registration_id, er.registration_number, er.payment_status, er.created_at, er.payment_proof, e.event_id, e.title as event_title, e.event_date, e.location, e.fee, e.description",
    "SELECT COUNT(*) as total",
    $sql
);
$resultCount = $db->fetchOne($countSql, $params);
$total = $resultCount ? (int) $resultCount['total'] : 0;
$totalPages = getTotalPages($total, $perPage);

// Ambil data dengan pagination
$sql .= " ORDER BY er.created_at DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $perPage;
$params[':offset'] = getPaginationOffset($page, $perPage);

$registrations = $db->fetchAll($sql, $params);

// Hitung statistik dengan mapping yang akurat
$sqlStats = "SELECT payment_status, COUNT(*) as count FROM event_registrations WHERE user_id = :user_id GROUP BY payment_status";
$statsResult = $db->fetchAll($sqlStats, [':user_id' => $userId]);
$stats = [
    'verified' => 0,
    'pending' => 0,
    'rejected' => 0,
    'total' => 0
];

foreach ($statsResult as $row) {
    $pStat = $row['payment_status'];
    $cnt = (int) $row['count'];
    $stats['total'] += $cnt;
    
    if ($pStat === 'paid' || $pStat === 'verified') {
        $stats['verified'] += $cnt;
    } elseif ($pStat === 'pending_verification' || $pStat === 'pending' || $pStat === 'unpaid') {
        $stats['pending'] += $cnt;
    } elseif ($pStat === 'rejected') {
        $stats['rejected'] += $cnt;
    }
}

// Include header
include APP_ROOT . '/includes/header.php';

// Flush output buffer
ob_end_flush();
?>

<div class="container" style="padding: 32px 0; max-width: 1000px; margin: 0 auto;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">
                Pendaftaran Saya
            </h1>
            <p style="color: var(--color-text-secondary); font-size: 14px;">
                Kelola pendaftaran event, cek status verifikasi, dan akses e-ticket Anda
            </p>
        </div>
        <a href="<?= url('modules/events/index.php') ?>" class="btn btn-primary">
            Cari Event Lainnya
        </a>
    </div>

    <!-- Statistics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px;">
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: var(--shadow-sm); border: 1px solid var(--color-border-light);">
            <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; font-weight: 700;">
                Total Pendaftaran
            </div>
            <div style="font-size: 28px; font-weight: 800; color: var(--color-text-primary);">
                <?= $stats['total'] ?>
            </div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: var(--shadow-sm); border: 1px solid var(--color-border-light);">
            <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; font-weight: 700;">
                Verified (Tiket Aktif)
            </div>
            <div style="font-size: 28px; font-weight: 800; color: var(--color-success);">
                <?= $stats['verified'] ?>
            </div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: var(--shadow-sm); border: 1px solid var(--color-border-light);">
            <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; font-weight: 700;">
                Menunggu Verifikasi
            </div>
            <div style="font-size: 28px; font-weight: 800; color: var(--color-warning);">
                <?= $stats['pending'] ?>
            </div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: var(--shadow-sm); border: 1px solid var(--color-border-light);">
            <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; font-weight: 700;">
                Ditolak / Batal
            </div>
            <div style="font-size: 28px; font-weight: 800; color: var(--color-error);">
                <?= $stats['rejected'] ?>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div style="display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap;">
        <a href="<?= url('modules/profile/my-registrations.php') ?>" 
           class="btn <?= !$paymentStatusFilter ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            Semua (<?= $stats['total'] ?>)
        </a>
        <a href="<?= url('modules/profile/my-registrations.php?payment_status=pending') ?>" 
           class="btn <?= $paymentStatusFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            Pending (<?= $stats['pending'] ?>)
        </a>
        <a href="<?= url('modules/profile/my-registrations.php?payment_status=verified') ?>" 
           class="btn <?= $paymentStatusFilter === 'verified' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            Verified (<?= $stats['verified'] ?>)
        </a>
        <a href="<?= url('modules/profile/my-registrations.php?payment_status=rejected') ?>" 
           class="btn <?= $paymentStatusFilter === 'rejected' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            Rejected (<?= $stats['rejected'] ?>)
        </a>
    </div>

    <!-- Registrations List -->
    <?php if (!empty($registrations) && is_array($registrations) && count($registrations) > 0): ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($registrations as $reg): ?>
                <?php if (!empty($reg) && isset($reg['registration_number'])): ?>
                <div style="background: white; border-radius: 16px; padding: 24px; box-shadow: var(--shadow-sm); border: 1px solid var(--color-border-light); display: flex; gap: 24px; align-items: center; flex-wrap: wrap;">
                    <!-- Event Info -->
                    <div style="flex: 1; min-width: 250px;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap;">
                            <span style="font-family: monospace; font-size: 13px; font-weight: 700; color: var(--color-text-muted); background: var(--color-background); padding: 4px 8px; border-radius: 6px;">
                                <?= htmlspecialchars($reg['registration_number']) ?>
                            </span>
                            <?php
                            $pStat = $reg['payment_status'];
                            if ($pStat === 'paid' || $pStat === 'verified') {
                                $badgeText = 'Verified / Paid';
                                $badgeStyle = ['bg' => 'rgba(16, 185, 129, 0.15)', 'color' => '#10B981'];
                            } elseif ($pStat === 'pending_verification' || $pStat === 'pending') {
                                $badgeText = 'Menunggu Verifikasi';
                                $badgeStyle = ['bg' => 'rgba(245, 158, 11, 0.15)', 'color' => '#F59E0B'];
                            } elseif ($pStat === 'unpaid') {
                                $badgeText = 'Belum Dibayar';
                                $badgeStyle = ['bg' => 'rgba(239, 68, 68, 0.15)', 'color' => '#EF4444'];
                            } elseif ($pStat === 'rejected') {
                                $badgeText = 'Ditolak';
                                $badgeStyle = ['bg' => 'rgba(239, 68, 68, 0.2)', 'color' => '#DC2626'];
                            } else {
                                $badgeText = $pStat;
                                $badgeStyle = ['bg' => 'rgba(245, 158, 11, 0.15)', 'color' => '#F59E0B'];
                            }
                            ?>
                            <span style="display: inline-flex; padding: 4px 10px; background: <?= $badgeStyle['bg'] ?>; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; color: <?= $badgeStyle['color'] ?>; letter-spacing: 0.5px;">
                                <?= htmlspecialchars($badgeText) ?>
                            </span>
                        </div>
                        <h3 style="font-family: var(--font-heading); font-size: 22px; font-weight: 700; color: var(--color-text-primary); margin-bottom: 10px; line-height: 1.3;">
                            <?= isset($reg['event_title']) ? htmlspecialchars($reg['event_title']) : 'Event Tidak Diketahui' ?>
                        </h3>
                        <div style="display: flex; gap: 16px; font-size: 13px; color: var(--color-text-secondary); margin-bottom: 12px; flex-wrap: wrap;">
                            <span>📅 <?= isset($reg['event_date']) ? formatDate($reg['event_date']) : '-' ?></span>
                            <?php if (isset($reg['location']) && $reg['location']): ?>
                                <span>📍 <?= htmlspecialchars($reg['location']) ?></span>
                            <?php endif; ?>
                            <span style="font-weight: 700; color: var(--color-primary);">💳 <?= isset($reg['fee']) && $reg['fee'] > 0 ? 'Rp ' . formatNumber($reg['fee']) : 'GRATIS' ?></span>
                        </div>
                        <div style="font-size: 12px; color: var(--color-text-muted);">
                            🕒 Waktu Mendaftar: <?= isset($reg['created_at']) ? formatDate($reg['created_at'], 'd M Y H:i') : '-' ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div style="display: flex; flex-direction: column; gap: 10px; min-width: 180px;">
                        <a href="<?= url('modules/events/ticket.php?id=' . (int)$reg['registration_id']) ?>" 
                           class="btn btn-primary btn-sm" style="text-align: center; font-weight: 700; padding: 10px 16px; box-shadow: var(--shadow-sm);">
                            🎟️ Lihat Tiket / Status
                        </a>
                        <?php if (isset($reg['event_id']) && $reg['event_id']): ?>
                        <a href="<?= url('modules/events/detail.php?id=' . (int)$reg['event_id']) ?>" 
                           class="btn btn-secondary btn-sm" style="text-align: center;">
                            ℹ️ Detail Event
                        </a>
                        <?php endif; ?>
                        <?php if ($pStat === 'pending' || $pStat === 'unpaid' || $pStat === 'pending_verification' || $pStat === 'rejected'): ?>
                            <a href="<?= url('modules/events/payment.php?reg=' . urlencode($reg['registration_number'])) ?>" 
                               class="btn btn-outline btn-sm" style="text-align: center; border-color: var(--color-primary); color: var(--color-primary);">
                                📤 Upload Bukti Bayar
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($reg['payment_proof'])): ?>
                            <a href="<?= url('assets/images/uploads/payments/' . htmlspecialchars($reg['payment_proof'])) ?>" 
                               target="_blank"
                               class="btn btn-outline btn-sm" style="text-align: center;">
                                📄 Lihat Bukti
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="background: white; border-radius: 16px; padding: 60px; text-align: center; box-shadow: var(--shadow-sm); border: 1px solid var(--color-border-light);">
            <div style="font-size: 64px; margin-bottom: 16px;">📋</div>
            <h3 style="font-size: 22px; font-weight: 700; color: var(--color-text-primary); margin-bottom: 8px;">
                Belum Ada Pendaftaran
            </h3>
            <p style="color: var(--color-text-secondary); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto;">
                Anda belum terdaftar di event manapun. Yuk jelajahi dan daftar event yang menarik!
            </p>
            <a href="<?= url('modules/events/index.php') ?>" class="btn btn-primary" style="padding: 12px 28px; font-weight: 700;">
                Cari Event Sekarang
            </a>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination" style="margin-top: 32px; display: flex; justify-content: center; gap: 8px;">
            <?php if ($page > 1): ?>
                <?php $prevPage = $page - 1; ?>
                <?php $statusParam = $paymentStatusFilter ? "&payment_status=$paymentStatusFilter" : ''; ?>
                <a href="<?= url("modules/profile/my-registrations.php?page=$prevPage$statusParam") ?>" 
                   class="pagination-link" style="padding: 8px 16px; background: white; border: 1px solid var(--color-border-light); border-radius: 8px; font-weight: 700;">
                    &laquo; Sebelumnya
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php $statusParam = $paymentStatusFilter ? "&payment_status=$paymentStatusFilter" : ''; ?>
                <a href="<?= url("modules/profile/my-registrations.php?page=$i$statusParam") ?>" 
                   class="pagination-link <?= $page === $i ? 'active' : '' ?>" style="padding: 8px 16px; background: <?= $page === $i ? 'var(--color-primary)' : 'white' ?>; color: <?= $page === $i ? 'white' : 'var(--color-text-primary)' ?>; border: 1px solid <?= $page === $i ? 'var(--color-primary)' : 'var(--color-border-light)' ?>; border-radius: 8px; font-weight: 700;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <?php $nextPage = $page + 1; ?>
                <?php $statusParam = $paymentStatusFilter ? "&payment_status=$paymentStatusFilter" : ''; ?>
                <a href="<?= url("modules/profile/my-registrations.php?page=$nextPage$statusParam") ?>" 
                   class="pagination-link" style="padding: 8px 16px; background: white; border: 1px solid var(--color-border-light); border-radius: 8px; font-weight: 700;">
                    Selanjutnya &raquo;
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>