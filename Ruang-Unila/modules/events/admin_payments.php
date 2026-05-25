<?php
/**
 * Modul Verifikasi Pembayaran - Ruang Unila
 * 
 * Dashboard admin/organisasi untuk verifikasi bukti pembayaran event.
 * Admin bisa verifikasi semua, Organisasi hanya event miliknya.
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
require_once APP_ROOT . '/classes/Event.php';

// Inisialisasi
$session = Session::getInstance();
$db = Database::getInstance();
$userRole = $session->getUserRole();
$userId = $session->getUserId();
$errors = [];

// Require login (Admin atau Organisasi)
requireLogin();

if ($userRole !== ROLE_ADMIN && $userRole !== ROLE_ORGANISATION) {
    setFlashMessage('error', 'Akses ditolak. Anda tidak memiliki izin untuk verifikasi pembayaran.');
    redirect(url('index.php'));
}

// Parameter filter
$status = isset($_GET['status']) ? sanitize($_GET['status']) : 'pending_verification';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;

// Proses aksi (approve/reject payment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validasi CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid.';
    } else {
        $registrationId = (int) ($_POST['registration_id'] ?? 0);
        $action = sanitize($_POST['action']);
        $reason = sanitize($_POST['reason'] ?? '');
        
        if ($registrationId <= 0) {
            $errors[] = 'ID registrasi tidak valid.';
        }
        
        if (empty($reason) && $action === 'reject') {
            $errors[] = 'Alasan penolakan wajib diisi.';
        }
        
        if (count($errors) === 0) {
            if ($action === 'approve') {
                $newPaymentStatus = 'paid';
                $newStatus = 'confirmed';
                $message = 'Pembayaran berhasil diverifikasi!';
            } else {
                $newPaymentStatus = 'rejected';
                $newStatus = 'pending';
                $message = 'Pembayaran ditolak.';
            }
            
            $sql = "UPDATE event_registrations 
                    SET payment_status = :payment_status, 
                        status = :status,
                        payment_verified_at = NOW(),
                        payment_rejection_reason = :reason
                    WHERE registration_id = :registration_id";
            
            $stmt = $db->executeQuery($sql, [
                ':payment_status' => $newPaymentStatus,
                ':status' => $newStatus,
                ':reason' => $action === 'reject' ? $reason : '',
                ':registration_id' => $registrationId
            ]);
            
            if ($stmt) {
                setFlashMessage('success', $message);
                redirect('modules/events/admin_payments.php?status=' . $status);
            } else {
                $errors[] = 'Gagal memproses verifikasi. Silakan coba lagi.';
            }
        }
    }
}

// Mapping status untuk query
$statusMap = [
    'pending_verification' => 'pending_verification',
    'paid' => 'paid',
    'rejected' => 'rejected'
];

$queryStatus = $statusMap[$status] ?? 'pending_verification';

// Hitung total
$countSql = "SELECT COUNT(*) as total 
             FROM event_registrations er 
             JOIN events e ON er.event_id = e.event_id
             WHERE er.payment_status = :status 
             AND er.payment_proof IS NOT NULL";

$params = [':status' => $queryStatus];

if ($userRole === ROLE_ORGANISATION) {
    $countSql .= " AND e.organizer_id = :organizer_id";
    $params[':organizer_id'] = $userId;
}

$countResult = $db->fetchOne($countSql, $params);
$total = $countResult ? (int) $countResult['total'] : 0;
$totalPages = getTotalPages($total, $perPage);
$offset = getPaginationOffset($page, $perPage);

// Ambil data registrasi dengan informasi event dan user
$sql = "SELECT er.registration_id, er.registration_number, er.payment_proof, 
               er.payment_status, er.status as registration_status, er.created_at,
               er.payment_verified_at, er.payment_rejection_reason,
               e.event_id, e.title as event_title, e.fee, e.event_date,
               u.user_id, u.full_name, u.email, u.phone
        FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        JOIN users u ON er.user_id = u.user_id
        WHERE er.payment_status = :status
        AND er.payment_proof IS NOT NULL";

if ($userRole === ROLE_ORGANISATION) {
    $sql .= " AND e.organizer_id = :organizer_id";
}

$sql .= " ORDER BY er.created_at DESC LIMIT :limit OFFSET :offset";

$params[':limit'] = $perPage;
$params[':offset'] = $offset;

$stmt = $db->executeQuery($sql, $params);

$registrations = $stmt ? $stmt->fetchAll() : [];

// Hitung statistik
$stats = [
    'pending_verification' => 0,
    'paid' => 0,
    'rejected' => 0,
    'unpaid' => 0
];

$statsSql = "SELECT er.payment_status, COUNT(*) as count 
             FROM event_registrations er
             JOIN events e ON er.event_id = e.event_id
             WHERE er.payment_proof IS NOT NULL";

$statsParams = [];
if ($userRole === ROLE_ORGANISATION) {
    $statsSql .= " AND e.organizer_id = :organizer_id";
    $statsParams[':organizer_id'] = $userId;
}

$statsSql .= " GROUP BY er.payment_status";
$statsResult = $db->fetchAll($statsSql, $statsParams);
foreach ($statsResult as $row) {
    $stats[$row['payment_status']] = (int) $row['count'];
}

// Set page title
$pageTitle = 'Verifikasi Pembayaran - ' . ucfirst($userRole);

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0;">
    <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">
        Verifikasi Pembayaran
    </h1>
    <p style="color: var(--color-text-muted); margin-bottom: 32px;">
        Review dan verifikasi bukti pembayaran event
    </p>
    
    <!-- Statistics Cards -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px;">
        <a href="<?= url('modules/events/admin_payments.php?status=pending_verification') ?>" 
           style="background: <?= $status === 'pending_verification' ? 'var(--color-warning)' : 'white' ?>; 
                  color: <?= $status === 'pending_verification' ? 'white' : 'var(--color-text-primary)' ?>;
                  padding: 20px; border-radius: 12px; text-decoration: none; 
                  box-shadow: var(--shadow-sm); transition: transform 0.2s;">
            <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Menunggu Verifikasi</div>
            <div style="font-size: 32px; font-weight: 700;"><?= $stats['pending_verification'] ?></div>
        </a>
        
        <a href="<?= url('modules/events/admin_payments.php?status=paid') ?>" 
           style="background: <?= $status === 'paid' ? 'var(--color-success)' : 'white' ?>; 
                  color: <?= $status === 'paid' ? 'white' : 'var(--color-text-primary)' ?>;
                  padding: 20px; border-radius: 12px; text-decoration: none; 
                  box-shadow: var(--shadow-sm); transition: transform 0.2s;">
            <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Terverifikasi</div>
            <div style="font-size: 32px; font-weight: 700;"><?= $stats['paid'] ?></div>
        </a>
        
        <a href="<?= url('modules/events/admin_payments.php?status=rejected') ?>" 
           style="background: <?= $status === 'rejected' ? 'var(--color-danger)' : 'white' ?>; 
                  color: <?= $status === 'rejected' ? 'white' : 'var(--color-text-primary)' ?>;
                  padding: 20px; border-radius: 12px; text-decoration: none; 
                  box-shadow: var(--shadow-sm); transition: transform 0.2s;">
            <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Ditolak</div>
            <div style="font-size: 32px; font-weight: 700;"><?= $stats['rejected'] ?></div>
        </a>
        
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: var(--shadow-sm);">
            <div style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 4px;">Belum Upload</div>
            <div style="font-size: 32px; font-weight: 700; color: var(--color-text-primary);"><?= $stats['unpaid'] ?></div>
        </div>
    </div>
    
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
    
    <!-- Registrations List -->
    <?php if (count($registrations) > 0): ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($registrations as $reg): ?>
                <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
                    <div style="display: grid; grid-template-columns: 200px 1fr 200px; gap: 24px; align-items: start;">
                        <!-- Payment Proof Image -->
                        <div>
                            <div style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 8px;">Bukti Pembayaran</div>
                            <div style="height: 150px; border-radius: 8px; overflow: hidden; border: 1px solid var(--color-border);">
                                <a href="<?= url('assets/images/uploads/payments/' . $reg['payment_proof']) ?>" target="_blank">
                                    <img src="<?= url('assets/images/uploads/payments/' . $reg['payment_proof']) ?>" 
                                         alt="Bukti Pembayaran"
                                         style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;">
                                </a>
                            </div>
                            <div style="margin-top: 8px;">
                                <a href="<?= url('assets/images/uploads/payments/' . $reg['payment_proof']) ?>" 
                                   target="_blank" style="font-size: 12px; color: var(--color-primary);">
                                    Lihat Ukuran Penuh →
                                </a>
                            </div>
                        </div>
                        
                        <!-- Registration Info -->
                        <div style="min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                <span style="background: var(--color-primary-light); color: var(--color-primary); padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                    <?= htmlspecialchars($reg['registration_number']) ?>
                                </span>
                                <span style="background: <?= $reg['payment_status'] === 'paid' ? 'var(--color-success)' : ($reg['payment_status'] === 'rejected' ? 'var(--color-danger)' : 'var(--color-warning)') ?>; 
                                             color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                    <?= $reg['payment_status'] === 'paid' ? 'Terverifikasi' : ($reg['payment_status'] === 'rejected' ? 'Ditolak' : 'Menunggu') ?>
                                </span>
                            </div>
                            
                            <h3 style="font-size: 18px; margin-bottom: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <a href="<?= url('modules/events/detail.php?id=' . $reg['event_id']) ?>" 
                                   style="color: var(--color-text-primary); text-decoration: none;">
                                    <?= htmlspecialchars($reg['event_title']) ?>
                                </a>
                            </h3>
                            
                            <div style="display: flex; flex-direction: column; gap: 8px; font-size: 14px; color: var(--color-text-muted); margin-bottom: 12px;">
                                <div>
                                    <strong>Peserta:</strong> <?= htmlspecialchars($reg['full_name']) ?>
                                </div>
                                <div>
                                    <strong>Email:</strong> <?= htmlspecialchars($reg['email']) ?>
                                </div>
                                <?php if ($reg['phone']): ?>
                                    <div>
                                        <strong>Telepon:</strong> <?= htmlspecialchars($reg['phone']) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <strong>Event:</strong> <?= formatDate($reg['event_date'], 'd M Y, H:i') ?> WIB
                                </div>
                            </div>
                            
                            <div style="background: var(--color-bg); padding: 12px; border-radius: 8px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <span style="font-size: 12px; color: var(--color-text-muted);">Total Pembayaran</span>
                                    <span style="font-weight: 700; color: var(--color-primary);">
                                        Rp <?= formatNumber($reg['fee']) ?>
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="font-size: 12px; color: var(--color-text-muted);">Tanggal Upload</span>
                                    <span style="font-size: 12px;">
                                        <?= formatDate($reg['created_at'], 'd M Y, H:i') ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($reg['payment_status'] === 'rejected' && $reg['payment_rejection_reason']): ?>
                                <div style="margin-top: 12px; padding: 12px; background: var(--color-danger-light); border-radius: 8px;">
                                    <div style="font-size: 12px; color: var(--color-danger); font-weight: 600; margin-bottom: 4px;">Alasan Penolakan:</div>
                                    <div style="font-size: 13px; color: var(--color-text-secondary);"><?= htmlspecialchars($reg['payment_rejection_reason']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Actions -->
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <?php if ($reg['payment_status'] === 'pending_verification'): ?>
                                <!-- Approve Button -->
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="registration_id" value="<?= $reg['registration_id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="reason" value="Pembayaran diverifikasi">
                                    <button type="submit" class="btn btn-success" style="width: 100%;"
                                            onclick="return confirm('Verifikasi pembayaran ini?')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Verifikasi
                                    </button>
                                </form>
                                
                                <!-- Reject Button -->
                                <button type="button" class="btn btn-danger" style="width: 100%;"
                                        onclick="showRejectModal(<?= $reg['registration_id'] ?>, '<?= htmlspecialchars(addslashes($reg['registration_number'])) ?>')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                    Tolak
                                </button>
                            <?php endif; ?>
                            
                            <a href="<?= url('modules/events/detail.php?id=' . $reg['event_id']) ?>" 
                               class="btn btn-outline" style="width: 100%;">
                                Lihat Event
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div style="display: flex; justify-content: center; margin-top: 32px;">
                <div style="display: flex; gap: 8px;">
                    <?php if ($page > 1): ?>
                        <a href="<?= url('modules/events/admin_payments.php?status=' . $status . '&page=' . ($page - 1)) ?>" 
                           class="btn btn-outline btn-sm">Sebelumnya</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="<?= url('modules/events/admin_payments.php?status=' . $status . '&page=' . $i) ?>" 
                           class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= url('modules/events/admin_payments.php?status=' . $status . '&page=' . ($page + 1)) ?>" 
                           class="btn btn-outline btn-sm">Selanjutnya</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- Empty State -->
        <div style="text-align: center; padding: 64px; background: white; border-radius: 12px;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="1" style="margin-bottom: 16px;">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            <h3 style="margin-bottom: 8px;">Tidak Ada Pembayaran</h3>
            <p style="color: var(--color-text-muted);">
                <?php if ($status === 'pending_verification'): ?>
                    Tidak ada pembayaran yang menunggu verifikasi.
                <?php elseif ($status === 'paid'): ?>
                    Belum ada pembayaran yang terverifikasi.
                <?php else: ?>
                    Belum ada pembayaran yang ditolak.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- Reject Modal -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 32px; border-radius: 12px; width: 500px; max-width: 90%;">
        <h3 style="margin-bottom: 16px;">Tolak Pembayaran</h3>
        <p style="color: var(--color-text-muted); margin-bottom: 16px;">
            Registrasi: <strong id="rejectRegNumber"></strong>
        </p>
        
        <form method="POST" id="rejectForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="registration_id" id="rejectRegId">
            <input type="hidden" name="action" value="reject">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Alasan Penolakan *</label>
                <textarea name="reason" class="form-input" rows="4" required
                          placeholder="Jelaskan alasan mengapa pembayaran ini ditolak..."></textarea>
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak Pembayaran</button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal(regId, regNumber) {
    document.getElementById('rejectRegId').value = regId;
    document.getElementById('rejectRegNumber').textContent = regNumber;
    document.getElementById('rejectModal').style.display = 'block';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectModal();
    }
});
</script>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>