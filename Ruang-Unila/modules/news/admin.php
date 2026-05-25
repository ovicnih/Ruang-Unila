<?php
/**
 * Admin News Validation - Ruang Unila
 * 
 * Dashboard admin untuk memvalidasi berita yang masuk.
 * Admin bisa approve/reject berita dengan satu klik.
 * 
 * @package RuangUnila
 * @subpackage Modules/News
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
$news = new News();
$pageTitle = 'Validasi Berita';

// Proses aksi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $newsId = (int)($_POST['news_id'] ?? 0);
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    // Validasi CSRF
    if (!$session->verifyCsrfToken($csrfToken)) {
        $session->setFlash('error', 'Token keamanan tidak valid.');
        redirect(url('modules/news/admin.php'));
    }
    
    // Validasi ID
    if ($newsId <= 0) {
        $session->setFlash('error', 'ID berita tidak valid.');
        redirect(url('modules/news/admin.php'));
    }
    
    // Ambil data news
    $newsItem = $news->getById($newsId);
    if (!$newsItem) {
        $session->setFlash('error', 'Berita tidak ditemukan.');
        redirect(url('modules/news/admin.php'));
    }
    
    // Proses aksi
    switch ($action) {
        case 'publish':
            if ($news->updateStatus($newsId, 'published')) {
                $session->setFlash('success', 'Berita "' . htmlspecialchars($newsItem['title']) . '" berhasil dipublikasikan!');
            } else {
                $session->setFlash('error', 'Gagal mempublikasikan berita.');
            }
            break;
            
        case 'reject':
            if ($news->updateStatus($newsId, 'rejected')) {
                $session->setFlash('success', 'Berita "' . htmlspecialchars($newsItem['title']) . '" berhasil ditolak.');
            } else {
                $session->setFlash('error', 'Gagal menolak berita.');
            }
            break;
            
        case 'pending':
            if ($news->updateStatus($newsId, 'pending')) {
                $session->setFlash('success', 'Berita "' . htmlspecialchars($newsItem['title']) . '" dikembalikan ke pending.');
            } else {
                $session->setFlash('error', 'Gagal mengubah status berita.');
            }
            break;
            
        default:
            $session->setFlash('error', 'Aksi tidak valid.');
            break;
    }
    
    redirect(url('modules/news/admin.php'));
}

// Ambil parameter filter
$statusFilter = isset($_GET['status']) ? cleanInput($_GET['status']) : null;
$page = getCurrentPage();
$perPage = ADMIN_ITEMS_PER_PAGE;

// Validasi status
$validStatuses = ['draft', 'pending', 'published', 'rejected'];
if ($statusFilter && !in_array($statusFilter, $validStatuses)) {
    $statusFilter = null;
}

// Ambil data news
$result = $news->getAll($page, $perPage, null, $statusFilter);
$newsList = $result['news'];
$totalPages = $result['total_pages'];

// Hitung statistik
$statusCounts = $news->countByStatus();

// Generate CSRF token (gunakan fungsi helper untuk konsistensi)
$csrfToken = generateCSRFToken();

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">
                Validasi Berita
            </h1>
            <p style="color: var(--color-text-secondary); font-size: 14px;">
                Kelola dan validasi berita yang masuk dari organisasi
            </p>
        </div>
        <a href="<?= url('modules/news/create.php') ?>" class="btn btn-primary">
            + Buat Berita
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
                <?= array_sum($statusCounts) ?>
            </div>
        </div>
        <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px;">
                Published
            </div>
            <div style="font-size: 24px; font-weight: 700; color: var(--color-success);">
                <?= $statusCounts['published'] ?>
            </div>
        </div>
        <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px;">
                Pending
            </div>
            <div style="font-size: 24px; font-weight: 700; color: var(--color-warning);">
                <?= $statusCounts['pending'] ?>
            </div>
        </div>
        <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px;">
                Rejected
            </div>
            <div style="font-size: 24px; font-weight: 700; color: var(--color-error);">
                <?= $statusCounts['rejected'] ?>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div style="display: flex; gap: 12px; margin-bottom: 24px;">
        <a href="<?= url('modules/news/admin.php') ?>" 
           class="btn <?= !$statusFilter ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            Semua
        </a>
        <a href="<?= url('modules/news/admin.php?status=pending') ?>" 
           class="btn <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            Pending
        </a>
        <a href="<?= url('modules/news/admin.php?status=published') ?>" 
           class="btn <?= $statusFilter === 'published' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            Published
        </a>
        <a href="<?= url('modules/news/admin.php?status=draft') ?>" 
           class="btn <?= $statusFilter === 'draft' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            Draft
        </a>
        <a href="<?= url('modules/news/admin.php?status=rejected') ?>" 
           class="btn <?= $statusFilter === 'rejected' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            Rejected
        </a>
    </div>

    <!-- News Table -->
    <div style="background: white; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow-x: auto;">
        <!-- Table Header -->
                <div style="display: grid; grid-template-columns: 1fr 120px 100px 100px 340px; gap: 16px; padding: 16px; background: var(--color-border-light); font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.6px; color: var(--color-text-muted);">
            <div>Berita</div>
            <div>Penulis</div>
            <div>Status</div>
            <div>Views</div>
            <div>Aksi</div>
        </div>
        
        <!-- Table Body -->
        <?php if (count($newsList) > 0): ?>
            <?php foreach ($newsList as $newsItem): ?>
                <div style="display: grid; grid-template-columns: 1fr 120px 100px 100px 340px; gap: 16px; padding: 16px; border-bottom: 1px solid var(--color-border-light); align-items: center;">
                    <!-- News Info -->
                    <div style="display: flex; gap: 16px; align-items: center; min-width: 0;">
                        <div style="width: 64px; height: 64px; background: url('<?= $newsItem['image'] ? url('assets/images/uploads/news/' . $newsItem['image']) : url('assets/images/placeholder.jpg') ?>') center/cover; border-radius: 8px; flex-shrink: 0;"></div>
                        <div style="flex: 1; min-width: 0;">
                            <a href="<?= url('modules/news/detail.php?id=' . $newsItem['news_id']) ?>" 
                               style="font-family: var(--font-primary); font-weight: 700; font-size: 14px; color: var(--color-text-primary); text-decoration: none; display: block; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= htmlspecialchars($newsItem['title']) ?>
                            </a>
                            <div style="font-family: var(--font-primary); font-size: 12px; color: var(--color-text-muted);">
                                <?= NEWS_CATEGORIES[$newsItem['category']] ?? $newsItem['category'] ?> • <?= formatDate($newsItem['created_at']) ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Author -->
                    <div style="font-size: 13px; color: var(--color-text-secondary);">
                        <?= htmlspecialchars($newsItem['author_name'] ?? 'Unknown') ?>
                    </div>
                    
                    <!-- Status -->
                    <div>
                        <?php
                        $statusColors = [
                            'published' => ['bg' => 'rgba(16, 185, 129, 0.1)', 'color' => 'var(--color-success)'],
                            'pending' => ['bg' => 'rgba(245, 158, 11, 0.1)', 'color' => 'var(--color-warning)'],
                            'draft' => ['bg' => 'rgba(113, 113, 122, 0.1)', 'color' => 'var(--color-text-muted)'],
                            'rejected' => ['bg' => 'rgba(239, 68, 68, 0.1)', 'color' => 'var(--color-error)']
                        ];
                        $statusStyle = $statusColors[$newsItem['status']] ?? $statusColors['draft'];
                        ?>
                        <span style="display: inline-flex; padding: 4px 8px; background: <?= $statusStyle['bg'] ?>; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: <?= $statusStyle['color'] ?>;">
                            <?= $newsItem['status'] ?>
                        </span>
                    </div>
                    
                    <!-- Views -->
                    <div style="font-size: 14px; color: var(--color-text-secondary);">
                        <?= formatNumber($newsItem['views']) ?>
                    </div>
                    
                    <!-- Actions -->
                    <div style="display: flex; gap: 8px; flex-wrap: nowrap;">
                        <a href="<?= url('modules/news/edit.php?id=' . $newsItem['news_id']) ?>" 
                           class="btn btn-secondary btn-sm"
                           style="padding: 6px 12px; white-space: nowrap;">
                            Edit
                        </a>
                        <a href="<?= url('modules/news/detail.php?id=' . $newsItem['news_id']) ?>" 
                           class="btn btn-secondary btn-sm"
                           style="padding: 6px 12px; white-space: nowrap;">
                            Preview
                        </a>
                        
                        <?php if ($newsItem['status'] === 'pending'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="publish">
                                <input type="hidden" name="news_id" value="<?= $newsItem['news_id'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm" style="padding: 6px 12px;">
                                    Publish
                                </button>
                            </form>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="news_id" value="<?= $newsItem['news_id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="padding: 6px 12px; color: var(--color-error); border-color: var(--color-error);">
                                    Reject
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($newsItem['status'] === 'published'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="pending">
                                <input type="hidden" name="news_id" value="<?= $newsItem['news_id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="padding: 6px 12px;">
                                    Unpublish
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($newsItem['status'] === 'rejected'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="pending">
                                <input type="hidden" name="news_id" value="<?= $newsItem['news_id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="padding: 6px 12px;">
                                    Restore
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="padding: 40px; text-align: center; color: var(--color-text-muted);">
                Tidak ada berita yang ditemukan.
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination" style="margin-top: 24px;">
            <?php if ($page > 1): ?>
                <?php $prevPage = $page - 1; ?>
                <?php $statusParam = $statusFilter ? "&status=$statusFilter" : ''; ?>
                <a href="<?= url("modules/news/admin.php?page=$prevPage$statusParam") ?>" 
                   class="pagination-link">
                    &laquo;
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php $statusParam = $statusFilter ? "&status=$statusFilter" : ''; ?>
                <a href="<?= url("modules/news/admin.php?page=$i$statusParam") ?>" 
                   class="pagination-link <?= $page === $i ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <?php $nextPage = $page + 1; ?>
                <?php $statusParam = $statusFilter ? "&status=$statusFilter" : ''; ?>
                <a href="<?= url("modules/news/admin.php?page=$nextPage$statusParam") ?>" 
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