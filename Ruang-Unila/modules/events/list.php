<?php
/**
 * Modul Daftar Event - Ruang Unila
 * 
 * Menampilkan daftar event dengan filter dan pagination.
 * Mendukung filter tanggal dan menampilkan sisa kuota.
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

// Require login
requireLogin();

// Inisialisasi
$event = new Event();
$news = new News();
$pageTitle = 'Daftar Event';

// Parameter pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9;

// Parameter filter
$filterDate = isset($_GET['date']) ? sanitize($_GET['date']) : null;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : null;

// Ambil data event
$eventsData = $event->getAll($page, $perPage, 'upcoming');
$events = $eventsData['events'];
$totalPages = $eventsData['total_pages'];

// Ambil berita paling populer
$featuredNews = $news->getPopular(1);
$featured = $featuredNews[0] ?? null;

// Hitung countdown untuk setiap event
$now = new DateTime();
foreach ($events as &$eventItem) {
    $eventDate = new DateTime($eventItem['event_date']);
    $deadline = new DateTime($eventItem['registration_deadline']);
    
    // Hitung sisa kuota
    $eventItem['remaining_quota'] = $event->getRemainingQuota($eventItem['event_id']);
    
    // Hitung countdown ke deadline
    $diff = $now->diff($deadline);
    if ($deadline < $now) {
        $eventItem['countdown'] = 'Pendaftaran ditutup';
        $eventItem['countdown_class'] = 'closed';
    } elseif ($diff->days == 0) {
        $eventItem['countdown'] = 'Hari ini';
        $eventItem['countdown_class'] = 'urgent';
    } elseif ($diff->days == 1) {
        $eventItem['countdown'] = 'Besok';
        $eventItem['countdown_class'] = 'urgent';
    } else {
        $eventItem['countdown'] = $diff->days . ' hari lagi';
        $eventItem['countdown_class'] = $diff->days <= 3 ? 'warning' : 'normal';
    }
    
    // Format tanggal event
    $eventItem['formatted_date'] = formatDate($eventItem['event_date'], 'd M Y');
    $eventItem['formatted_time'] = date('H:i', strtotime($eventItem['event_date']));
}
unset($eventItem);

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">
                Daftar Event
            </h1>
            <p style="color: var(--color-text-muted);">
                Temukan dan daftar event menarik di Universitas Lampung
            </p>
        </div>
        
        <?php if (hasRole('organisasi') || hasRole('admin')): ?>
            <a href="<?= url('modules/events/create.php') ?>" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Buat Event
            </a>
        <?php endif; ?>
    </div>
    
<!-- Berita Paling Populer -->
<?php if ($featured): ?>
<section style="margin-bottom: 24px;">
    <div class="container">
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- News Image (full-width) -->
            <div style="border-radius: 12px; overflow: hidden; height: 300px; margin: 0 -24px;">
                <img src="<?= $featured['image'] ? url('assets/images/uploads/news/' . $featured['image']) : url('assets/images/placeholder.jpg') ?>" 
                     alt="<?= htmlspecialchars($featured['title']) ?>"
                     style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            
            <!-- News Content (full-width) -->
            <div style="padding: 24px; background: white; border-radius: 12px;">
                <h2 style="font-family: var(--font-heading); font-size: 28px; margin-bottom: 16px;">
                    <?= htmlspecialchars($featured['title']) ?>
                </h2>
                <p style="color: var(--color-text-secondary); line-height: 1.8;">
                    <?= truncateText(strip_tags($featured['content'] ?? $featured['excerpt'] ?? ''), 200) ?>
                </p>
                <div style="margin-top: 16px; color: var(--color-text-muted); font-size: 12px;">
                    <span>📅 <?= formatDate($featured['created_at']) ?></span>
                    <span style="margin-left: 16px;">👁️ <?= formatNumber($featured['views']) ?> views</span>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Filter Section -->
    <div style="background: white; padding: 20px; border-radius: 12px; margin-bottom: 24px; box-shadow: var(--shadow-sm);">
        <form method="GET" action="<?= url('modules/events/list.php') ?>" style="display: flex; gap: 16px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Cari Event</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" 
                       placeholder="Nama event..." class="form-input">
            </div>
            
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Tanggal</label>
                <input type="date" name="date" value="<?= htmlspecialchars($filterDate ?? '') ?>" class="form-input">
            </div>
            
            <div style="display: flex; align-items: flex-end; gap: 8px;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= url('modules/events/list.php') ?>" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </div>
    
    <!-- Events Grid -->
    <?php if (count($events) > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
            <?php foreach ($events as $eventItem): ?>
                <div class="event-card" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-sm);">
                    <!-- Event Image -->
                    <div style="height: 200px; background-image: <?= $eventItem['image'] ? "url('" . url('assets/images/uploads/events/' . $eventItem['image']) . "')" : "linear-gradient(135deg, #16a34a 0%, #22c55e 100%)" ?>; background-size: cover; background-position: center; position: relative;">
                        <div style="position: absolute; top: 12px; right: 12px; background: white; padding: 8px 12px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 12px; color: var(--color-text-muted);"><?= date('M', strtotime($eventItem['event_date'])) ?></div>
                            <div style="font-size: 20px; font-weight: 700; color: var(--color-primary);"><?= date('d', strtotime($eventItem['event_date'])) ?></div>
                        </div>
                        
                        <!-- Countdown Badge -->
                        <div style="position: absolute; bottom: 12px; left: 12px; background: <?= $eventItem['countdown_class'] === 'urgent' ? 'var(--color-danger)' : ($eventItem['countdown_class'] === 'warning' ? 'var(--color-warning)' : 'var(--color-success)') ?>; color: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                            <?= $eventItem['countdown'] ?>
                        </div>
                    </div>
                    
                    <!-- Event Content -->
                    <div style="padding: 20px;">
                        <h3 style="font-size: 18px; margin-bottom: 8px; font-family: var(--font-heading);">
                            <a href="<?= url('modules/events/detail.php?id=' . $eventItem['event_id']) ?>" style="color: var(--color-text-primary); text-decoration: none;">
                                <?= htmlspecialchars($eventItem['title']) ?>
                            </a>
                        </h3>
                        
                        <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px;">
                            <div style="display: flex; align-items: center; gap: 8px; color: var(--color-text-muted); font-size: 14px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <?= $eventItem['formatted_date'] ?>, <?= $eventItem['formatted_time'] ?>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 8px; color: var(--color-text-muted); font-size: 14px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <?= htmlspecialchars($eventItem['location']) ?>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 8px; color: var(--color-text-muted); font-size: 14px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <?= $eventItem['remaining_quota'] ?> / <?= $eventItem['max_participants'] ?> kuota tersisa
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700; color: var(--color-primary);">
                                <?= $eventItem['fee'] > 0 ? 'Rp ' . formatNumber($eventItem['fee']) : 'Gratis' ?>
                            </span>
                            
                            <a href="<?= url('modules/events/detail.php?id=' . $eventItem['event_id']) ?>" class="btn btn-sm btn-outline">
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
                        <a href="<?= url('modules/events/list.php?page=' . ($page - 1)) ?>" class="btn btn-outline btn-sm">Sebelumnya</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="<?= url('modules/events/list.php?page=' . $i) ?>" 
                           class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= url('modules/events/list.php?page=' . ($page + 1)) ?>" class="btn btn-outline btn-sm">Selanjutnya</a>
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
            <p style="color: var(--color-text-muted);">Belum ada event yang tersedia saat ini.</p>
            
            <?php if (hasRole('organisasi') || hasRole('admin')): ?>
                <a href="<?= url('modules/events/create.php') ?>" class="btn btn-primary" style="margin-top: 16px;">
                    Buat Event Pertama
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.event-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.event-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}
</style>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>