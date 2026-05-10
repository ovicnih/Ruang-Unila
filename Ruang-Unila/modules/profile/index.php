<?php
/**
 * Modul Lihat Profil - Ruang Unila
 * 
 * Menampilkan profil user dengan statistik.
 * 
 * @package RuangUnila
 * @subpackage Modules/Profile
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
$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Ambil data user
$sql = "SELECT user_id, username, email, full_name, role, profile_photo, phone, bio, created_at
        FROM users 
        WHERE user_id = :user_id 
        LIMIT 1";

$user = $db->fetchOne($sql, [':user_id' => $userId]);

if (!$user) {
    setFlashMessage('error', 'Data user tidak ditemukan.');
    redirect('index.php');
}

// Hitung statistik berdasarkan role
$stats = [];

if ($user['role'] === 'organisasi' || $user['role'] === 'admin') {
    // Hitung berita yang dibuat
    $newsSql = "SELECT COUNT(*) as total FROM news WHERE author_id = :user_id";
    $newsResult = $db->fetchOne($newsSql, [':user_id' => $userId]);
    $stats['news_count'] = $newsResult ? (int) $newsResult['total'] : 0;
    
    // Hitung berita published
    $publishedSql = "SELECT COUNT(*) as total FROM news WHERE author_id = :user_id AND status = 'published'";
    $publishedResult = $db->fetchOne($publishedSql, [':user_id' => $userId]);
    $stats['news_published'] = $publishedResult ? (int) $publishedResult['total'] : 0;
    
    // Hitung event yang dibuat
    $eventSql = "SELECT COUNT(*) as total FROM events WHERE organizer_id = :user_id";
    $eventResult = $db->fetchOne($eventSql, [':user_id' => $userId]);
    $stats['event_count'] = $eventResult ? (int) $eventResult['total'] : 0;
}

if ($user['role'] === 'mahasiswa') {
    // Hitung event yang diikuti
    $regSql = "SELECT COUNT(*) as total FROM event_registrations WHERE user_id = :user_id";
    $regResult = $db->fetchOne($regSql, [':user_id' => $userId]);
    $stats['events_joined'] = $regResult ? (int) $regResult['total'] : 0;
    
    // Hitung event terkonfirmasi
    $confirmedSql = "SELECT COUNT(*) as total FROM event_registrations WHERE user_id = :user_id AND status = 'confirmed'";
    $confirmedResult = $db->fetchOne($confirmedSql, [':user_id' => $userId]);
    $stats['events_confirmed'] = $confirmedResult ? (int) $confirmedResult['total'] : 0;
}

// Set page title
$pageTitle = 'Profil Saya';

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0; max-width: 800px; margin: 0 auto;">
    <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 32px;">
        Profil Saya
    </h1>
    
    <!-- Profile Card -->
    <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-sm); margin-bottom: 24px;">
        <!-- Cover Photo -->
        <div style="height: 150px; background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);"></div>
        
        <!-- Profile Info -->
        <div style="padding: 24px; margin-top: -60px;">
            <div style="display: flex; align-items: flex-end; gap: 24px; margin-bottom: 24px;">
                <!-- Profile Photo -->
                <div style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 4px solid white; box-shadow: var(--shadow-md); background: var(--color-bg);">
                    <?php if ($user['profile_photo']): ?>
                        <img src="<?= url('assets/images/uploads/profile/' . $user['profile_photo']) ?>" 
                             alt="<?= htmlspecialchars($user['full_name']) ?>"
                             style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: var(--color-primary); color: white; font-size: 48px; font-weight: 700;">
                            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Name & Role -->
                <div style="flex: 1; padding-bottom: 8px;">
                    <h2 style="font-size: 24px; margin-bottom: 4px; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                        <?= htmlspecialchars($user['full_name']) ?>
                    </h2>
                    <span style="background: white; color: var(--color-primary); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                        <?= ucfirst($user['role']) ?>
                    </span>
                </div>
                
                <!-- Edit Button -->
                <a href="<?= url('modules/profile/edit.php') ?>" class="btn btn-outline" style="border-color: white; color: white;">
                    Edit Profil
                </a>
            </div>
            
            <!-- User Details -->
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <div>
                    <div style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 4px;">Username</div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($user['username']) ?></div>
                </div>
                
                <div>
                    <div style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 4px;">Email</div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($user['email']) ?></div>
                </div>
                
                <?php if ($user['phone']): ?>
                <div>
                    <div style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 4px;">Telepon</div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($user['phone']) ?></div>
                </div>
                <?php endif; ?>
                
                <div>
                    <div style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 4px;">Bergabung</div>
                    <div style="font-weight: 600;"><?= formatDate($user['created_at']) ?></div>
                </div>
            </div>
            
            <?php if ($user['bio']): ?>
            <div style="margin-top: 16px;">
                <div style="font-size: 12px; color: var(--color-text-muted); margin-bottom: 4px;">Bio</div>
                <div style="line-height: 1.6;"><?= nl2br(htmlspecialchars($user['bio'])) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Statistics -->
    <?php if (count($stats) > 0): ?>
    <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm); margin-bottom: 24px;">
        <h3 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 20px;">Statistik</h3>
        
        <?php if ($user['role'] === 'organisasi' || $user['role'] === 'admin'): ?>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
            <div style="text-align: center; padding: 20px; background: var(--color-bg); border-radius: 8px;">
                <div style="font-size: 32px; font-weight: 700; color: var(--color-primary);"><?= $stats['news_count'] ?></div>
                <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 4px;">Berita Dibuat</div>
            </div>
            
            <div style="text-align: center; padding: 20px; background: var(--color-bg); border-radius: 8px;">
                <div style="font-size: 32px; font-weight: 700; color: var(--color-success);"><?= $stats['news_published'] ?></div>
                <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 4px;">Berita Published</div>
            </div>
            
            <div style="text-align: center; padding: 20px; background: var(--color-bg); border-radius: 8px;">
                <div style="font-size: 32px; font-weight: 700; color: var(--color-warning);"><?= $stats['event_count'] ?></div>
                <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 4px;">Event Dibuat</div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($user['role'] === 'mahasiswa'): ?>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
            <div style="text-align: center; padding: 20px; background: var(--color-bg); border-radius: 8px;">
                <div style="font-size: 32px; font-weight: 700; color: var(--color-primary);"><?= $stats['events_joined'] ?></div>
                <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 4px;">Event Diikuti</div>
            </div>
            
            <div style="text-align: center; padding: 20px; background: var(--color-bg); border-radius: 8px;">
                <div style="font-size: 32px; font-weight: 700; color: var(--color-success);"><?= $stats['events_confirmed'] ?></div>
                <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 4px;">Event Terkonfirmasi</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
        <h3 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 20px;">Menu Cepat</h3>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
            <a href="<?= url('modules/profile/edit.php') ?>" 
               style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--color-bg); border-radius: 8px; text-decoration: none; color: var(--color-text-primary); transition: background 0.2s;"
               onmouseover="this.style.background='var(--color-primary-light)'"
               onmouseout="this.style.background='var(--color-bg)'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                <span style="font-weight: 600;">Edit Profil</span>
            </a>
            
            <a href="<?= url('modules/profile/password.php') ?>" 
               style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--color-bg); border-radius: 8px; text-decoration: none; color: var(--color-text-primary); transition: background 0.2s;"
               onmouseover="this.style.background='var(--color-primary-light)'"
               onmouseout="this.style.background='var(--color-bg)'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <span style="font-weight: 600;">Ubah Password</span>
            </a>
            
            <?php if ($user['role'] === 'mahasiswa'): ?>
            <a href="<?= url('modules/profile/my-registrations.php') ?>" 
               style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--color-bg); border-radius: 8px; text-decoration: none; color: var(--color-text-primary); transition: background 0.2s;"
               onmouseover="this.style.background='var(--color-primary-light)'"
               onmouseout="this.style.background='var(--color-bg)'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span style="font-weight: 600;">Pendaftaran Saya</span>
            </a>
            <?php endif; ?>
            
            <?php if ($user['role'] === 'organisasi'): ?>
            <a href="<?= url('modules/news/manage.php') ?>" 
               style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--color-bg); border-radius: 8px; text-decoration: none; color: var(--color-text-primary); transition: background 0.2s;"
               onmouseover="this.style.background='var(--color-primary-light)'"
               onmouseout="this.style.background='var(--color-bg)'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <span style="font-weight: 600;">Kelola Berita</span>
            </a>
            
            <a href="<?= url('modules/events/create.php') ?>" 
               style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--color-bg); border-radius: 8px; text-decoration: none; color: var(--color-text-primary); transition: background 0.2s;"
               onmouseover="this.style.background='var(--color-primary-light)'"
               onmouseout="this.style.background='var(--color-bg)'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="12" y1="11" x2="12" y2="17"></line>
                    <line x1="9" y1="14" x2="15" y2="14"></line>
                </svg>
                <span style="font-weight: 600;">Buat Event</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>