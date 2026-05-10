<?php
/**
 * Pendaftaran Saya - Ruang Unila
 * 
 * Halaman mahasiswa untuk melihat daftar event yang diikuti.
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

// Validasi payment status
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

if ($paymentStatusFilter) {
    $sql .= " AND er.payment_status = :payment_status";
    $params[':payment_status'] = $paymentStatusFilter;
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

// Hitung statistik
$sqlStats = "SELECT payment_status, COUNT(*) as count FROM event_registrations WHERE user_id = :user_id GROUP BY payment_status";
$statsResult = $db->fetchAll($sqlStats, [':user_id' => $userId]);
$stats = [];
foreach ($statsResult as $row) {
    $stats[$row['payment_status']] = (int) $row['count'];
}

// Include header
include APP_ROOT . '/includes/header.php';

// Flush output buffer
ob_end_flush();
?>

<div class="container" style="padding: 32px 0; max-width: 1000px; margin: 0 auto;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">
                Pendaftaran Saya
            </h1>
            <p style="color: var(--color-text-secondary); font-size: 14px;">
                Daftar event yang Anda ikuti
            </p>
        </div>
        <a href="<?= url('modules/events/index.php') ?>" class="btn btn-primary">
            Cari Event
        </a>
    </div>

    <!-- Statistics -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px;">
        <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px;">
                Total
            </div>
            <div style="font-size: 24px; font-weight: 700; color: var(--color-text-primary);">
                <?= array_sum($stats) ?>
            </div>
        </div>
        <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px;">
                Verified
            </div>
            <div style="font-size: 24px; font-weight: 700; color: var(--color-success);">
                <?= $stats['verified'] ?? 0 ?>
            </div>
        </div>
        <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px;">
                Pending
            </div>
            <div style="font-size: 24px; font-weight: 700; color: var(--color-warning);">
                <?= ($stats['pending'] ?? 0) + ($stats['unpaid'] ?? 0) ?>
            </div>
        </div>
        <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px;">
                Rejected
            </div>
            <div style="font-size: 24px; font-weight: 700; color: var(--color-error);">
                <?= $stats['rejected'] ?? 0 ?>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div style="display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap;">
        <a href="<?= url('modules/profile/my-registrations.php') ?>" 
           class="btn <?= !$paymentStatusFilter ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            Semua
        </a>
        <a href="<?= url('modules/profile/my-registrations.php?payment_status=pending') ?>" 
           class="btn <?= $paymentStatusFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            Pending
        </a>
        <a href="<?= url('modules/profile/my-registrations.php?payment_status=verified') ?>" 
           class="btn <?= $paymentStatusFilter === 'verified' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            Verified
        </a>
        <a href="<?= url('modules/profile/my-registrations.php?payment_status=rejected') ?>" 
           class="btn <?= $paymentStatusFilter === 'rejected' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            Rejected
        </a>
    </div>

    <!-- Registrations List -->
    <?php if (!empty($registrations) && is_array($registrations) && count($registrations) > 0): ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($registrations as $reg): ?>
                <?php if (!empty($reg) && isset($reg['registration_number'])): ?>
                <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); display: flex; gap: 24px; align-items: center;">
                    <!-- Event Info -->
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                            <span style="font-size: 12px; color: var(--color-text-muted);">
                                <?= htmlspecialchars($reg['registration_number']) ?>
                            </span>
                            <?php
                            $statusColors = [
                                'verified' => ['bg' => 'rgba(16, 185, 129, 0.1)', 'color' => 'var(--color-success)'],
                                'pending' => ['bg' => 'rgba(245, 158, 11, 0.1)', 'color' => 'var(--color-warning)'],
                                'unpaid' => ['bg' => 'rgba(245, 158, 11, 0.1)', 'color' => 'var(--color-warning)'],
                                'rejected' => ['bg' => 'rgba(239, 68, 68, 0.1)', 'color' => 'var(--color-error)']
                            ];
                            $statusStyle = $statusColors[$reg['payment_status']] ?? $statusColors['pending'];
                            ?>
                            <span style="display: inline-flex; padding: 4px 8px; background: <?= $statusStyle['bg'] ?>; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: <?= $statusStyle['color'] ?>;">
                                <?= htmlspecialchars($reg['payment_status']) ?>
                            </span>
                        </div>
                        <h3 style="font-size: 18px; font-weight: 700; color: var(--color-text-primary); margin-bottom: 8px;">
                            <?= isset($reg['event_title']) ? htmlspecialchars($reg['event_title']) : 'Event Tidak Diketahui' ?>
                        </h3>
                        <div style="display: flex; gap: 16px; font-size: 13px; color: var(--color-text-secondary); margin-bottom: 8px;">
                            <span>Tanggal: <?= isset($reg['event_date']) ? formatDate($reg['event_date']) : '-' ?></span>
                            <?php if (isset($reg['location']) && $reg['location']): ?>
                                <span>Lokasi: <?= htmlspecialchars($reg['location']) ?></span>
                            <?php endif; ?>
                            <span>Biaya: <?= isset($reg['fee']) && $reg['fee'] > 0 ? 'Rp ' . formatNumber($reg['fee']) : 'Gratis' ?></span>
                        </div>
                        <div style="font-size: 12px; color: var(--color-text-muted);">
                            Daftar: <?= isset($reg['created_at']) ? formatDate($reg['created_at'], 'd M Y H:i') : '-' ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div style="display: flex; flex-direction: column; gap: 8px; min-width: 150px;">
                        <?php if (isset($reg['event_id']) && $reg['event_id']): ?>
                        <a href="<?= url('modules/events/detail.php?id=' . (int)$reg['event_id']) ?>" 
                           class="btn btn-secondary btn-sm" style="text-align: center;">
                            Lihat Detail
                        </a>
                        <?php endif; ?>
                        <?php if ($reg['payment_status'] === 'pending' || $reg['payment_status'] === 'unpaid'): ?>
                            <a href="<?= url('modules/events/payment.php?id=' . (int)$reg['registration_id']) ?>" 
                               class="btn btn-primary btn-sm" style="text-align: center;">
                                Upload Bukti
                            </a>
                        <?php endif; ?>
                        <?php if (isset($reg['payment_proof']) && $reg['payment_proof']): ?>
                            <a href="<?= url('assets/images/uploads/payments/' . htmlspecialchars($reg['payment_proof'])) ?>" 
                               target="_blank"
                               class="btn btn-outline btn-sm" style="text-align: center;">
                                Lihat Bukti
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="background: white; border-radius: 12px; padding: 60px; text-align: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="font-size: 64px; margin-bottom: 16px;">📋</div>
            <h3 style="font-size: 20px; font-weight: 700; color: var(--color-text-primary); margin-bottom: 8px;">
                Belum Ada Pendaftaran
            </h3>
            <p style="color: var(--color-text-secondary); margin-bottom: 24px;">
                Anda belum terdaftar di event manapun. Yuk cari event yang menarik!
            </p>
            <a href="<?= url('modules/events/index.php') ?>" class="btn btn-primary">
                Cari Event
            </a>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination" style="margin-top: 24px;">
            <?php if ($page > 1): ?>
                <?php $prevPage = $page - 1; ?>
                <?php $statusParam = $paymentStatusFilter ? "&payment_status=$paymentStatusFilter" : ''; ?>
                <a href="<?= url("modules/profile/my-registrations.php?page=$prevPage$statusParam") ?>" 
                   class="pagination-link">
                    &laquo;
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php $statusParam = $paymentStatusFilter ? "&payment_status=$paymentStatusFilter" : ''; ?>
                <a href="<?= url("modules/profile/my-registrations.php?page=$i$statusParam") ?>" 
                   class="pagination-link <?= $page === $i ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <?php $nextPage = $page + 1; ?>
                <?php $statusParam = $paymentStatusFilter ? "&payment_status=$paymentStatusFilter" : ''; ?>
                <a href="<?= url("modules/profile/my-registrations.php?page=$nextPage$statusParam") ?>" 
                   class="pagination-link">
                    &raquo;
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>