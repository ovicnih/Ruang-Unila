<?php
/**
 * Modul List News - Ruang Unila
 * 
 * Modul ini menampilkan daftar berita dengan filter kategori
 * dan pagination.
 * 
 * @package RuangUnila
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

// Include konfigurasi dan fungsi
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

// Ambil parameter
$category = isset($_GET['category']) ? cleanInput($_GET['category']) : null;
$page = getCurrentPage();
$perPage = ITEMS_PER_PAGE;

// Validasi kategori
if ($category && !isset(NEWS_CATEGORIES[$category])) {
    $category = null;
}

// Ambil data news
$result = $news->getAll($page, $perPage, $category, 'published');
$newsList = $result['news'];
$totalPages = $result['total_pages'];
$total = $result['total'];

// Ambil popular news untuk sidebar
$popularNews = $news->getPopular(5);

// Hitung jumlah berita per kategori
$categoryCounts = $news->countByCategory();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-container">
            <a href="<?= url() ?>" class="navbar-brand">Ruang Unila</a>
            <div class="navbar-nav">
                <a href="<?= url() ?>" class="nav-link">Beranda</a>
                <a href="<?= url('modules/news/list.php') ?>" class="nav-link active">Berita</a>
                <a href="<?= url('modules/events/list.php') ?>" class="nav-link">Event</a>
            </div>
            <div class="navbar-search">
                <input type="text" placeholder="Cari berita...">
                <svg class="navbar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
            </div>
            <div style="display: flex; align-items: center; gap: 16px;">
                <?php if ($session->isLoggedIn()): ?>
                    <?php if ($session->getUserRole() === ROLE_ADMIN): ?>
                        <a href="<?= url('modules/admin/dashboard.php') ?>" class="btn btn-primary btn-sm">Admin Dashboard</a>
                    <?php endif; ?>
                    <a href="<?= url('modules/profile/view.php') ?>" class="btn btn-secondary btn-sm">Profil</a>
                    <a href="<?= url('modules/auth/logout.php') ?>" class="btn btn-outline btn-sm">Logout</a>
                <?php else: ?>
                    <a href="<?= url('modules/auth/login.php') ?>" class="btn btn-secondary btn-sm">Masuk</a>
                    <a href="<?= url('modules/auth/register.php') ?>" class="btn btn-primary btn-sm">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div style="display: flex; gap: 32px;">
                <!-- Main News List -->
                <div style="flex: 1;">
                    <!-- Page Header -->
                    <div style="margin-bottom: 32px;">
                        <h1 style="font-family: var(--font-heading); font-size: 48px; letter-spacing: -1.2px; margin-bottom: 16px;">
                            Berita
                        </h1>
                        
                        <!-- Category Tabs -->
                        <div class="category-tabs">
                            <a href="<?= url('modules/news/list.php') ?>" 
                               class="category-tab <?= !$category ? 'active' : '' ?>">
                                Semua
                            </a>
                            <?php foreach (NEWS_CATEGORIES as $slug => $name): ?>
                                <a href="<?= url('modules/news/list.php?category=' . $slug) ?>" 
                                   class="category-tab <?= $category === $slug ? 'active' : '' ?>">
                                    <?= $name ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- News Grid -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px;">
                        <?php foreach ($newsList as $newsItem): ?>
                            <article class="news-card">
                                <div class="news-card-image" style="background: url('<?= $newsItem['image'] ? url('assets/images/uploads/news/' . $newsItem['image']) : url('assets/images/placeholder.jpg') ?>') center/cover;">
                                    <span class="news-card-category"><?= NEWS_CATEGORIES[$newsItem['category']] ?? $newsItem['category'] ?></span>
                                </div>
                                <div class="news-card-content">
                                    <h3 class="news-card-title">
                                        <a href="<?= url('modules/news/detail.php?id=' . $newsItem['news_id']) ?>">
                                            <?= htmlspecialchars($newsItem['title']) ?>
                                        </a>
                                    </h3>
                                    <p class="news-card-excerpt">
                                        <?= truncateText(strip_tags($newsItem['content']), 120) ?>
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

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="<?= url('modules/news/list.php?page=' . ($page - 1) . ($category ? '&category=' . $category : '')) ?>" 
                                   class="pagination-link">
                                    &laquo;
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="<?= url('modules/news/list.php?page=' . $i . ($category ? '&category=' . $category : '')) ?>" 
                                   class="pagination-link <?= $page === $i ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="<?= url('modules/news/list.php?page=' . ($page + 1) . ($category ? '&category=' . $category : '')) ?>" 
                                   class="pagination-link">
                                    &raquo;
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div style="width: 378px;">
                    <!-- Popular News -->
                    <div style="background: white; border-radius: 16px; padding: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); margin-bottom: 24px;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
                            <div style="width: 8px; height: 32px; background: linear-gradient(165.96deg, var(--color-primary) 0%, var(--color-primary-light) 100%); border-radius: 9999px;"></div>
                            <h4 style="font-family: var(--font-heading); font-size: 24px; font-weight: 700; color: var(--color-text-primary);">
                                Berita Populer
                            </h4>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 24px;">
                            <?php foreach ($popularNews as $index => $popular): ?>
                                <div style="display: flex; gap: 16px;">
                                    <span style="font-family: var(--font-heading); font-size: 30px; font-weight: 900; color: rgba(232, 189, 182, 0.4); width: 38px;">
                                        <?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?>
                                    </span>
                                    <div style="flex: 1;">
                                        <a href="<?= url('modules/news/detail.php?id=' . $popular['news_id']) ?>" 
                                           style="font-family: var(--font-primary); font-size: 14px; font-weight: 700; line-height: 1.25; color: var(--color-text-primary); text-decoration: none;">
                                            <?= htmlspecialchars($popular['title']) ?>
                                        </a>
                                        <div style="font-family: var(--font-primary); font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                                            <?= formatNumber($popular['views']) ?> views
                                        </div>
                                    </div>
                                </div>
                                <?php if ($index < count($popularNews) - 1): ?>
                                    <div style="height: 1px; background: rgba(232, 189, 182, 0.1);"></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Newsletter -->
                    <div style="background: linear-gradient(123.43deg, var(--color-primary) 0%, var(--color-primary-light) 100%); border-radius: 16px; padding: 32px;">
                        <h4 style="font-family: var(--font-primary); font-weight: 700; font-size: 20px; color: white; margin-bottom: 8px;">
                            Jangan Lewatkan Berita!
                        </h4>
                        <p style="font-family: var(--font-primary); font-size: 14px; color: rgba(255,255,255,0.8); margin-bottom: 16px;">
                            Dapatkan rangkuman berita terpenting Unila langsung ke email kamu setiap hari Senin.
                        </p>
                        <div style="display: flex; gap: 8px;">
                            <input type="email" placeholder="Email kamu" style="flex: 1; padding: 8px 16px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white; font-size: 14px;">
                            <button style="padding: 8px 0; background: white; border-radius: 8px; font-weight: 700; font-size: 14px; color: var(--color-primary); border: none; cursor: pointer; width: 100%;">
                                Berlangganan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">Ruang Unila</div>
            <div class="footer-copyright">© 2026 Ruang Unila. Universitas Lampung.</div>
            <div class="footer-links">
                <a href="#" class="footer-link">Tentang Kami</a>
                <a href="#" class="footer-link">Kontak</a>
                <a href="#" class="footer-link">FAQ</a>
                <a href="#" class="footer-link">Privasi</a>
            </div>
        </div>
    </footer>

    <script>
        // Lazy loading untuk gambar
        document.querySelectorAll('.news-card-image').forEach(img => {
            if (img.dataset.src) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            img.style.backgroundImage = `url(${img.dataset.src})`;
                            observer.unobserve(img);
                        }
                    });
                });
                observer.observe(img);
            }
        });
    </script>
</body>
</html>