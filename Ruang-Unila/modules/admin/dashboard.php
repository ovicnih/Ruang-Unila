<?php
/**
 * Admin Dashboard - Ruang Unila
 * 
 * Panel kontrol utama admin dengan statistik sistem,
 * quick actions, dan recent activities.
 * 
 * @package RuangUnila
 * @subpackage Modules/Admin
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
$db = Database::getInstance();
$pageTitle = 'Admin Dashboard';

// Ambil statistik
$stats = [];

// Total Users
$sqlUsers = "SELECT COUNT(*) as total FROM users";
$resultUsers = $db->fetchOne($sqlUsers);
$stats['total_users'] = $resultUsers ? (int) $resultUsers['total'] : 0;

// Users by Role
$sqlUsersByRole = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$resultUsersByRole = $db->fetchAll($sqlUsersByRole);
$stats['users_by_role'] = [];
foreach ($resultUsersByRole as $row) {
    $stats['users_by_role'][$row['role']] = (int) $row['count'];
}

// Total News
$sqlNews = "SELECT COUNT(*) as total FROM news";
$resultNews = $db->fetchOne($sqlNews);
$stats['total_news'] = $resultNews ? (int) $resultNews['total'] : 0;

// News by Status
$sqlNewsByStatus = "SELECT status, COUNT(*) as count FROM news GROUP BY status";
$resultNewsByStatus = $db->fetchAll($sqlNewsByStatus);
$stats['news_by_status'] = [];
foreach ($resultNewsByStatus as $row) {
    $stats['news_by_status'][$row['status']] = (int) $row['count'];
}

// Total Events
$sqlEvents = "SELECT COUNT(*) as total FROM events";
$resultEvents = $db->fetchOne($sqlEvents);
$stats['total_events'] = $resultEvents ? (int) $resultEvents['total'] : 0;

// Events by Status
$sqlEventsByStatus = "SELECT status, COUNT(*) as count FROM events GROUP BY status";
$resultEventsByStatus = $db->fetchAll($sqlEventsByStatus);
$stats['events_by_status'] = [];
foreach ($resultEventsByStatus as $row) {
    $stats['events_by_status'][$row['status']] = (int) $row['count'];
}

// Total Registrations
$sqlRegistrations = "SELECT COUNT(*) as total FROM event_registrations";
$resultRegistrations = $db->fetchOne($sqlRegistrations);
$stats['total_registrations'] = $resultRegistrations ? (int) $resultRegistrations['total'] : 0;

// Pending Payments
$sqlPendingPayments = "SELECT COUNT(*) as total FROM event_registrations WHERE payment_status = 'pending'";
$resultPendingPayments = $db->fetchOne($sqlPendingPayments);
$stats['pending_payments'] = $resultPendingPayments ? (int) $resultPendingPayments['total'] : 0;

// Pending News
$sqlPendingNews = "SELECT COUNT(*) as total FROM news WHERE status = 'pending'";
$resultPendingNews = $db->fetchOne($sqlPendingNews);
$stats['pending_news'] = $resultPendingNews ? (int) $resultPendingNews['total'] : 0;

// Recent News (5 terbaru)
$sqlRecentNews = "SELECT n.news_id, n.title, n.status, n.created_at, u.full_name as author_name 
                  FROM news n 
                  LEFT JOIN users u ON n.author_id = u.user_id 
                  ORDER BY n.created_at DESC 
                  LIMIT 5";
$recentNews = $db->fetchAll($sqlRecentNews);

// Recent Events (5 terbaru)
$sqlRecentEvents = "SELECT event_id, title, status, event_date, created_at 
                    FROM events 
                    ORDER BY created_at DESC 
                    LIMIT 5";
$recentEvents = $db->fetchAll($sqlRecentEvents);

// Recent Registrations (5 terbaru)
$sqlRecentRegistrations = "SELECT er.registration_id, er.registration_number, er.payment_status, er.created_at,
                           u.full_name as user_name, e.title as event_title
                           FROM event_registrations er
                           LEFT JOIN users u ON er.user_id = u.user_id
                           LEFT JOIN events e ON er.event_id = e.event_id
                           ORDER BY er.created_at DESC
                           LIMIT 5";
$recentRegistrations = $db->fetchAll($sqlRecentRegistrations);

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">
                Admin Dashboard
            </h1>
            <p style="color: var(--color-text-secondary); font-size: 14px;">
                Panel kontrol utama untuk mengelola sistem Ruang Unila
            </p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="<?= url('modules/news/create.php') ?>" class="btn btn-primary">
                + Buat Berita
            </a>
            <a href="<?= url('modules/events/create.php') ?>" class="btn btn-secondary">
                + Buat Event
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 32px;">
        <!-- Total Users -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: var(--color-primary-light); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div>
                    <div style="font-size: 32px; font-weight: 700; color: var(--color-text-primary);">
                        <?= $stats['total_users'] ?>
                    </div>
                    <div style="font-size: 14px; color: var(--color-text-muted);">Total Users</div>
                </div>
            </div>
        </div>

        <!-- Total News -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </div>
                <div>
                    <div style="font-size: 32px; font-weight: 700; color: var(--color-text-primary);">
                        <?= $stats['total_news'] ?>
                    </div>
                    <div style="font-size: 14px; color: var(--color-text-muted);">Total Berita</div>
                </div>
            </div>
        </div>

        <!-- Total Events -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: rgba(245, 158, 11, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div>
                    <div style="font-size: 32px; font-weight: 700; color: var(--color-text-primary);">
                        <?= $stats['total_events'] ?>
                    </div>
                    <div style="font-size: 14px; color: var(--color-text-muted);">Total Event</div>
                </div>
            </div>
        </div>

        <!-- Pending Items -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: rgba(239, 68, 68, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div>
                    <div style="font-size: 32px; font-weight: 700; color: var(--color-text-primary);">
                        <?= $stats['pending_news'] + $stats['pending_payments'] ?>
                    </div>
                    <div style="font-size: 14px; color: var(--color-text-muted);">Perlu Ditinjau</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 32px;">
        <!-- Quick Actions Panel -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h3 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 20px;">Quick Actions</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                <a href="<?= url('modules/news/admin.php') ?>" 
                   style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--color-bg); border-radius: 8px; text-decoration: none; color: var(--color-text-primary); transition: background 0.2s;"
                   onmouseover="this.style.background='var(--color-primary-light)'"
                   onmouseout="this.style.background='var(--color-bg)'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <span style="font-weight: 600;">Validasi Berita</span>
                </a>
                
                <a href="<?= url('modules/events/admin_payments.php') ?>" 
                   style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--color-bg); border-radius: 8px; text-decoration: none; color: var(--color-text-primary); transition: background 0.2s;"
                   onmouseover="this.style.background='var(--color-primary-light)'"
                   onmouseout="this.style.background='var(--color-bg)'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    <span style="font-weight: 600;">Verifikasi Pembayaran</span>
                </a>
                
                <a href="<?= url('modules/events/admin.php') ?>" 
                   style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--color-bg); border-radius: 8px; text-decoration: none; color: var(--color-text-primary); transition: background 0.2s;"
                   onmouseover="this.style.background='var(--color-primary-light)'"
                   onmouseout="this.style.background='var(--color-bg)'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span style="font-weight: 600;">Kelola Event</span>
                </a>
                
                <a href="<?= url('modules/news/manage.php') ?>" 
                   style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--color-bg); border-radius: 8px; text-decoration: none; color: var(--color-text-primary); transition: background 0.2s;"
                   onmouseover="this.style.background='var(--color-primary-light)'"
                   onmouseout="this.style.background='var(--color-bg)'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <span style="font-weight: 600;">Kelola Berita</span>
                </a>
            </div>
        </div>

        <!-- System Stats -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h3 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 20px;">Statistik Sistem</h3>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--color-text-muted);">Total Registrasi Event</span>
                    <span style="font-weight: 700;"><?= $stats['total_registrations'] ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--color-text-muted);">Pembayaran Pending</span>
                    <span style="font-weight: 700; color: var(--color-warning);"><?= $stats['pending_payments'] ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--color-text-muted);">Berita Pending</span>
                    <span style="font-weight: 700; color: var(--color-warning);"><?= $stats['pending_news'] ?></span>
                </div>
                <div style="height: 1px; background: var(--color-border); margin: 8px 0;"></div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--color-text-muted);">Admin</span>
                    <span style="font-weight: 700;"><?= $stats['users_by_role']['admin'] ?? 0 ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--color-text-muted);">Organisasi</span>
                    <span style="font-weight: 700;"><?= $stats['users_by_role']['organisasi'] ?? 0 ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--color-text-muted);">Mahasiswa</span>
                    <span style="font-weight: 700;"><?= $stats['users_by_role']['mahasiswa'] ?? 0 ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
        <!-- Recent News -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-family: var(--font-heading); font-size: 18px; margin: 0;">Berita Terbaru</h3>
                <a href="<?= url('modules/news/manage.php') ?>" style="font-size: 12px; color: var(--color-primary);">Lihat Semua</a>
            </div>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($recentNews as $news): ?>
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="flex: 1;">
                            <a href="<?= url('modules/news/detail.php?id=' . $news['news_id']) ?>" 
                               style="font-weight: 600; font-size: 14px; color: var(--color-text-primary); text-decoration: none; display: block; margin-bottom: 4px;">
                                <?= htmlspecialchars(truncateText($news['title'], 40)) ?>
                            </a>
                            <div style="font-size: 12px; color: var(--color-text-muted);">
                                <?= htmlspecialchars($news['author_name'] ?? 'Unknown') ?> • <?= timeAgo($news['created_at']) ?>
                            </div>
                        </div>
                        <span style="padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase;
                            background: <?= $news['status'] === 'published' ? 'rgba(16, 185, 129, 0.1)' : ($news['status'] === 'pending' ? 'rgba(245, 158, 11, 0.1)' : 'rgba(113, 113, 122, 0.1)') ?>;
                            color: <?= $news['status'] === 'published' ? '#10b981' : ($news['status'] === 'pending' ? '#f59e0b' : '#71717a') ?>;">
                            <?= $news['status'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Events -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-family: var(--font-heading); font-size: 18px; margin: 0;">Event Terbaru</h3>
                <a href="<?= url('modules/events/admin.php') ?>" style="font-size: 12px; color: var(--color-primary);">Lihat Semua</a>
            </div>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($recentEvents as $event): ?>
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="flex: 1;">
                            <a href="<?= url('modules/events/detail.php?id=' . $event['event_id']) ?>" 
                               style="font-weight: 600; font-size: 14px; color: var(--color-text-primary); text-decoration: none; display: block; margin-bottom: 4px;">
                                <?= htmlspecialchars(truncateText($event['title'], 40)) ?>
                            </a>
                            <div style="font-size: 12px; color: var(--color-text-muted);">
                                <?= formatDate($event['event_date'], 'd M Y') ?> • <?= timeAgo($event['created_at']) ?>
                            </div>
                        </div>
                        <span style="padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase;
                            background: <?= $event['status'] === 'upcoming' ? 'rgba(16, 185, 129, 0.1)' : ($event['status'] === 'ongoing' ? 'rgba(59, 130, 246, 0.1)' : ($event['status'] === 'completed' ? 'rgba(113, 113, 122, 0.1)' : 'rgba(239, 68, 68, 0.1)')) ?>;
                            color: <?= $event['status'] === 'upcoming' ? '#10b981' : ($event['status'] === 'ongoing' ? '#3b82f6' : ($event['status'] === 'completed' ? '#71717a' : '#ef4444')) ?>;">
                            <?= $event['status'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Registrations -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-family: var(--font-heading); font-size: 18px; margin: 0;">Verifikasi Pembayaran</h3>
                <a href="<?= url('modules/events/admin_payments.php') ?>" style="font-size: 12px; color: var(--color-primary);">Lihat Semua</a>
            </div>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($recentRegistrations as $reg): ?>
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 14px; color: var(--color-text-primary); margin-bottom: 4px;">
                                <?= htmlspecialchars($reg['user_name'] ?? 'Unknown') ?>
                            </div>
                            <div style="font-size: 12px; color: var(--color-text-muted);">
                                <?= htmlspecialchars(truncateText($reg['event_title'] ?? 'Unknown', 30)) ?>
                            </div>
                        </div>
                        <span style="padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase;
                            background: <?= $reg['payment_status'] === 'verified' ? 'rgba(16, 185, 129, 0.1)' : ($reg['payment_status'] === 'pending' ? 'rgba(245, 158, 11, 0.1)' : 'rgba(239, 68, 68, 0.1)') ?>;
                            color: <?= $reg['payment_status'] === 'verified' ? '#10b981' : ($reg['payment_status'] === 'pending' ? '#f59e0b' : '#ef4444') ?>;">
                            <?= $reg['payment_status'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>