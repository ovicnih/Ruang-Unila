<?php
/**
 * Modul Detail News - Ruang Unila
 * 
 * Modul ini menampilkan detail berita dengan view counter
 * dan related news.
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
$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validasi ID
if ($newsId <= 0) {
    header('Location: ' . url('modules/news/list.php'));
    exit;
}

// Ambil data news
$newsItem = $news->getById($newsId);

// Cek apakah news ada
if (!$newsItem) {
    header('Location: ' . url('modules/news/list.php'));
    exit;
}

// Cek apakah news sudah published
if ($newsItem['status'] !== 'published') {
    // Hanya admin/author yang bisa lihat draft/pending
    if (!$session->isLoggedIn() || 
        $session->getUserRole() !== ROLE_ADMIN && $session->getUserId() !== $newsItem['author_id']) {
        header('Location: ' . url('modules/news/list.php'));
        exit;
    }
}

// Increment views
$news->incrementViews($newsId);

// Ambil related news (same category, exclude current)
$relatedNews = $news->getRelated($newsId, $newsItem['category'], 3);

// Ambil popular news untuk sidebar
$popularNews = $news->getPopular(5);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($newsItem['title']) ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
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
            <!-- Breadcrumbs -->
            <div style="display: flex; align-items: center; margin-bottom: 24px;">
                <a href="<?= url('modules/news/list.php') ?>" style="display: flex; align-items: center; gap: 4px; color: var(--color-text-secondary); text-decoration: none;">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                    Kembali ke Berita
                </a>
            </div>

            <div style="display: flex; gap: 32px;">
                <!-- Main Article -->
                <article style="flex: 1;">
                    <!-- Category Badge -->
                    <div style="margin-bottom: 16px;">
                        <span style="display: inline-flex; align-items: center; padding: 4px 12px; background: rgba(158, 0, 0, 0.1); border-radius: 9999px; font-family: var(--font-primary); font-weight: 700; font-size: 12px; letter-spacing: 0.6px; text-transform: uppercase; color: var(--color-primary);">
                            <?= NEWS_CATEGORIES[$newsItem['category']] ?? $newsItem['category'] ?>
                        </span>
                    </div>

                    <!-- Title -->
                    <h1 style="font-family: var(--font-heading); font-size: 48px; line-height: 1.1; letter-spacing: -1.5px; margin-bottom: 24px; color: var(--color-text-primary);">
                        <?= htmlspecialchars($newsItem['title']) ?>
                    </h1>

                    <!-- Meta -->
                    <div style="display: flex; align-items: center; gap: 24px; padding-bottom: 24px; border-top: 1px solid rgba(232, 189, 182, 0.15);">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 48px; height: 48px; background: var(--color-border-light); border-radius: 9999px; background-image: url('<?= $newsItem['author_photo'] ? url('assets/images/uploads/profile/' . $newsItem['author_photo']) : '' ?>'); background-size: cover;"></div>
                            <div>
                                <div style="font-family: var(--font-primary); font-weight: 700; font-size: 14px; color: var(--color-text-primary);">
                                    <?= htmlspecialchars($newsItem['author_name'] ?? 'Admin') ?>
                                </div>
                                <div style="font-family: var(--font-primary); font-size: 12px; color: var(--color-text-secondary);">
                                    Penulis
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <span style="font-family: var(--font-primary); font-weight: 700; font-size: 12px; letter-spacing: 1.2px; text-transform: uppercase; color: var(--color-text-secondary);">
                                <?= formatDate($newsItem['created_at']) ?>
                            </span>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <span style="font-family: var(--font-primary); font-weight: 700; font-size: 12px; letter-spacing: 1.2px; text-transform: uppercase; color: var(--color-text-secondary);">
                                <?= formatNumber($newsItem['views']) ?> views
                            </span>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <span style="font-family: var(--font-primary); font-weight: 700; font-size: 12px; letter-spacing: 1.2px; text-transform: uppercase; color: var(--color-text-secondary);">
                                <?= calculateReadTime($newsItem['content']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <?php if ($newsItem['image']): ?>
                        <div style="margin-bottom: 32px; border-radius: 12px; overflow: hidden;">
                            <img src="<?= url('assets/images/uploads/news/' . $newsItem['image']) ?>" 
                                 alt="<?= htmlspecialchars($newsItem['title']) ?>"
                                 style="width: 100%; height: 453px; object-fit: cover;">
                        </div>
                    <?php endif; ?>

                    <!-- Content -->
                    <div style="margin-bottom: 32px;">
                        <div style="font-family: var(--font-body); font-size: 16px; line-height: 1.62; color: var(--color-text-primary);">
                            <?= nl2br(htmlspecialchars($newsItem['content'])) ?>
                        </div>
                    </div>

                    <!-- Interaction Bar -->
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 32px 0; border-width: 1px 0; border-style: solid; border-color: rgba(232, 189, 182, 0.2);">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <button class="btn btn-primary" style="display: flex; align-items: center; gap: 8px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
                                </svg>
                                Suka
                            </button>
                            <button class="btn btn-outline" style="display: flex; align-items: center; gap: 8px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                                Komentar
                            </button>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-family: var(--font-primary); font-weight: 700; font-size: 12px; letter-spacing: 1.2px; text-transform: uppercase; color: var(--color-text-secondary);">
                                Tags:
                            </span>
                            <span style="display: inline-flex; padding: 4px 12px; background: var(--color-border-light); border-radius: 9999px; font-family: var(--font-primary); font-size: 12px; font-weight: 500; color: var(--color-text-primary);">
                                <?= NEWS_CATEGORIES[$newsItem['category']] ?? $newsItem['category'] ?>
                            </span>
                        </div>
                    </div>
                </article>

                <!-- Sidebar -->
                <div style="width: 378px;">
                    <!-- Related News -->
                    <?php if (count($relatedNews) > 0): ?>
                        <div style="background: white; border-radius: 16px; padding: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); margin-bottom: 24px;">
                            <h4 style="font-family: var(--font-heading); font-weight: 700; font-size: 24px; color: var(--color-text-primary); margin-bottom: 24px; padding-bottom: 8px; border-bottom: 2px solid var(--color-primary);">
                                Berita Terkait
                            </h4>
                            
                            <div style="display: flex; flex-direction: column; gap: 24px;">
                                <?php foreach ($relatedNews as $related): ?>
                                    <div style="display: flex; gap: 16px;">
                                        <div style="width: 96px; height: 96px; background: url('<?= $related['image'] ? url('assets/images/uploads/news/' . $related['image']) : url('assets/images/placeholder.jpg') ?>') center/cover; border-radius: 8px;"></div>
                                        <div style="flex: 1; display: flex; flex-direction: column; gap: 4px;">
                                            <span style="font-family: var(--font-primary); font-weight: 700; font-size: 10px; letter-spacing: 0.5px; text-transform: uppercase; color: var(--color-primary);">
                                                <?= NEWS_CATEGORIES[$related['category']] ?? $related['category'] ?>
                                            </span>
                                            <a href="<?= url('modules/news/detail.php?id=' . $related['news_id']) ?>" 
                                               style="font-family: var(--font-primary); font-weight: 700; font-size: 14px; line-height: 1.43; color: var(--color-text-primary); text-decoration: none;">
                                                <?= htmlspecialchars($related['title']) ?>
                                            </a>
                                            <div style="font-family: var(--font-primary); font-size: 12px; color: var(--color-text-muted);">
                                                <?= timeAgo($related['created_at']) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Newsletter -->
                    <div style="background: linear-gradient(123.43deg, var(--color-primary) 0%, var(--color-primary-light) 100%); border-radius: 16px; padding: 32px;">
                        <h4 style="font-family: var(--font-primary); font-weight: 700; font-size: 20px; color: white; margin-bottom: 8px;">
                            Jangan Lewatkan Berita!
                        </h4>
                        <p style="font-family: var(--font-primary); font-size: 14px; color: rgba(255,255,255,0.8); margin-bottom: 16px;">
                            Dapatkan rangkuman berita terpenting Unila langsung ke email kamu setiap hari Senin.
                        </p>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <input type="email" placeholder="Email kamu" style="padding: 8px 16px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white; font-size: 14px;">
                            <button style="padding: 8px 0; background: white; border-radius: 8px; font-weight: 700; font-size: 14px; color: var(--color-primary); border: none; cursor: pointer;">
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
</body>
</html>