<?php
/**
 * Modul Validasi Event Admin - Ruang Unila
 * 
 * Dashboard admin untuk review dan validasi event.
 * Admin bisa setujui atau tolak event yang diajukan organisasi.
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

// Require login sebagai admin
requireLogin();

if (!hasRole(ROLE_ADMIN)) {
    setFlashMessage('error', 'Akses ditolak. Hanya admin yang bisa mengakses halaman ini.');
    redirect('index.php');
}

// Inisialisasi
$event = new Event();
$db = Database::getInstance();
$errors = [];
$success = false;

// Parameter filter
$status = isset($_GET['status']) ? sanitize($_GET['status']) : 'pending';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;

// Proses aksi (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validasi CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid.';
    } else {
        $actionId = (int) ($_POST['event_id'] ?? 0);
        $action = sanitize($_POST['action']);
        $reason = sanitize($_POST['reason'] ?? '');
        
        if ($actionId <= 0) {
            $errors[] = 'ID event tidak valid.';
        }
        
        if (empty($reason) && $action === 'reject') {
            $errors[] = 'Alasan penolakan wajib diisi.';
        }
        
        if (count($errors) === 0) {
            $newStatus = ($action === 'approve') ? 'upcoming' : 'rejected';
            
            $sql = "UPDATE events SET status = :status WHERE event_id = :event_id";
            $stmt = $db->executeQuery($sql, [
                ':status' => $newStatus,
                ':event_id' => $actionId
            ]);
            
            if ($stmt) {
                $success = true;
                $message = ($action === 'approve') 
                    ? 'Event berhasil disetujui dan dipublikasikan!' 
                    : 'Event berhasil ditolak.';
                setFlashMessage('success', $message);
                redirect('modules/events/admin.php?status=' . $status);
            } else {
                $errors[] = 'Gagal memproses aksi. Silakan coba lagi.';
            }
        }
    }
}

// Ambil data event berdasarkan status
$offset = getPaginationOffset($page, $perPage);

// Hitung total
$countSql = "SELECT COUNT(*) as total FROM events WHERE status = :status";
$countResult = $db->fetchOne($countSql, [':status' => $status]);
$total = $countResult ? (int) $countResult['total'] : 0;
$totalPages = getTotalPages($total, $perPage);

// Ambil data event dengan informasi organisasi
$sql = "SELECT e.event_id, e.title, e.description, e.location, e.event_date, 
               e.registration_deadline, e.max_participants, e.fee, e.image, 
               e.status, e.created_at,
               u.full_name as organizer_name, u.email as organizer_email
        FROM events e
        LEFT JOIN users u ON e.organizer_id = u.user_id
        WHERE e.status = :status
        ORDER BY e.created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $db->executeQuery($sql, [
    ':status' => $status,
    ':limit' => $perPage,
    ':offset' => $offset
]);

$events = $stmt ? $stmt->fetchAll() : [];

// Hitung statistik
$stats = [
    'pending' => 0,
    'upcoming' => 0,
    'rejected' => 0,
    'completed' => 0
];

$statsSql = "SELECT status, COUNT(*) as count FROM events GROUP BY status";
$statsResult = $db->fetchAll($statsSql);
foreach ($statsResult as $row) {
    $stats[$row['status']] = (int) $row['count'];
}

// Set page title
$pageTitle = 'Validasi Event - Admin';

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0;">
    <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">
        Validasi Event
    </h1>
    <p style="color: var(--color-text-muted); margin-bottom: 32px;">
        Review dan validasi event yang diajukan oleh organisasi
    </p>
    
    <!-- Statistics Cards -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px;">
        <a href="<?= url('modules/events/admin.php?status=pending') ?>" 
           style="background: <?= $status === 'pending' ? 'var(--color-warning)' : 'white' ?>; 
                  color: <?= $status === 'pending' ? 'white' : 'var(--color-text-primary)' ?>;
                  padding: 20px; border-radius: 12px; text-decoration: none; 
                  box-shadow: var(--shadow-sm); transition: transform 0.2s;">
            <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Menunggu Review</div>
            <div style="font-size: 32px; font-weight: 700;"><?= $stats['pending'] ?></div>
        </a>
        
        <a href="<?= url('modules/events/admin.php?status=upcoming') ?>" 
           style="background: <?= $status === 'upcoming' ? 'var(--color-success)' : 'white' ?>; 
                  color: <?= $status === 'upcoming' ? 'white' : 'var(--color-text-primary)' ?>;
                  padding: 20px; border-radius: 12px; text-decoration: none; 
                  box-shadow: var(--shadow-sm); transition: transform 0.2s;">
            <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Disetujui</div>
            <div style="font-size: 32px; font-weight: 700;"><?= $stats['upcoming'] ?></div>
        </a>
        
        <a href="<?= url('modules/events/admin.php?status=rejected') ?>" 
           style="background: <?= $status === 'rejected' ? 'var(--color-danger)' : 'white' ?>; 
                  color: <?= $status === 'rejected' ? 'white' : 'var(--color-text-primary)' ?>;
                  padding: 20px; border-radius: 12px; text-decoration: none; 
                  box-shadow: var(--shadow-sm); transition: transform 0.2s;">
            <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Ditolak</div>
            <div style="font-size: 32px; font-weight: 700;"><?= $stats['rejected'] ?></div>
        </a>
        
        <a href="<?= url('modules/events/admin.php?status=completed') ?>" 
           style="background: <?= $status === 'completed' ? 'var(--color-primary)' : 'white' ?>; 
                  color: <?= $status === 'completed' ? 'white' : 'var(--color-text-primary)' ?>;
                  padding: 20px; border-radius: 12px; text-decoration: none; 
                  box-shadow: var(--shadow-sm); transition: transform 0.2s;">
            <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Selesai</div>
            <div style="font-size: 32px; font-weight: 700;"><?= $stats['completed'] ?></div>
        </a>
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
    
    <!-- Events List -->
    <?php if (count($events) > 0): ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($events as $eventItem): ?>
                <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
                    <div style="display: grid; grid-template-columns: 150px 1fr 200px; gap: 24px; align-items: start;">
                        <!-- Event Image -->
                        <div style="height: 120px; border-radius: 8px; overflow: hidden;">
                            <img src="<?= $eventItem['image'] ? url('assets/images/uploads/events/' . $eventItem['image']) : url('assets/images/placeholder.jpg') ?>" 
                                 alt="<?= htmlspecialchars($eventItem['title']) ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        
                        <!-- Event Info -->
                        <div>
                            <h3 style="font-size: 18px; margin-bottom: 8px;">
                                <a href="<?= url('modules/events/detail.php?id=' . $eventItem['event_id']) ?>" 
                                   style="color: var(--color-text-primary); text-decoration: none;">
                                    <?= htmlspecialchars($eventItem['title']) ?>
                                </a>
                            </h3>
                            
                            <div style="display: flex; flex-wrap: wrap; gap: 16px; font-size: 14px; color: var(--color-text-muted); margin-bottom: 12px;">
                                <span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <?= formatDate($eventItem['event_date'], 'd M Y, H:i') ?> WIB
                                </span>
                                <span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <?= htmlspecialchars($eventItem['location']) ?>
                                </span>
                                <span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                    <?= $eventItem['fee'] > 0 ? 'Rp ' . formatNumber($eventItem['fee']) : 'Gratis' ?>
                                </span>
                            </div>
                            
                            <div style="font-size: 13px; color: var(--color-text-muted);">
                                <strong>Diselenggarakan oleh:</strong> <?= htmlspecialchars($eventItem['organizer_name']) ?>
                                <br>
                                <strong>Email:</strong> <?= htmlspecialchars($eventItem['organizer_email']) ?>
                                <br>
                                <strong>Diajukan pada:</strong> <?= formatDate($eventItem['created_at'], 'd M Y, H:i') ?>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <?php if ($status === 'pending'): ?>
                                <!-- Approve Button -->
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="event_id" value="<?= $eventItem['event_id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="reason" value="Event disetujui">
                                    <button type="submit" class="btn btn-success" style="width: 100%;"
                                            onclick="return confirm('Setujui event ini?')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Setujui
                                    </button>
                                </form>
                                
                                <!-- Reject Button -->
                                <button type="button" class="btn btn-danger" style="width: 100%;"
                                        onclick="showRejectModal(<?= $eventItem['event_id'] ?>, '<?= htmlspecialchars(addslashes($eventItem['title'])) ?>')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                    Tolak
                                </button>
                            <?php endif; ?>
                            
                            <a href="<?= url('modules/events/detail.php?id=' . $eventItem['event_id']) ?>" 
                               class="btn btn-outline" style="width: 100%;">
                                Lihat Detail
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
                        <a href="<?= url('modules/events/admin.php?status=' . $status . '&page=' . ($page - 1)) ?>" 
                           class="btn btn-outline btn-sm">Sebelumnya</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="<?= url('modules/events/admin.php?status=' . $status . '&page=' . $i) ?>" 
                           class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= url('modules/events/admin.php?status=' . $status . '&page=' . ($page + 1)) ?>" 
                           class="btn btn-outline btn-sm">Selanjutnya</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- Empty State -->
        <div style="text-align: center; padding: 64px; background: white; border-radius: 12px;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="1" style="margin-bottom: 16px;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <h3 style="margin-bottom: 8px;">Tidak Ada Event</h3>
            <p style="color: var(--color-text-muted);">
                <?php if ($status === 'pending'): ?>
                    Tidak ada event yang menunggu review.
                <?php elseif ($status === 'upcoming'): ?>
                    Belum ada event yang disetujui.
                <?php elseif ($status === 'rejected'): ?>
                    Belum ada event yang ditolak.
                <?php else: ?>
                    Belum ada event yang selesai.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- Reject Modal -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 32px; border-radius: 12px; width: 500px; max-width: 90%;">
        <h3 style="margin-bottom: 16px;">Tolak Event</h3>
        <p style="color: var(--color-text-muted); margin-bottom: 16px;">
            Event: <strong id="rejectEventTitle"></strong>
        </p>
        
        <form method="POST" id="rejectForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="event_id" id="rejectEventId">
            <input type="hidden" name="action" value="reject">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Alasan Penolakan *</label>
                <textarea name="reason" class="form-input" rows="4" required
                          placeholder="Jelaskan alasan mengapa event ini ditolak..."></textarea>
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak Event</button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal(eventId, eventTitle) {
    document.getElementById('rejectEventId').value = eventId;
    document.getElementById('rejectEventTitle').textContent = eventTitle;
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