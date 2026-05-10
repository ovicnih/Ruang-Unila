<?php
/**
 * Laporan & Analytics - Ruang Unila
 * 
 * Laporan statistik berita, event, dengan export PDF
 * dan grafik interaktif.
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
$pageTitle = 'Laporan & Analytics';

// Filter periode
$period = isset($_GET['period']) ? cleanInput($_GET['period']) : 'month'; // week, month, year, custom
$dateFrom = isset($_GET['date_from']) ? cleanInput($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? cleanInput($_GET['date_to']) : '';

// Tentukan tanggal berdasarkan periode
$startDate = '';
$endDate = date('Y-m-d');

switch ($period) {
    case 'week':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'month':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'year':
        $startDate = date('Y-m-d', strtotime('-365 days'));
        break;
    case 'custom':
        $startDate = $dateFrom ?: date('Y-m-d', strtotime('-30 days'));
        $endDate = $dateTo ?: date('Y-m-d');
        break;
}

// Export PDF
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    // Generate PDF content (simple HTML to PDF)
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_' . date('Y-m-d_His') . '.html');
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Laporan Ruang Unila</title>";
    echo "<style>body{font-family:Arial,sans-serif; padding:20px;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f5f5f5;}</style>";
    echo "</head><body>";
    echo "<h1>Laporan Ruang Unila</h1>";
    echo "<p>Periode: " . formatDate($startDate) . " - " . formatDate($endDate) . "</p>";
    echo "<p>Digenerate: " . date('d F Y H:i:s') . "</p>";
    echo "<hr>";
    
    // Statistik Berita
    $newsStatsSql = "SELECT category, COUNT(*) as total, SUM(views) as total_views 
                     FROM news 
                     WHERE created_at BETWEEN :start AND :end 
                     GROUP BY category";
    $newsStats = $db->fetchAll($newsStatsSql, [':start' => $startDate . ' 00:00:00', ':end' => $endDate . ' 23:59:59']);
    
    echo "<h2>Statistik Berita per Kategori</h2>";
    echo "<table><tr><th>Kategori</th><th>Jumlah</th><th>Total Views</th></tr>";
    foreach ($newsStats as $stat) {
        echo "<tr><td>" . (NEWS_CATEGORIES[$stat['category']] ?? $stat['category']) . "</td><td>{$stat['total']}</td><td>{$stat['total_views']}</td></tr>";
    }
    echo "</table>";
    
    // Statistik Event
    $eventStatsSql = "SELECT COUNT(*) as total_events, 
                             SUM(CASE WHEN status = 'upcoming' THEN 1 ELSE 0 END) as upcoming,
                             SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                             SUM(max_participants) as total_capacity
                      FROM events 
                      WHERE created_at BETWEEN :start AND :end";
    $eventStats = $db->fetchOne($eventStatsSql, [':start' => $startDate . ' 00:00:00', ':end' => $endDate . ' 23:59:59']);
    
    echo "<h2>Statistik Event</h2>";
    echo "<table><tr><th>Metrik</th><th>Nilai</th></tr>";
    echo "<tr><td>Total Event</td><td>{$eventStats['total_events']}</td></tr>";
    echo "<tr><td>Upcoming</td><td>{$eventStats['upcoming']}</td></tr>";
    echo "<tr><td>Completed</td><td>{$eventStats['completed']}</td></tr>";
    echo "<tr><td>Total Kapasitas</td><td>{$eventStats['total_capacity']}</td></tr>";
    echo "</table>";
    
    // Statistik User
    $userStatsSql = "SELECT role, COUNT(*) as total FROM users GROUP BY role";
    $userStats = $db->fetchAll($userStatsSql);
    
    echo "<h2>Statistik User</h2>";
    echo "<table><tr><th>Role</th><th>Jumlah</th></tr>";
    foreach ($userStats as $stat) {
        echo "<tr><td>" . ucfirst($stat['role']) . "</td><td>{$stat['total']}</td></tr>";
    }
    echo "</table>";
    
    echo "</body></html>";
    exit;
}

// Hitung Statistik
$stats = [];

// Statistik Berita
$sqlNewsTotal = "SELECT COUNT(*) as total FROM news";
$resultNewsTotal = $db->fetchOne($sqlNewsTotal);
$stats['news_total'] = $resultNewsTotal ? (int) $resultNewsTotal['total'] : 0;

$sqlNewsViews = "SELECT SUM(views) as total FROM news";
$resultNewsViews = $db->fetchOne($sqlNewsViews);
$stats['news_total_views'] = $resultNewsViews ? (int) $resultNewsViews['total'] : 0;

// Berita per kategori
$sqlNewsByCategory = "SELECT category, COUNT(*) as count, SUM(views) as views 
                      FROM news 
                      WHERE status = 'published'
                      GROUP BY category";
$newsByCategory = $db->fetchAll($sqlNewsByCategory);

// Berita per periode
$sqlNewsByPeriod = "SELECT DATE(created_at) as date, COUNT(*) as count 
                    FROM news 
                    WHERE created_at BETWEEN :start AND :end
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
$newsByPeriod = $db->fetchAll($sqlNewsByPeriod, [':start' => $startDate . ' 00:00:00', ':end' => $endDate . ' 23:59:59']);

// Top 10 berita terpopuler
$sqlTopNews = "SELECT news_id, title, views, category 
               FROM news 
               WHERE status = 'published'
               ORDER BY views DESC 
               LIMIT 10";
$topNews = $db->fetchAll($sqlTopNews);

// Statistik Event
$sqlEventTotal = "SELECT COUNT(*) as total FROM events";
$resultEventTotal = $db->fetchOne($sqlEventTotal);
$stats['event_total'] = $resultEventTotal ? (int) $resultEventTotal['total'] : 0;

$sqlEventUpcoming = "SELECT COUNT(*) as total FROM events WHERE status = 'upcoming'";
$resultEventUpcoming = $db->fetchOne($sqlEventUpcoming);
$stats['event_upcoming'] = $resultEventUpcoming ? (int) $resultEventUpcoming['total'] : 0;

$sqlEventCompleted = "SELECT COUNT(*) as total FROM events WHERE status = 'completed'";
$resultEventCompleted = $db->fetchOne($sqlEventCompleted);
$stats['event_completed'] = $resultEventCompleted ? (int) $resultEventCompleted['total'] : 0;

// Pendaftar per event
$sqlRegistrationsByEvent = "SELECT e.title, COUNT(er.registration_id) as registrations, e.max_participants
                            FROM events e
                            LEFT JOIN event_registrations er ON e.event_id = er.event_id
                            GROUP BY e.event_id
                            ORDER BY registrations DESC
                            LIMIT 10";
$registrationsByEvent = $db->fetchAll($sqlRegistrationsByEvent);

// Registrasi per periode
$sqlRegistrationsByPeriod = "SELECT DATE(registered_at) as date, COUNT(*) as count 
                             FROM event_registrations 
                             WHERE registered_at BETWEEN :start AND :end
                             GROUP BY DATE(registered_at)
                             ORDER BY date ASC";
$registrationsByPeriod = $db->fetchAll($sqlRegistrationsByPeriod, [':start' => $startDate . ' 00:00:00', ':end' => $endDate . ' 23:59:59']);

// Revenue (dari event berbayar)
$sqlRevenue = "SELECT SUM(e.fee) as total_revenue
               FROM event_registrations er
               JOIN events e ON er.event_id = e.event_id
               WHERE er.payment_status = 'verified' 
               AND e.fee > 0
               AND er.registered_at BETWEEN :start AND :end";
$resultRevenue = $db->fetchOne($sqlRevenue, [':start' => $startDate . ' 00:00:00', ':end' => $endDate . ' 23:59:59']);
$stats['total_revenue'] = $resultRevenue ? (float) $resultRevenue['total_revenue'] : 0;

// Statistik User
$sqlUserTotal = "SELECT COUNT(*) as total FROM users";
$resultUserTotal = $db->fetchOne($sqlUserTotal);
$stats['user_total'] = $resultUserTotal ? (int) $resultUserTotal['total'] : 0;

$sqlUsersByRole = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$usersByRole = $db->fetchAll($sqlUsersByRole);

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">
                Laporan & Analytics
            </h1>
            <p style="color: var(--color-text-secondary); font-size: 14px;">
                Analisis performa sistem Ruang Unila
            </p>
        </div>
        <a href="<?= url('modules/admin/reports.php?export=pdf&period=' . $period . ($dateFrom ? '&date_from=' . $dateFrom : '') . ($dateTo ? '&date_to=' . $dateTo : '')) ?>" 
           class="btn btn-primary">
            📥 Export PDF
        </a>
    </div>

    <!-- Filter Periode -->
    <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm); margin-bottom: 32px;">
        <form method="GET" action="" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Periode</label>
                <select name="period" class="form-input" onchange="this.form.submit()">
                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>7 Hari Terakhir</option>
                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>30 Hari Terakhir</option>
                    <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>1 Tahun Terakhir</option>
                    <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom</option>
                </select>
            </div>
            
            <?php if ($period === 'custom'): ?>
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Dari Tanggal</label>
                <input type="date" name="date_from" value="<?= $dateFrom ?>" class="form-input">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px;">Sampai Tanggal</label>
                <input type="date" name="date_to" value="<?= $dateTo ?>" class="form-input">
            </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
        <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 12px;">
            Periode: <?= formatDate($startDate) ?> - <?= formatDate($endDate) ?>
        </p>
    </div>

    <!-- Overview Stats -->
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 32px;">
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: var(--shadow-sm); text-align: center;">
            <div style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 8px;">Total Berita</div>
            <div style="font-size: 32px; font-weight: 700; color: var(--color-primary);"><?= $stats['news_total'] ?></div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: var(--shadow-sm); text-align: center;">
            <div style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 8px;">Total Views</div>
            <div style="font-size: 32px; font-weight: 700; color: var(--color-success);"><?= formatNumber($stats['news_total_views']) ?></div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: var(--shadow-sm); text-align: center;">
            <div style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 8px;">Total Event</div>
            <div style="font-size: 32px; font-weight: 700; color: var(--color-warning);"><?= $stats['event_total'] ?></div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: var(--shadow-sm); text-align: center;">
            <div style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 8px;">Total User</div>
            <div style="font-size: 32px; font-weight: 700; color: #3b82f6;"><?= $stats['user_total'] ?></div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: var(--shadow-sm); text-align: center;">
            <div style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 8px;">Revenue</div>
            <div style="font-size: 32px; font-weight: 700; color: #8b5cf6;">Rp <?= formatNumber($stats['total_revenue']) ?></div>
        </div>
    </div>

    <!-- Charts Row -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 32px;">
        <!-- News by Category -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h3 style="font-family: var(--font-heading); font-size: 18px; margin-bottom: 20px;">Berita per Kategori</h3>
            <div id="newsByCategoryChart" style="height: 300px;">
                <?php foreach ($newsByCategory as $item): ?>
                    <div style="display: flex; align-items: center; margin-bottom: 12px;">
                        <div style="width: 100px; font-size: 13px; color: var(--color-text-secondary);">
                            <?= NEWS_CATEGORIES[$item['category']] ?? $item['category'] ?>
                        </div>
                        <div style="flex: 1; height: 24px; background: var(--color-bg); border-radius: 4px; overflow: hidden;">
                            <?php $width = $stats['news_total'] > 0 ? ($item['count'] / $stats['news_total'] * 100) : 0; ?>
                            <div style="width: <?= $width ?>%; height: 100%; background: var(--color-primary); border-radius: 4px;"></div>
                        </div>
                        <div style="width: 60px; text-align: right; font-weight: 700; font-size: 14px;">
                            <?= $item['count'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Users by Role -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h3 style="font-family: var(--font-heading); font-size: 18px; margin-bottom: 20px;">User per Role</h3>
            <div id="usersByRoleChart" style="height: 300px;">
                <?php foreach ($usersByRole as $item): ?>
                    <div style="display: flex; align-items: center; margin-bottom: 12px;">
                        <div style="width: 100px; font-size: 13px; color: var(--color-text-secondary);">
                            <?= ucfirst($item['role']) ?>
                        </div>
                        <div style="flex: 1; height: 24px; background: var(--color-bg); border-radius: 4px; overflow: hidden;">
                            <?php $width = $stats['user_total'] > 0 ? ($item['count'] / $stats['user_total'] * 100) : 0; ?>
                            <div style="width: <?= $width ?>%; height: 100%; background: #3b82f6; border-radius: 4px;"></div>
                        </div>
                        <div style="width: 60px; text-align: right; font-weight: 700; font-size: 14px;">
                            <?= $item['count'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Top News & Events -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 32px;">
        <!-- Top 10 Berita -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h3 style="font-family: var(--font-heading); font-size: 18px; margin-bottom: 20px;">Top 10 Berita Terpopuler</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($topNews as $index => $news): ?>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <span style="width: 24px; height: 24px; background: var(--color-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;"><?= $index + 1 ?></span>
                        <div style="flex: 1; min-width: 0;">
                            <a href="<?= url('modules/news/detail.php?id=' . $news['news_id']) ?>" 
                               style="font-size: 14px; font-weight: 600; color: var(--color-text-primary); text-decoration: none; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= htmlspecialchars($news['title']) ?>
                            </a>
                            <span style="font-size: 12px; color: var(--color-text-muted);">
                                <?= NEWS_CATEGORIES[$news['category']] ?? $news['category'] ?>
                            </span>
                        </div>
                        <span style="font-size: 14px; font-weight: 700; color: var(--color-primary);">
                            <?= formatNumber($news['views']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Top Events by Registrations -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h3 style="font-family: var(--font-heading); font-size: 18px; margin-bottom: 20px;">Event Terpopuler</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($registrationsByEvent as $index => $event): ?>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <span style="width: 24px; height: 24px; background: var(--color-warning); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;"><?= $index + 1 ?></span>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: 14px; font-weight: 600; color: var(--color-text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= htmlspecialchars($event['title']) ?>
                            </div>
                            <div style="font-size: 12px; color: var(--color-text-muted);">
                                <?= $event['registrations'] ?> / <?= $event['max_participants'] ?> peserta
                            </div>
                        </div>
                        <?php $percentage = $event['max_participants'] > 0 ? ($event['registrations'] / $event['max_participants'] * 100) : 0; ?>
                        <div style="width: 60px;">
                            <div style="height: 6px; background: var(--color-bg); border-radius: 3px; overflow: hidden;">
                                <div style="width: <?= min(100, $percentage) ?>%; height: 100%; background: <?= $percentage >= 100 ? 'var(--color-error)' : 'var(--color-success)' ?>; border-radius: 3px;"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Detailed Stats -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
        <!-- Event Status -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h3 style="font-family: var(--font-heading); font-size: 18px; margin-bottom: 20px;">Status Event</h3>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--color-text-muted);">Upcoming</span>
                    <span style="font-weight: 700; color: var(--color-success);"><?= $stats['event_upcoming'] ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--color-text-muted);">Completed</span>
                    <span style="font-weight: 700; color: var(--color-text-muted);"><?= $stats['event_completed'] ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--color-text-muted);">Ongoing</span>
                    <span style="font-weight: 700; color: #3b82f6;"><?= $stats['event_total'] - $stats['event_upcoming'] - $stats['event_completed'] ?></span>
                </div>
            </div>
        </div>

        <!-- News Views -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h3 style="font-family: var(--font-heading); font-size: 18px; margin-bottom: 20px;">Views per Kategori</h3>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($newsByCategory as $item): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--color-text-muted);"><?= NEWS_CATEGORIES[$item['category']] ?? $item['category'] ?></span>
                        <span style="font-weight: 700;"><?= formatNumber($item['views']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Revenue -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h3 style="font-family: var(--font-heading); font-size: 18px; margin-bottom: 20px;">Revenue Summary</h3>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--color-text-muted);">Total Revenue</span>
                    <span style="font-weight: 700; color: var(--color-primary);">Rp <?= formatNumber($stats['total_revenue']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--color-text-muted);">Event Berbayar</span>
                    <span style="font-weight: 700;"><?= count($registrationsByEvent) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>