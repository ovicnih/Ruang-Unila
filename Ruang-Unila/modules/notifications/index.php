<?php
/**
 * Sistem Notifikasi - Ruang Unila
 * 
 * Notifikasi untuk admin (berita pending, event baru)
 * dan untuk user (event terkonfirmasi).
 * 
 * @package RuangUnila
 * @subpackage Modules/Notifications
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
$session = Session::getInstance();
$db = Database::getInstance();
$userId = $session->getUserId();
$userRole = $session->getUserRole();
$pageTitle = 'Notifikasi';

// Mark notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notifId = (int) $_GET['mark_read'];
    $updateSql = "UPDATE notifications SET is_read = 1 WHERE notification_id = :id AND user_id = :user_id";
    $db->executeQuery($updateSql, [':id' => $notifId, ':user_id' => $userId]);
    redirect(url('modules/notifications/index.php'));
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $updateSql = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id";
    $db->executeQuery($updateSql, [':user_id' => $userId]);
    redirect(url('modules/notifications/index.php'));
}

// Delete notification
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notifId = (int) $_GET['delete'];
    $deleteSql = "DELETE FROM notifications WHERE notification_id = :id AND user_id = :user_id";
    $db->executeQuery($deleteSql, [':id' => $notifId, ':user_id' => $userId]);
    redirect(url('modules/notifications/index.php'));
}

// Generate notifications for admin
if ($userRole === ROLE_ADMIN) {
    // Check pending news
    $pendingNewsSql = "SELECT COUNT(*) as count FROM news WHERE status = 'pending'";
    $pendingNewsResult = $db->fetchOne($pendingNewsSql);
    $pendingNewsCount = $pendingNewsResult ? (int) $pendingNewsResult['count'] : 0;
    
    // Check pending payments
    $pendingPaymentsSql = "SELECT COUNT(*) as count FROM event_registrations WHERE payment_status = 'pending'";
    $pendingPaymentsResult = $db->fetchOne($pendingPaymentsSql);
    $pendingPaymentsCount = $pendingPaymentsResult ? (int) $pendingPaymentsResult['count'] : 0;
    
    // Check new events (last 7 days)
    $newEventsSql = "SELECT COUNT(*) as count FROM events WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $newEventsResult = $db->fetchOne($newEventsSql);
    $newEventsCount = $newEventsResult ? (int) $newEventsResult['count'] : 0;
    
    // Create notifications if not exists
    if ($pendingNewsCount > 0) {
        $checkNotifSql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND type = 'pending_news' AND is_read = 0";
        $checkResult = $db->fetchOne($checkNotifSql, [':user_id' => $userId]);
        
        if ($checkResult['count'] == 0) {
            $insertSql = "INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
                          VALUES (:user_id, 'pending_news', 'Berita Pending', :message, :link, 0, NOW())";
            $db->executeQuery($insertSql, [
                ':user_id' => $userId,
                ':message' => "Ada {$pendingNewsCount} berita yang menunggu validasi.",
                ':link' => url('modules/news/admin.php?status=pending')
            ]);
        }
    }
    
    if ($pendingPaymentsCount > 0) {
        $checkNotifSql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND type = 'pending_payment' AND is_read = 0";
        $checkResult = $db->fetchOne($checkNotifSql, [':user_id' => $userId]);
        
        if ($checkResult['count'] == 0) {
            $insertSql = "INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
                          VALUES (:user_id, 'pending_payment', 'Pembayaran Pending', :message, :link, 0, NOW())";
            $db->executeQuery($insertSql, [
                ':user_id' => $userId,
                ':message' => "Ada {$pendingPaymentsCount} pembayaran yang menunggu verifikasi.",
                ':link' => url('modules/events/admin_payments.php')
            ]);
        }
    }
}

// Generate notifications for students
if ($userRole === ROLE_STUDENT) {
    // Check verified registrations
    $verifiedSql = "SELECT COUNT(*) as count FROM event_registrations WHERE user_id = :user_id AND payment_status = 'verified' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $verifiedResult = $db->fetchOne($verifiedSql, [':user_id' => $userId]);
    $verifiedCount = $verifiedResult ? (int) $verifiedResult['count'] : 0;
    
    if ($verifiedCount > 0) {
        $checkNotifSql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND type = 'event_verified' AND is_read = 0";
        $checkResult = $db->fetchOne($checkNotifSql, [':user_id' => $userId]);
        
        if ($checkResult['count'] == 0) {
            $insertSql = "INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
                          VALUES (:user_id, 'event_verified', 'Pendaftaran Dikonfirmasi', :message, :link, 0, NOW())";
            $db->executeQuery($insertSql, [
                ':user_id' => $userId,
                ':message' => "Pendaftaran event Anda telah dikonfirmasi!",
                ':link' => url('modules/profile/index.php')
            ]);
        }
    }
}

// Generate notifications for organisations
if ($userRole === ROLE_ORGANISATION) {
    // Check published news
    $publishedSql = "SELECT COUNT(*) as count FROM news WHERE author_id = :user_id AND status = 'published' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $publishedResult = $db->fetchOne($publishedSql, [':user_id' => $userId]);
    $publishedCount = $publishedResult ? (int) $publishedResult['count'] : 0;
    
    if ($publishedCount > 0) {
        $checkNotifSql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND type = 'news_published' AND is_read = 0";
        $checkResult = $db->fetchOne($checkNotifSql, [':user_id' => $userId]);
        
        if ($checkResult['count'] == 0) {
            $insertSql = "INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
                          VALUES (:user_id, 'news_published', 'Berita Dipublikasi', :message, :link, 0, NOW())";
            $db->executeQuery($insertSql, [
                ':user_id' => $userId,
                ':message' => "Berita Anda telah dipublikasi!",
                ':link' => url('modules/news/manage.php')
            ]);
        }
    }
}

// Ambil semua notifikasi user
$sql = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC";
$notifications = $db->fetchAll($sql, [':user_id' => $userId]);

// Hitung unread count
$unreadSql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0";
$unreadResult = $db->fetchOne($unreadSql, [':user_id' => $userId]);
$unreadCount = $unreadResult ? (int) $unreadResult['count'] : 0;

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0; max-width: 800px; margin: 0 auto;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">
                Notifikasi
            </h1>
            <p style="color: var(--color-text-secondary); font-size: 14px;">
                <?= $unreadCount > 0 ? "Anda memiliki {$unreadCount} notifikasi belum dibaca" : 'Semua notifikasi sudah dibaca' ?>
            </p>
        </div>
        <?php if ($unreadCount > 0): ?>
            <a href="<?= url('modules/notifications/index.php?mark_all_read=1') ?>" class="btn btn-secondary">
                Tandai Semua Dibaca
            </a>
        <?php endif; ?>
    </div>

    <!-- Notifications List -->
    <div style="background: white; border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden;">
        <?php if (count($notifications) > 0): ?>
            <?php foreach ($notifications as $notif): ?>
                <div style="display: flex; gap: 16px; padding: 20px; border-bottom: 1px solid var(--color-border-light); <?= $notif['is_read'] == 0 ? 'background: rgba(158, 0, 0, 0.02);' : '' ?>">
                    <!-- Icon -->
                    <div style="width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
                        background: <?= $notif['is_read'] == 0 ? 'var(--color-primary-light)' : 'var(--color-bg)' ?>;">
                        <?php
                        $iconColor = $notif['is_read'] == 0 ? 'var(--color-primary)' : 'var(--color-text-muted)';
                        switch ($notif['type']) {
                            case 'pending_news':
                            case 'pending_payment':
                                $icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="' . $iconColor . '" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>';
                                break;
                            case 'event_verified':
                            case 'news_published':
                                $icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="' . $iconColor . '" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
                                break;
                            default:
                                $icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="' . $iconColor . '" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>';
                        }
                        echo $icon;
                        ?>
                    </div>
                    
                    <!-- Content -->
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <h3 style="font-weight: 700; font-size: 16px; color: var(--color-text-primary); margin: 0;">
                                <?= htmlspecialchars($notif['title']) ?>
                            </h3>
                            <?php if ($notif['is_read'] == 0): ?>
                                <span style="width: 8px; height: 8px; background: var(--color-primary); border-radius: 50%; flex-shrink: 0;"></span>
                            <?php endif; ?>
                        </div>
                        <p style="color: var(--color-text-secondary); font-size: 14px; margin-bottom: 8px; line-height: 1.5;">
                            <?= htmlspecialchars($notif['message']) ?>
                        </p>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 12px; color: var(--color-text-muted);">
                                <?= timeAgo($notif['created_at']) ?>
                            </span>
                            <div style="display: flex; gap: 8px;">
                                <?php if ($notif['is_read'] == 0): ?>
                                    <a href="<?= url('modules/notifications/index.php?mark_read=' . $notif['notification_id']) ?>" 
                                       style="font-size: 12px; color: var(--color-primary); text-decoration: none;">
                                        Tandai dibaca
                                    </a>
                                <?php endif; ?>
                                <?php if ($notif['link']): ?>
                                    <a href="<?= $notif['link'] ?>" 
                                       style="font-size: 12px; color: var(--color-primary); text-decoration: none; font-weight: 700;">
                                        Lihat →
                                    </a>
                                <?php endif; ?>
                                <a href="<?= url('modules/notifications/index.php?delete=' . $notif['notification_id']) ?>" 
                                   style="font-size: 12px; color: var(--color-error); text-decoration: none;"
                                   onclick="return confirm('Hapus notifikasi ini?')">
                                    Hapus
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="padding: 60px 20px; text-align: center;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="1.5" style="margin-bottom: 16px;">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <h3 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 8px;">Tidak ada notifikasi</h3>
                <p style="color: var(--color-text-muted);">Anda akan menerima notifikasi untuk aktivitas penting</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>