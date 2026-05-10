<?php
/**
 * Daftar Pendaftar Event - Ruang Unila
 * 
 * Dashboard admin untuk melihat semua pendaftar event.
 * Admin bisa export CSV, filter status, dan konfirmasi peserta.
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

// Cek role: admin atau organisasi boleh akses
if (!hasRole(ROLE_ADMIN) && !hasRole(ROLE_ORGANIZATION)) {
    setFlashMessage('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect(url('index.php'));
}

// Inisialisasi
$session = Session::getInstance();
$event = new Event();
$db = Database::getInstance();
$pageTitle = 'Daftar Pendaftar Event';

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;
    
    $sql = "SELECT er.registration_number, u.full_name, u.email, u.phone, 
                   e.title as event_title, er.payment_status, er.created_at,
                   er.payment_proof, er.verified_at
            FROM event_registrations er
            LEFT JOIN users u ON er.user_id = u.user_id
            LEFT JOIN events e ON er.event_id = e.event_id";
    
    $params = [];
    if ($eventId) {
        $sql .= " WHERE er.event_id = :event_id";
        $params[':event_id'] = $eventId;
    }
    
    $sql .= " ORDER BY er.created_at DESC";
    
    $registrations = $db->fetchAll($sql, $params);
    
    // Set header untuk download CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=pendaftar_event_' . date('Y-m-d_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Header CSV
    fputcsv($output, ['No. Pendaftaran', 'Nama', 'Email', 'Telepon', 'Event', 'Status Pembayaran', 'Tanggal Daftar', 'Bukti Pembayaran', 'Tanggal Verifikasi']);
    
    // Data
    foreach ($registrations as $reg) {
        fputcsv($output, [
            $reg['registration_number'],
            $reg['full_name'],
            $reg['email'],
            $reg['phone'] ?? '-',
            $reg['event_title'],
            $reg['payment_status'],
            $reg['created_at'],
            $reg['payment_proof'] ? 'Ada' : 'Tidak ada',
            $reg['verified_at'] ?? '-'
        ]);
    }
    
    fclose($output);
    exit;
}

// Proses aksi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $registrationId = (int)($_POST['registration_id'] ?? 0);
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    // Validasi CSRF
    if (!$session->verifyCsrfToken($csrfToken)) {
        $session->setFlash('error', 'Token keamanan tidak valid.');
        redirect(url('modules/events/registrations.php'));
    }
    
    // Validasi ID
    if ($registrationId <= 0) {
        $session->setFlash('error', 'ID registrasi tidak valid.');
        redirect(url('modules/events/registrations.php'));
    }
    
    // Proses aksi
    switch ($action) {
        case 'confirm':
            $updateSql = "UPDATE event_registrations SET payment_status = 'verified', verified_at = NOW() WHERE registration_id = :id";
            $stmt = $db->executeQuery($updateSql, [':id' => $registrationId]);
            
            if ($stmt) {
                $session->setFlash('success', 'Pembayaran berhasil dikonfirmasi!');
            } else {
                $session->setFlash('error', 'Gagal mengkonfirmasi pembayaran.');
            }
            break;
            
        case 'reject':
            $updateSql = "UPDATE event_registrations SET payment_status = 'rejected', verified_at = NOW() WHERE registration_id = :id";
            $stmt = $db->executeQuery($updateSql, [':id' => $registrationId]);
            
            if ($stmt) {
                $session->setFlash('success', 'Pembayaran berhasil ditolak.');
            } else {
                $session->setFlash('error', 'Gagal menolak pembayaran.');
            }
            break;
            
        case 'pending':
            $updateSql = "UPDATE event_registrations SET payment_status = 'pending', verified_at = NULL WHERE registration_id = :id";
            $stmt = $db->executeQuery($updateSql, [':id' => $registrationId]);
            
            if ($stmt) {
                $session->setFlash('success', 'Status pembayaran diubah ke pending.');
            } else {
                $session->setFlash('error', 'Gagal mengubah status.');
            }
            break;
            
        default:
            $session->setFlash('error', 'Aksi tidak valid.');
            break;
    }
    
    redirect(url('modules/events/registrations.php'));
}

// Ambil parameter filter
$eventIdFilter = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;
$paymentStatusFilter = isset($_GET['payment_status']) ? cleanInput($_GET['payment_status']) : null;
$page = getCurrentPage();
$perPage = ADMIN_ITEMS_PER_PAGE;

// Validasi payment status
$validPaymentStatuses = ['pending', 'unpaid', 'verified', 'rejected'];
if ($paymentStatusFilter && !in_array($paymentStatusFilter, $validPaymentStatuses)) {
    $paymentStatusFilter = null;
}

// Ambil data registrations
$sql = "SELECT er.registration_id, er.registration_number, er.payment_status, er.created_at, er.payment_proof,
               u.full_name as user_name, u.email as user_email, u.phone as user_phone,
               e.title as event_title, e.event_date, e.fee
        FROM event_registrations er
        LEFT JOIN users u ON er.user_id = u.user_id
        LEFT JOIN events e ON er.event_id = e.event_id
        WHERE 1=1";

$params = [];

if ($eventIdFilter) {
    $sql .= " AND er.event_id = :event_id";
    $params[':event_id'] = $eventIdFilter;
}

if ($paymentStatusFilter) {
    $sql .= " AND er.payment_status = :payment_status";
    $params[':payment_status'] = $paymentStatusFilter;
}

// Hitung total
$countSql = str_replace("SELECT er.registration_id, er.registration_number, er.payment_status, er.created_at, er.payment_proof, u.full_name as user_name, u.email as user_email, u.phone as user_phone, e.title as event_title, e.event_date, e.fee", "SELECT COUNT(*) as total", $sql);
$resultCount = $db->fetchOne($countSql, $params);
$total = $resultCount ? (int) $resultCount['total'] : 0;
$totalPages = getTotalPages($total, $perPage);

// Ambil data dengan pagination
$sql .= " ORDER BY er.created_at DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $perPage;
$params[':offset'] = getPaginationOffset($page, $perPage);

$registrations = $db->fetchAll($sql, $params);

// Ambil daftar event untuk filter
$sqlEvents = "SELECT event_id, title FROM events ORDER BY created_at DESC";
$events = $db->fetchAll($sqlEvents);

// Hitung statistik
$sqlStats = "SELECT payment_status, COUNT(*) as count FROM event_registrations GROUP BY payment_status";
$statsResult = $db->fetchAll($sqlStats);
$stats = [];
foreach ($statsResult as $row) {
    $stats[$row['payment_status']] = (int) $row['count'];
}

// Generate CSRF token
$csrfToken = $session->generateCsrfToken();

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">
                Daftar Pendaftar Event
            </h1>
            <p style="color: var(--color-text-secondary); font-size: 14px;">
                Kelola semua pendaftaran event dari mahasiswa
            </p>
        </div>
        <a href="<?= url('modules/events/registrations.php?export=csv' . ($eventIdFilter ? '&event_id=' . $eventIdFilter : '')) ?>" 
           class="btn btn-primary">
            📥 Export CSV
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
    <div style="display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
        <!-- Event Filter -->
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Filter Event</label>
            <select id="eventFilter" class="form-input" onchange="applyFilter()">
                <option value="">Semua Event</option>
                <?php foreach ($events as $evt): ?>
                    <option value="<?= $evt['event_id'] ?>" <?= $eventIdFilter === $evt['event_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($evt['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Payment Status Filter -->
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Filter Status</label>
            <div style="display: flex; gap: 8px;">
                <a href="<?= url('modules/events/registrations.php' . ($eventIdFilter ? '?event_id=' . $eventIdFilter : '')) ?>" 
                   class="btn <?= !$paymentStatusFilter ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                    Semua
                </a>
                <a href="<?= url('modules/events/registrations.php?payment_status=pending' . ($eventIdFilter ? '&event_id=' . $eventIdFilter : '')) ?>" 
                   class="btn <?= $paymentStatusFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                    Pending
                </a>
                <a href="<?= url('modules/events/registrations.php?payment_status=verified' . ($eventIdFilter ? '&event_id=' . $eventIdFilter : '')) ?>" 
                   class="btn <?= $paymentStatusFilter === 'verified' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                    Verified
                </a>
                <a href="<?= url('modules/events/registrations.php?payment_status=rejected' . ($eventIdFilter ? '&event_id=' . $eventIdFilter : '')) ?>" 
                   class="btn <?= $paymentStatusFilter === 'rejected' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                    Rejected
                </a>
            </div>
        </div>
    </div>

    <!-- Registrations Table -->
    <div style="background: white; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
        <!-- Table Header -->
        <div style="display: grid; grid-template-columns: 150px 1fr 150px 100px 100px 150px; gap: 16px; padding: 16px; background: var(--color-border-light); font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.6px; color: var(--color-text-muted);">
            <div>No. Pendaftaran</div>
            <div>Peserta & Event</div>
            <div>Kontak</div>
            <div>Status</div>
            <div>Tanggal</div>
            <div>Aksi</div>
        </div>
        
        <!-- Table Body -->
        <?php if (count($registrations) > 0): ?>
            <?php foreach ($registrations as $reg): ?>
                <div style="display: grid; grid-template-columns: 150px 1fr 150px 100px 100px 150px; gap: 16px; padding: 16px; border-bottom: 1px solid var(--color-border-light); align-items: center;">
                    <!-- Registration Number -->
                    <div>
                        <div style="font-weight: 700; font-size: 14px; color: var(--color-text-primary); margin-bottom: 4px;">
                            <?= htmlspecialchars($reg['registration_number']) ?>
                        </div>
                        <?php if ($reg['payment_proof']): ?>
                            <a href="<?= url('assets/images/uploads/payments/' . $reg['payment_proof']) ?>" 
                               target="_blank"
                               style="font-size: 11px; color: var(--color-primary);">
                                📷 Lihat Bukti
                            </a>
                        <?php else: ?>
                            <span style="font-size: 11px; color: var(--color-text-muted);">
                                Tidak ada bukti
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Participant & Event Info -->
                    <div>
                        <div style="font-weight: 700; font-size: 14px; color: var(--color-text-primary); margin-bottom: 4px;">
                            <?= htmlspecialchars($reg['user_name']) ?>
                        </div>
                        <div style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 4px;">
                            <?= htmlspecialchars($reg['event_title']) ?>
                        </div>
                        <div style="font-size: 12px; color: var(--color-primary); font-weight: 600;">
                            <?= $reg['fee'] > 0 ? 'Rp ' . formatNumber($reg['fee']) : 'Gratis' ?>
                        </div>
                    </div>
                    
                    <!-- Contact -->
                    <div>
                        <div style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 4px;">
                            <?= htmlspecialchars($reg['user_email']) ?>
                        </div>
                        <div style="font-size: 12px; color: var(--color-text-muted);">
                            <?= htmlspecialchars($reg['user_phone'] ?? '-') ?>
                        </div>
                    </div>
                    
                    <!-- Status -->
                    <div>
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
                            <?= $reg['payment_status'] ?>
                        </span>
                    </div>
                    
                    <!-- Date -->
                    <div style="font-size: 12px; color: var(--color-text-muted);">
                        <?= formatDate($reg['created_at'], 'd M Y') ?>
                        <br>
                        <?= date('H:i', strtotime($reg['created_at'])) ?>
                    </div>
                    
                    <!-- Actions -->
                    <div style="display: flex; gap: 8px;">
                        <?php if ($reg['payment_status'] === 'pending' || $reg['payment_status'] === 'unpaid'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="confirm">
                                <input type="hidden" name="registration_id" value="<?= $reg['registration_id'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm" style="padding: 4px 8px;">
                                    ✓ Konfirmasi
                                </button>
                            </form>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="registration_id" value="<?= $reg['registration_id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="padding: 4px 8px; color: var(--color-error); border-color: var(--color-error);">
                                    ✗ Tolak
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($reg['payment_status'] === 'verified'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="pending">
                                <input type="hidden" name="registration_id" value="<?= $reg['registration_id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="padding: 4px 8px;">
                                    ↩ Reset
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($reg['payment_status'] === 'rejected'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="confirm">
                                <input type="hidden" name="registration_id" value="<?= $reg['registration_id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="padding: 4px 8px;">
                                    ✓ Konfirmasi
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="padding: 40px; text-align: center; color: var(--color-text-muted);">
                Tidak ada pendaftaran yang ditemukan.
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination" style="margin-top: 24px;">
            <?php if ($page > 1): ?>
                <?php $prevPage = $page - 1; ?>
                <?php $eventParam = $eventIdFilter ? "&event_id=$eventIdFilter" : ''; ?>
                <?php $statusParam = $paymentStatusFilter ? "&payment_status=$paymentStatusFilter" : ''; ?>
                <a href="<?= url("modules/events/registrations.php?page=$prevPage$eventParam$statusParam") ?>" 
                   class="pagination-link">
                    &laquo;
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php $eventParam = $eventIdFilter ? "&event_id=$eventIdFilter" : ''; ?>
                <?php $statusParam = $paymentStatusFilter ? "&payment_status=$paymentStatusFilter" : ''; ?>
                <a href="<?= url("modules/events/registrations.php?page=$i$eventParam$statusParam") ?>" 
                   class="pagination-link <?= $page === $i ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <?php $nextPage = $page + 1; ?>
                <?php $eventParam = $eventIdFilter ? "&event_id=$eventIdFilter" : ''; ?>
                <?php $statusParam = $paymentStatusFilter ? "&payment_status=$paymentStatusFilter" : ''; ?>
                <a href="<?= url("modules/events/registrations.php?page=$nextPage$eventParam$statusParam") ?>" 
                   class="pagination-link">
                    &raquo;
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function applyFilter() {
    const eventId = document.getElementById('eventFilter').value;
    const currentUrl = new URL(window.location.href);
    
    if (eventId) {
        currentUrl.searchParams.set('event_id', eventId);
    } else {
        currentUrl.searchParams.delete('event_id');
    }
    
    // Reset page ke 1 saat filter berubah
    currentUrl.searchParams.delete('page');
    
    window.location.href = currentUrl.toString();
}
</script>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>