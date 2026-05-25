<?php
/**
 * Modul Edit News - Ruang Unila
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/config/constants.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/includes/functions.php';

require_once APP_ROOT . '/classes/User.php';
require_once APP_ROOT . '/classes/News.php';
require_once APP_ROOT . '/classes/Event.php';

$session = Session::getInstance();
$news = new News();

$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($newsId <= 0) {
    $session->setFlash('error', 'ID berita tidak valid.');
    header('Location: ' . url('modules/news/manage.php'));
    exit;
}

$newsItem = $news->getById($newsId);
if (!$newsItem) {
    $session->setFlash('error', 'Berita tidak ditemukan.');
    header('Location: ' . url('modules/news/manage.php'));
    exit;
}

$userRole = $session->getUserRole();
$userId = $session->getUserId();

if ($userRole !== ROLE_ADMIN && $newsItem['author_id'] !== $userId) {
    $session->setFlash('error', 'Anda tidak memiliki akses untuk mengedit berita ini.');
    header('Location: ' . url('modules/news/manage.php'));
    exit;
}

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!$session->verifyCsrfToken($csrfToken)) {
        $result = ['success' => false, 'message' => 'Token keamanan tidak valid.'];
    } else {
        $title = cleanInput($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $category = cleanInput($_POST['category'] ?? '');

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
            $image = $newsItem['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleImageUpload($_FILES['image'], 'news');
                if ($uploadResult['success']) {
                    if ($newsItem['image']) {
                        $oldPath = APP_ROOT . '/assets/images/uploads/news/' . $newsItem['image'];
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $image = $uploadResult['filename'];
                } else {
                    $result = ['success' => false, 'message' => $uploadResult['error']];
                }
            }

            if (!$result) {
                $updateData = [
                    'title' => $title,
                    'content' => $content,
                    'category' => $category,
                    'image' => $image,
                ];

                if ($news->update($newsId, $updateData)) {
                    $session->setFlash('success', 'Berita berhasil diperbarui!');
                    header('Location: ' . url('modules/news/detail.php?id=' . $newsId));
                    exit;
                } else {
                    $result = ['success' => false, 'message' => 'Gagal memperbarui berita.'];
                }
            }
        }
    }

    if ($result && !$result['success']) {
        $session->setFlash('error', $result['message']);
    }
}

$csrfToken = $session->generateCsrfToken();
$pageTitle = 'Edit Berita';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Berita - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <div class="navbar-container">
            <a href="<?= url() ?>" class="navbar-brand">Ruang Unila</a>
            <div class="navbar-nav">
                <a href="<?= url() ?>" class="nav-link">Beranda</a>
                <a href="<?= url('modules/news/list.php') ?>" class="nav-link active">Berita</a>
                <a href="<?= url('modules/events/list.php') ?>" class="nav-link">Event</a>
            </div>
            <div style="display: flex; align-items: center; gap: 16px;">
                <a href="<?= url('modules/profile/index.php') ?>" class="btn btn-secondary btn-sm">Profil</a>
                <a href="<?= url('modules/auth/logout.php') ?>" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container" style="max-width: 800px; margin: 0 auto;">
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
                <div style="margin-bottom: 32px;">
                    <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 8px;">
                        Edit Berita
                    </h1>
                    <p style="color: var(--color-text-secondary); font-size: 14px;">
                        Perbarui berita yang sudah ada
                    </p>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">

                    <div class="form-group">
                        <label class="form-label" for="title">Judul Berita</label>
                        <input type="text" id="title" name="title" class="form-input"
                               placeholder="Masukkan judul berita" required maxlength="200"
                               value="<?= htmlspecialchars($_POST['title'] ?? $newsItem['title']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="category">Kategori</label>
                        <select id="category" name="category" class="form-input" required>
                            <option value="">Pilih kategori</option>
                            <?php foreach (NEWS_CATEGORIES as $slug => $name): ?>
                                <option value="<?= $slug ?>" <?= ($_POST['category'] ?? $newsItem['category']) === $slug ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="image">Gambar Berita</label>
                        <?php if ($newsItem['image']): ?>
                            <div style="margin-bottom: 12px;">
                                <img src="<?= url('assets/images/uploads/news/' . $newsItem['image']) ?>"
                                     alt="Current image" style="max-width: 200px; border-radius: 8px;">
                                <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                                    Gambar saat ini. Upload gambar baru untuk mengganti.
                                </p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="image" name="image" class="form-input"
                               accept="image/jpeg,image/png,image/gif">
                        <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                            Format: JPG, PNG, GIF. Maksimal 2MB. Biarkan kosong jika tidak ingin mengganti.
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="content">Konten Berita</label>
                        <textarea id="content" name="content" class="form-textarea"
                                  style="min-height: 300px;"
                                  placeholder="Tulis konten berita di sini..." required><?= htmlspecialchars($_POST['content'] ?? $newsItem['content']) ?></textarea>
                    </div>

                    <div style="display: flex; gap: 16px; margin-top: 32px;">
                        <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">
                            Simpan Perubahan
                        </button>
                        <a href="<?= url('modules/news/manage.php') ?>" class="btn btn-outline btn-lg">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
