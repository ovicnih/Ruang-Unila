<?php
/**
 * Pencarian - Ruang Unila
 * 
 * Fitur pencarian untuk berita dan event.
 * Support filter advanced dan highlight hasil.
 * 
 * @package RuangUnila
 * @subpackage Modules/Search
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

// Inisialisasi
$session = Session::getInstance();
$db = Database::getInstance();
$news = new News();
$event = new Event();
$pageTitle = 'Pencarian';

// Ambil parameter pencarian
$searchQuery = isset($_GET['q']) ? cleanInput($_GET['q']) : '';
$searchType = isset($_GET['type']) ? cleanInput($_GET['type']) : 'all'; // all, news, events
$category = isset($_GET['category']) ? cleanInput($_GET['category']) : '';
$dateFrom = isset($_GET['date_from']) ? cleanInput($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? cleanInput($_GET['date_to']) : '';
$author = isset($_GET['author']) ? cleanInput($_GET['author']) : '';

// Pagination
$page = getCurrentPage();
$perPage = ITEMS_PER_PAGE;

// Hasil pencarian
$newsResults = [];
$eventsResults = [];
$totalNews = 0;
$totalEvents = 0;
$totalNewsPages = 0;
$totalEventsPages = 0;

// Lakukan pencarian jika ada query
if (!empty($searchQuery)) {
    // Pencarian Berita
    if ($searchType === 'all' || $searchType === 'news') {
        $newsSql = "SELECT n.news_id, n.title, n.content, n.category, n.image, n.views, n.created_at,
                           u.full_name as author_name
                    FROM news n
                    LEFT JOIN users u ON n.author_id = u.user_id
                    WHERE n.status = 'published'";
        
        $newsParams = [];
        
        // Search query
        $newsSql .= " AND (n.title LIKE :query_title OR n.content LIKE :query_content)";
        $newsParams[':query_title'] = '%' . $searchQuery . '%';
        $newsParams[':query_content'] = '%' . $searchQuery . '%';
        
        // Filter kategori
        if (!empty($category) && isset(NEWS_CATEGORIES[$category])) {
            $newsSql .= " AND n.category = :category";
            $newsParams[':category'] = $category;
        }
        
        // Filter tanggal
        if (!empty($dateFrom)) {
            $newsSql .= " AND DATE(n.created_at) >= :date_from";
            $newsParams[':date_from'] = $dateFrom;
        }
        
        if (!empty($dateTo)) {
            $newsSql .= " AND DATE(n.created_at) <= :date_to";
            $newsParams[':date_to'] = $dateTo;
        }
        
        // Hitung total
        $countNewsSql = str_replace(
            "SELECT n.news_id, n.title, n.content, n.category, n.image, n.views, n.created_at, u.full_name as author_name",
            "SELECT COUNT(*) as total",
            $newsSql
        );
        $countNewsResult = $db->fetchOne($countNewsSql, $newsParams);
        $totalNews = $countNewsResult ? (int) $countNewsResult['total'] : 0;
        $totalNewsPages = getTotalPages($totalNews, $perPage);
        
        // Ambil data dengan pagination
        $newsSql .= " ORDER BY n.views DESC, n.created_at DESC LIMIT :limit OFFSET :offset";
        $newsParams[':limit'] = $perPage;
        $newsParams[':offset'] = getPaginationOffset($page, $perPage);
        
        $newsResults = $db->fetchAll($newsSql, $newsParams);
    }
    
    // Pencarian Event
    if ($searchType === 'all' || $searchType === 'events') {
        $eventsSql = "SELECT e.event_id, e.title, e.description, e.location, e.event_date, 
                             e.registration_deadline, e.max_participants, e.fee, e.image, e.status,
                             u.full_name as organizer_name
                      FROM events e
                      LEFT JOIN users u ON e.organizer_id = u.user_id
                      WHERE 1=1";
        
        $eventsParams = [];
        
        // Search query
        $eventsSql .= " AND (e.title LIKE :query_title OR e.description LIKE :query_description OR e.location LIKE :query_location)";
        $eventsParams[':query_title'] = '%' . $searchQuery . '%';
        $eventsParams[':query_description'] = '%' . $searchQuery . '%';
        $eventsParams[':query_location'] = '%' . $searchQuery . '%';
        
        // Filter tanggal event
        if (!empty($dateFrom)) {
            $eventsSql .= " AND DATE(e.event_date) >= :date_from";
            $eventsParams[':date_from'] = $dateFrom;
        }
        
        if (!empty($dateTo)) {
            $eventsSql .= " AND DATE(e.event_date) <= :date_to";
            $eventsParams[':date_to'] = $dateTo;
        }
        
        // Hitung total
        $countEventsSql = str_replace(
            "SELECT e.event_id, e.title, e.description, e.location, e.event_date, e.registration_deadline, e.max_participants, e.fee, e.image, e.status, u.full_name as organizer_name",
            "SELECT COUNT(*) as total",
            $eventsSql
        );
        $countEventsResult = $db->fetchOne($countEventsSql, $eventsParams);
        $totalEvents = $countEventsResult ? (int) $countEventsResult['total'] : 0;
        $totalEventsPages = getTotalPages($totalEvents, $perPage);
        
        // Ambil data dengan pagination
        $eventsSql .= " ORDER BY e.event_date ASC LIMIT :limit OFFSET :offset";
        $eventsParams[':limit'] = $perPage;
        $eventsParams[':offset'] = getPaginationOffset($page, $perPage);
        
        $eventsResults = $db->fetchAll($eventsSql, $eventsParams);
    }
}

// Fungsi untuk highlight kata yang dicari
function highlightText($text, $query) {
    if (empty($query)) {
        return htmlspecialchars($text);
    }
    $highlighted = preg_replace('/(' . preg_quote($query, '/') . ')/i', '<mark style="background: var(--color-primary-light); padding: 0 2px;">$1</mark>', $text);
    return $highlighted;
}

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0;">
    <!-- Header -->
    <div style="margin-bottom: 32px;">
        <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">
            Pencarian
        </h1>
        <p style="color: var(--color-text-secondary); font-size: 14px;">
            Cari berita dan event di Ruang Unila
        </p>
    </div>

    <!-- Search Form -->
    <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm); margin-bottom: 32px;">
        <form method="GET" action="">
            <!-- Main Search -->
            <div style="display: flex; gap: 16px; margin-bottom: 16px;">
                <div style="flex: 1; position: relative;">
                    <input type="text" 
                           name="q" 
                           value="<?= htmlspecialchars($searchQuery) ?>"
                           placeholder="Cari berita atau event..."
                           class="form-input"
                           style="padding-left: 48px; font-size: 16px;"
                           autofocus>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="2" 
                         style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%);">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="M21 21l-4.35-4.35"/>
                    </svg>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 12px 32px;">
                    Cari
                </button>
            </div>
            
            <!-- Advanced Filters -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
                <!-- Search Type -->
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Tipe</label>
                    <select name="type" class="form-input">
                        <option value="all" <?= $searchType === 'all' ? 'selected' : '' ?>>Semua</option>
                        <option value="news" <?= $searchType === 'news' ? 'selected' : '' ?>>Berita</option>
                        <option value="events" <?= $searchType === 'events' ? 'selected' : '' ?>>Event</option>
                    </select>
                </div>
                
                <!-- Category -->
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Kategori</label>
                    <select name="category" class="form-input">
                        <option value="">Semua Kategori</option>
                        <?php foreach (NEWS_CATEGORIES as $slug => $name): ?>
                            <option value="<?= $slug ?>" <?= $category === $slug ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Date From -->
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Dari Tanggal</label>
                    <input type="date" name="date_from" value="<?= $dateFrom ?>" class="form-input">
                </div>
                
                <!-- Date To -->
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="<?= $dateTo ?>" class="form-input">
                </div>
            </div>
        </form>
    </div>

    <!-- Results -->
    <?php if (!empty($searchQuery)): ?>
        <div style="margin-bottom: 24px;">
            <p style="color: var(--color-text-secondary); font-size: 14px;">
                Hasil pencarian untuk "<strong><?= htmlspecialchars($searchQuery) ?></strong>"
                <?php if ($searchType === 'all'): ?>
                    - <?= $totalNews ?> berita, <?= $totalEvents ?> event ditemukan
                <?php elseif ($searchType === 'news'): ?>
                    - <?= $totalNews ?> berita ditemukan
                <?php else: ?>
                    - <?= $totalEvents ?> event ditemukan
                <?php endif; ?>
            </p>
        </div>

        <!-- News Results -->
        <?php if (($searchType === 'all' || $searchType === 'news') && count($newsResults) > 0): ?>
            <div style="margin-bottom: 48px;">
                <h2 style="font-family: var(--font-heading); font-size: 24px; margin-bottom: 24px;">
                    Berita (<?= $totalNews ?>)
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
                    <?php foreach ($newsResults as $newsItem): ?>
                        <article class="news-card">
                            <div class="news-card-image" style="background: url('<?= $newsItem['image'] ? url('assets/images/uploads/news/' . $newsItem['image']) : url('assets/images/placeholder.jpg') ?>') center/cover;">
                                <span class="news-card-category"><?= NEWS_CATEGORIES[$newsItem['category']] ?? $newsItem['category'] ?></span>
                            </div>
                            <div class="news-card-content">
                                <h3 class="news-card-title">
                                    <a href="<?= url('modules/news/detail.php?id=' . $newsItem['news_id']) ?>">
                                        <?= highlightText($newsItem['title'], $searchQuery) ?>
                                    </a>
                                </h3>
                                <p class="news-card-excerpt">
                                    <?= highlightText(truncateText(strip_tags($newsItem['content']), 120), $searchQuery) ?>
                                </p>
                                <div class="news-card-meta">
                                    <span class="news-card-meta-item">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M12 6v6l4 2"/>
                                        </svg>
                                        <?= formatDate($newsItem['created_at']) ?>
                                    </span>
                                    <span class="news-card-meta-item">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <?= formatNumber($newsItem['views']) ?> views
                                    </span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination News -->
                <?php if ($totalNewsPages > 1): ?>
                    <div class="pagination" style="margin-top: 24px;">
                        <?php if ($page > 1): ?>
                            <?php $prevPage = $page - 1; ?>
                            <?php $params = http_build_query(['q' => $searchQuery, 'type' => $searchType, 'category' => $category, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $prevPage]); ?>
                            <a href="<?= url("modules/search/index.php?$params") ?>" class="pagination-link">&laquo;</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalNewsPages; $i++): ?>
                            <?php $params = http_build_query(['q' => $searchQuery, 'type' => $searchType, 'category' => $category, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $i]); ?>
                            <a href="<?= url("modules/search/index.php?$params") ?>" class="pagination-link <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalNewsPages): ?>
                            <?php $nextPage = $page + 1; ?>
                            <?php $params = http_build_query(['q' => $searchQuery, 'type' => $searchType, 'category' => $category, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $nextPage]); ?>
                            <a href="<?= url("modules/search/index.php?$params") ?>" class="pagination-link">&raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Events Results -->
        <?php if (($searchType === 'all' || $searchType === 'events') && count($eventsResults) > 0): ?>
            <div>
                <h2 style="font-family: var(--font-heading); font-size: 24px; margin-bottom: 24px;">
                    Event (<?= $totalEvents ?>)
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
                    <?php foreach ($eventsResults as $eventItem): ?>
                        <div class="event-card">
                            <div class="event-card-image" style="background: url('<?= $eventItem['image'] ? url('assets/images/uploads/events/' . $eventItem['image']) : url('assets/images/placeholder.jpg') ?>') center/cover;">
                                <div class="event-card-date">
                                    <span class="event-card-date-month"><?= date('M', strtotime($eventItem['event_date'])) ?></span>
                                    <span class="event-card-date-day"><?= date('d', strtotime($eventItem['event_date'])) ?></span>
                                </div>
                            </div>
                            <div class="event-card-content">
                                <h3 class="event-card-title"><?= highlightText($eventItem['title'], $searchQuery) ?></h3>
                                <div class="event-card-location">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <?= highlightText($eventItem['location'], $searchQuery) ?>
                                </div>
                                <div style="margin-top: 8px; font-size: 12px; color: var(--color-text-muted);">
                                    <?= $eventItem['fee'] > 0 ? 'Rp ' . formatNumber($eventItem['fee']) : 'Gratis' ?>
                                    &bull; <?= $eventItem['max_participants'] ?> peserta
                                </div>
                                <a href="<?= url('modules/events/detail.php?id=' . $eventItem['event_id']) ?>" class="event-card-btn">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination Events -->
                <?php if ($totalEventsPages > 1): ?>
                    <div class="pagination" style="margin-top: 24px;">
                        <?php if ($page > 1): ?>
                            <?php $prevPage = $page - 1; ?>
                            <?php $params = http_build_query(['q' => $searchQuery, 'type' => $searchType, 'category' => $category, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $prevPage]); ?>
                            <a href="<?= url("modules/search/index.php?$params") ?>" class="pagination-link">&laquo;</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalEventsPages; $i++): ?>
                            <?php $params = http_build_query(['q' => $searchQuery, 'type' => $searchType, 'category' => $category, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $i]); ?>
                            <a href="<?= url("modules/search/index.php?$params") ?>" class="pagination-link <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalEventsPages): ?>
                            <?php $nextPage = $page + 1; ?>
                            <?php $params = http_build_query(['q' => $searchQuery, 'type' => $searchType, 'category' => $category, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $nextPage]); ?>
                            <a href="<?= url("modules/search/index.php?$params") ?>" class="pagination-link">&raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- No Results -->
        <?php if (empty($newsResults) && empty($eventsResults)): ?>
            <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="1.5" style="margin-bottom: 16px;">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
                <h3 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 8px;">Tidak ada hasil</h3>
                <p style="color: var(--color-text-muted);">Coba gunakan kata kunci lain atau ubah filter pencarian</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Empty State -->
        <div style="text-align: center; padding: 80px 20px; background: white; border-radius: 12px;">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="1.5" style="margin-bottom: 24px;">
                <circle cx="11" cy="11" r="8"/>
                <path d="M21 21l-4.35-4.35"/>
            </svg>
            <h2 style="font-family: var(--font-heading); font-size: 24px; margin-bottom: 12px;">Cari Berita & Event</h2>
            <p style="color: var(--color-text-muted); max-width: 400px; margin: 0 auto;">
                Masukkan kata kunci untuk mencari berita dan event di Ruang Unila. Anda juga bisa menggunakan filter untuk hasil yang lebih spesifik.
            </p>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>