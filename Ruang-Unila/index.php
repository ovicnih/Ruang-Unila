<?php
/**
 * Entry Point - Ruang Unila
 * 
 * Halaman utama sistem web berita kampus
 * dengan routing dan navigasi yang proper.
 * 
 * @package RuangUnila
 * @version 1.1.0
 */

// Definisi root untuk akses (hanya jika belum didefinisikan)
if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
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
$news = new News();
$event = new Event();

// Set page title
$pageTitle = 'Beranda';

// Ambil data untuk homepage
$popularNews = $news->getPopular(5);
$latestNews = $news->getLatest(6);
$upcomingEvents = $event->getUpcoming(3);
$categoryCounts = $news->countByCategory();
$featuredNews = $news->getPopular(1);
$featured = $featuredNews[0] ?? null;

// Include header
include APP_ROOT . '/includes/header.php';
?>

<!-- Berita Paling Populer -->
<?php if ($featured): ?>
<section class="section-featured">
    <div class="container">
        <div class="featured-grid">
            <!-- News Image -->
            <div class="featured-image-wrapper">
                <img src="<?= $featured['image'] ? url('assets/images/uploads/news/' . $featured['image']) : url('assets/images/placeholder.jpg') ?>" 
                     alt="<?= htmlspecialchars($featured['title']) ?>"
                     class="featured-image">
            </div>
            
            <!-- News Content -->
            <div class="featured-content shadow">
                <h2 class="featured-title">
                    <?= htmlspecialchars($featured['title']) ?>
                </h2>
                <p class="featured-text">
                    <?= truncateText(strip_tags($featured['content'] ?? $featured['excerpt'] ?? ''), 250) ?>
                </p>
                <div class="featured-meta">
                    <span>📅 <?= formatDate($featured['created_at']) ?></span>
                    <span>👁️ <?= formatNumber($featured['views']) ?> views</span>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-container">
            <h1 class="hero-title">
                Selamat Datang di Ruang Unila
            </h1>
            <p class="hero-subtitle">
                Platform informasi terpercaya untuk civitas akademika Universitas Lampung
            </p>
            <div class="hero-actions">
                <a href="<?= url('modules/news/list.php') ?>" class="btn hero-btn-primary">
                    Jelajahi Berita
                </a>
                <a href="<?= url('modules/events/list.php') ?>" class="btn hero-btn-outline">
                    Lihat Event
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Kategori Section -->
<section class="section-category">
    <div class="container">
        <h2 class="section-title mb-4">
            Jelajahi Kategori
        </h2>
        <div class="category-grid">
            <?php foreach (NEWS_CATEGORIES as $slug => $name): ?>
                <a href="<?= url('modules/news/list.php?category=' . $slug) ?>" class="category-card shadow-sm">
                    <div class="category-icon-wrapper">
                        <span class="category-icon"><?= $name[0] ?></span>
                    </div>
                    <span class="category-name"><?= $name ?></span>
                    <span class="category-count"><?= $categoryCounts[$slug] ?? 0 ?> berita</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Berita Terbaru -->
<section class="section-news">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">
                Berita Terbaru
            </h2>
            <a href="<?= url('modules/news/list.php') ?>" class="btn btn-outline">
                Lihat Semua
            </a>
        </div>
        
        <div class="news-grid">
            <?php foreach ($latestNews as $newsItem): ?>
                <article class="news-card shadow-sm">
                    <div class="news-card-image" style="background-image: <?= $newsItem['image'] ? "url('" . url('assets/images/uploads/news/' . $newsItem['image']) . "')" : "linear-gradient(135deg, #16a34a 0%, #22c55e 100%)" ?>; background-size: cover; background-position: center;">
                        <span class="news-card-category"><?= NEWS_CATEGORIES[$newsItem['category']] ?? $newsItem['category'] ?></span>
                    </div>
                    <div class="news-card-content">
                        <h3 class="news-card-title">
                            <a href="<?= url('modules/news/detail.php?id=' . $newsItem['news_id']) ?>">
                                <?= htmlspecialchars($newsItem['title']) ?>
                            </a>
                        </h3>
                        <p class="news-card-excerpt">
                            <?= truncateText(strip_tags($newsItem['content'] ?? $newsItem['excerpt'] ?? ''), 100) ?>
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
                                <?= formatNumber($newsItem['views']) ?>
                            </span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Event Mendatang -->
<section class="section-event">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">
                Event Mendatang
            </h2>
            <a href="<?= url('modules/events/list.php') ?>" class="btn btn-outline">
                Lihat Semua
            </a>
        </div>
        
        <?php if (count($upcomingEvents) > 0): ?>
            <div class="event-grid">
                <?php foreach ($upcomingEvents as $eventItem): ?>
                    <div class="event-card shadow-sm">
                        <div class="event-card-image" style="background-image: <?= $eventItem['image'] ? "url('" . url('assets/images/uploads/events/' . $eventItem['image']) . "')" : "linear-gradient(135deg, #16a34a 0%, #22c55e 100%)" ?>; background-size: cover; background-position: center;">
                            <div class="event-card-date">
                                <span class="event-card-date-month"><?= date('M', strtotime($eventItem['event_date'])) ?></span>
                                <span class="event-card-date-day"><?= date('d', strtotime($eventItem['event_date'])) ?></span>
                            </div>
                        </div>
                        <div class="event-card-content">
                            <h3 class="event-card-title"><?= htmlspecialchars($eventItem['title']) ?></h3>
                            <div class="event-card-location">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <?= htmlspecialchars($eventItem['location']) ?>
                            </div>
                            <a href="<?= url('modules/events/detail.php?id=' . $eventItem['event_id']) ?>" class="event-card-btn">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="event-empty shadow-sm">
                <p class="event-empty-text">Tidak ada event mendatang saat ini.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>