<?php
/**
 * Modul Create News - Ruang Unila
 * 
 * Modul ini menangani pembuatan berita baru oleh organisasi.
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

// Cek login dan role
if (!$session->isLoggedIn()) {
    $session->setFlash('error', 'Anda harus login untuk membuat berita.');
    header('Location: ' . url('modules/auth/login.php'));
    exit;
}

$userRole = $session->getUserRole();
if ($userRole !== ROLE_ORGANISATION && $userRole !== ROLE_ADMIN) {
    $session->setFlash('error', 'Anda tidak memiliki akses untuk membuat berita.');
    header('Location: ' . url('modules/news/list.php'));
    exit;
}

// Proses form
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!$session->verifyCsrfToken($csrfToken)) {
        $result = ['success' => false, 'message' => 'Token keamanan tidak valid.'];
    } else {
        // Ambil data
        $title = cleanInput($_POST['title'] ?? '');
        $content = $_POST['content'] ?? ''; // Tidak di-clean karena bisa HTML
        $category = cleanInput($_POST['category'] ?? '');
        
        // Validasi
        if (empty($title)) {
            $result = ['success' => false, 'message' => 'Judul berita harus diisi.'];
        } elseif (strlen($title) > 200) {
            $result = ['success' => false, 'message' => 'Judul berita maksimal 200 karakter.'];
        } elseif (empty($content)) {
            $result = ['success' => false, 'message' => 'Konten berita harus diisi.'];
        } elseif (empty($category)) {
            $result = ['success' => false, 'message' => 'Kategori berita harus dipilih.'];
        } elseif (!isset(NEWS_CATEGORIES[$category])) {
            $result = ['success' => false, 'message' => 'Kategori tidak valid.'];
        } else {
            // Upload gambar
            $image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleImageUpload($_FILES['image'], 'news');
                if ($uploadResult['success']) {
                    $image = $uploadResult['filename'];
                } else {
                    $result = ['success' => false, 'message' => $uploadResult['message']];
                }
            }
            
if (!$result) {
                // Insert ke database
                $newsData = [
                    'title' => $title,
                    'content' => $content,
                    'category' => $category,
                    'author_id' => $session->getUserId(),
                    'image' => $image,
                    'status' => $userRole === ROLE_ADMIN ? 'published' : 'pending'
                ];
                
                $newsId = $news->insert($newsData);
                
                if ($newsId) {
                    $session->setFlash('success', 'Berita berhasil dibuat!');
                    redirect('modules/news/detail.php?id=' . $newsId);
                } else {
                    $result = ['success' => false, 'message' => 'Gagal menyimpan berita.'];
                }
            }
        }
    }
    
    if ($result && !$result['success']) {
        $session->setFlash('error', $result['message']);
    }
}

// Generate CSRF token
$csrfToken = $session->generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Berita - <?= APP_NAME ?></title>
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
            <div style="display: flex; align-items: center; gap: 16px;">
                <a href="<?= url('modules/profile/view.php') ?>" class="btn btn-secondary btn-sm">Profil</a>
                <a href="<?= url('modules/auth/logout.php') ?>" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container" style="max-width: 800px; margin: 0 auto;">
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

            <div style="background: white; border-radius: 12px; padding: 40px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <!-- Header -->
                <div style="margin-bottom: 32px;">
                    <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">
                        Buat Berita Baru
                    </h1>
                    <p style="color: var(--color-text-secondary); font-size: 14px;">
                        Tulis berita untuk dipublikasikan di Ruang Unila
                    </p>
                </div>

                <!-- Form -->
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                    
                    <!-- Judul -->
                    <div class="form-group">
                        <label class="form-label" for="title">Judul Berita</label>
                        <input type="text" 
                               id="title" 
                               name="title" 
                               class="form-input" 
                               placeholder="Masukkan judul berita"
                               required
                               maxlength="200"
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                    </div>

                    <!-- Kategori -->
                    <div class="form-group">
                        <label class="form-label" for="category">Kategori</label>
                        <select id="category" name="category" class="form-input" required>
                            <option value="">Pilih kategori</option>
                            <?php foreach (NEWS_CATEGORIES as $slug => $name): ?>
                                <option value="<?= $slug ?>" <?= ($_POST['category'] ?? '') === $slug ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Gambar -->
                    <div class="form-group">
                        <label class="form-label" for="image">Gambar Berita</label>
                        <input type="file" 
                               id="image" 
                               name="image" 
                               class="form-input" 
                               accept="image/jpeg,image/png,image/gif">
                        <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                            Format: JPG, PNG, GIF. Maksimal 2MB.
                        </div>
                    </div>

                    <!-- Konten -->
                    <div class="form-group">
                        <label class="form-label" for="content">Konten Berita</label>
                        <textarea 
                            id="content" 
                            name="content" 
                            class="form-textarea" 
                            style="min-height: 300px;"
                            placeholder="Tulis konten berita di sini..."
                            required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                    </div>

                    <!-- Submit -->
                    <div style="display: flex; gap: 16px; margin-top: 32px;">
                        <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">
                            Publikasikan Berita
                        </button>
                        <a href="<?= url('modules/news/list.php') ?>" class="btn btn-outline btn-lg">
                            Batal
                        </a>
                    </div>
                </form>
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