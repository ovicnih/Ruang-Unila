<?php
/**
 * Modul Edit Event - Ruang Unila
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
require_once APP_ROOT . '/classes/Event.php';

$session = Session::getInstance();
$event = new Event();

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($eventId <= 0) {
    $session->setFlash('error', 'ID event tidak valid.');
    header('Location: ' . url('modules/events/manage.php'));
    exit;
}

$eventItem = $event->getById($eventId);
if (!$eventItem) {
    $session->setFlash('error', 'Event tidak ditemukan.');
    header('Location: ' . url('modules/events/manage.php'));
    exit;
}

$userRole = $session->getUserRole();
$userId = $session->getUserId();

if ($userRole !== ROLE_ADMIN && $eventItem['organizer_id'] !== $userId) {
    $session->setFlash('error', 'Anda tidak memiliki akses untuk mengedit event ini.');
    header('Location: ' . url('modules/events/manage.php'));
    exit;
}

$errors = [];
$formData = [
    'title' => $eventItem['title'],
    'description' => $eventItem['description'],
    'location' => $eventItem['location'],
    'event_date' => date('Y-m-d\TH:i', strtotime($eventItem['event_date'])),
    'registration_deadline' => date('Y-m-d\TH:i', strtotime($eventItem['registration_deadline'])),
    'max_participants' => $eventItem['max_participants'],
    'fee' => $eventItem['fee'],
    'bank_name' => $eventItem['bank_name'] ?? '',
    'bank_account_number' => $eventItem['bank_account_number'] ?? '',
    'bank_account_name' => $eventItem['bank_account_name'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $errors[] = 'Token keamanan tidak valid.';
    }

    $formData['title'] = sanitize($_POST['title'] ?? '');
    $formData['description'] = sanitize($_POST['description'] ?? '');
    $formData['location'] = sanitize($_POST['location'] ?? '');
    $formData['event_date'] = $_POST['event_date'] ?? '';
    $formData['registration_deadline'] = $_POST['registration_deadline'] ?? '';
    $formData['max_participants'] = $_POST['max_participants'] ?? '';
    $formData['fee'] = $_POST['fee'] ?? '0';
    $formData['bank_name'] = sanitize($_POST['bank_name'] ?? '');
    $formData['bank_account_number'] = sanitize($_POST['bank_account_number'] ?? '');
    $formData['bank_account_name'] = sanitize($_POST['bank_account_name'] ?? '');

    if (empty($formData['title'])) {
        $errors[] = 'Judul event wajib diisi.';
    } elseif (strlen($formData['title']) < 10) {
        $errors[] = 'Judul event minimal 10 karakter.';
    } elseif (strlen($formData['title']) > 200) {
        $errors[] = 'Judul event maksimal 200 karakter.';
    }

    if (empty($formData['description'])) {
        $errors[] = 'Deskripsi event wajib diisi.';
    } elseif (strlen($formData['description']) < 50) {
        $errors[] = 'Deskripsi event minimal 50 karakter.';
    }

    if (empty($formData['location'])) {
        $errors[] = 'Lokasi event wajib diisi.';
    }

    if (empty($formData['event_date'])) {
        $errors[] = 'Tanggal event wajib diisi.';
    }

    if (empty($formData['registration_deadline'])) {
        $errors[] = 'Deadline pendaftaran wajib diisi.';
    }

    $maxParticipants = (int)$formData['max_participants'];
    if ($maxParticipants <= 0) {
        $errors[] = 'Kuota peserta harus lebih dari 0.';
    } elseif ($maxParticipants > 1000) {
        $errors[] = 'Kuota peserta maksimal 1000.';
    }

    if (count($errors) === 0) {
        $imageFilename = $eventItem['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowedExt = ['jpg', 'jpeg', 'png'];
                if (!in_array($extension, $allowedExt)) {
                    $errors[] = 'Format gambar harus JPG atau PNG.';
                } else {
                    $imageFilename = 'event_' . time() . '_' . uniqid() . '.' . $extension;
                    $uploadPath = APP_ROOT . '/assets/images/uploads/events/';

                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath . $imageFilename)) {
                        if ($eventItem['image']) {
                            $oldPath = $uploadPath . $eventItem['image'];
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }
                    } else {
                        $errors[] = 'Gagal menyimpan gambar. Silakan coba lagi.';
                        $imageFilename = $eventItem['image'];
                    }
                }
            } else {
                $errors[] = 'Error upload gambar. Silakan coba lagi.';
            }
        }

        if (count($errors) === 0) {
            $data = [
                'title' => $formData['title'],
                'description' => $formData['description'],
                'location' => $formData['location'],
                'event_date' => $formData['event_date'],
                'registration_deadline' => $formData['registration_deadline'],
                'max_participants' => (int)$formData['max_participants'],
                'fee' => (float)$formData['fee'],
                'bank_name' => $formData['bank_name'],
                'bank_account_number' => $formData['bank_account_number'],
                'bank_account_name' => $formData['bank_account_name'],
                'image' => $imageFilename,
            ];

            if ($event->update($eventId, $data)) {
                setFlashMessage('success', 'Event berhasil diperbarui!');
                redirect('modules/events/detail.php?id=' . $eventId);
            } else {
                $errors[] = 'Gagal menyimpan event. Silakan coba lagi.';
            }
        }
    }
}

$pageTitle = 'Edit Event';
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0; max-width: 900px; margin: 0 auto;">
    <nav style="margin-bottom: 24px; font-size: 14px;">
        <a href="<?= url('modules/events/list.php') ?>" style="color: var(--color-text-muted);">Event</a>
        <span style="margin: 0 8px; color: var(--color-text-muted);">/</span>
        <span style="color: var(--color-text-primary);">Edit Event</span>
    </nav>

    <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 32px;">
        Edit Event
    </h1>

    <?php if (count($errors) > 0): ?>
        <div style="background: #fef2f2; border: 1px solid #fee2e2; padding: 20px; border-radius: 12px; margin-bottom: 32px; display: flex; gap: 16px; align-items: flex-start;">
            <div style="width: 24px; height: 24px; background: var(--color-danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </div>
            <div>
                <div style="font-weight: 700; color: #991b1b; margin-bottom: 4px; font-size: 15px;">Terdapat beberapa kendala:</div>
                <ul style="margin: 0; padding-left: 20px; color: #b91c1c; font-size: 14px; line-height: 1.6;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px;">
            <div>
                <div style="background: white; padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                    <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 20px;">Informasi Dasar</h2>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Judul Event *</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($formData['title']) ?>"
                               class="form-input" placeholder="Masukkan judul event" required>
                        <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                            Minimal 10 karakter, maksimal 200 karakter
                        </p>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Deskripsi Event *</label>
                        <textarea name="description" class="form-input" rows="6"
                                  placeholder="Jelaskan tentang event Anda..." required><?= htmlspecialchars($formData['description']) ?></textarea>
                        <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                            Minimal 50 karakter. Jelaskan secara detail tentang event.
                        </p>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Lokasi *</label>
                        <input type="text" name="location" value="<?= htmlspecialchars($formData['location']) ?>"
                               class="form-input" placeholder="Contoh: Aula Gedung A, Lantai 3" required>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Gambar Event</label>
                        <?php if ($eventItem['image']): ?>
                            <div style="margin-bottom: 12px;">
                                <img src="<?= url('assets/images/uploads/events/' . $eventItem['image']) ?>"
                                     alt="Current image" style="max-width: 200px; border-radius: 8px; display: block; margin-bottom: 4px;">
                                <p style="font-size: 12px; color: var(--color-text-muted); margin: 0;">
                                    Gambar saat ini. Upload gambar baru untuk mengganti.
                                </p>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/jpg" class="form-input">
                        <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                            Format: JPG, PNG. Maksimal 2MB. Biarkan kosong jika tidak ingin mengganti.
                        </p>
                    </div>
                </div>

                <div style="background: white; padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                    <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 20px;">Jadwal & Kuota</h2>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Tanggal & Waktu Event *</label>
                            <input type="datetime-local" name="event_date" value="<?= htmlspecialchars($formData['event_date']) ?>"
                                   class="form-input" required>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Deadline Pendaftaran *</label>
                            <input type="datetime-local" name="registration_deadline" value="<?= htmlspecialchars($formData['registration_deadline']) ?>"
                                   class="form-input" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Kuota Peserta *</label>
                            <input type="number" name="max_participants" value="<?= htmlspecialchars($formData['max_participants']) ?>"
                                   class="form-input" min="1" max="1000" required>
                            <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                                Maksimal 1000 peserta
                            </p>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Biaya Pendaftaran (Rp)</label>
                            <input type="number" name="fee" id="fee_input" value="<?= htmlspecialchars($formData['fee']) ?>"
                                   class="form-input" min="0" step="1000" placeholder="0">
                            <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                                Isi 0 untuk event gratis
                            </p>
                        </div>
                    </div>
                </div>

                <div id="payment_info_section" style="background: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; display: <?= $formData['fee'] > 0 ? 'block' : 'none' ?>;">
                    <h2 style="font-family: var(--font-heading); font-size: 20px; margin-bottom: 20px;">Informasi Pembayaran</h2>
                    <p style="font-size: 14px; color: var(--color-text-secondary); margin-bottom: 20px;">
                        Masukkan detail rekening bank organisasi Anda untuk menerima pembayaran pendaftaran dari mahasiswa.
                    </p>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Nama Bank *</label>
                        <input type="text" name="bank_name" value="<?= htmlspecialchars($formData['bank_name']) ?>"
                               class="form-input" placeholder="Contoh: Bank BRI / Mandiri / BCA">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Nomor Rekening *</label>
                            <input type="text" name="bank_account_number" value="<?= htmlspecialchars($formData['bank_account_number']) ?>"
                                   class="form-input" placeholder="Contoh: 1234567890">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Atas Nama *</label>
                            <input type="text" name="bank_account_name" value="<?= htmlspecialchars($formData['bank_account_name']) ?>"
                                   class="form-input" placeholder="Contoh: BEM FKIP Unila">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Simpan Perubahan
                    </button>
                    <a href="<?= url('modules/events/manage.php') ?>" class="btn btn-outline">
                        Batal
                    </a>
                </div>
            </div>

            <div>
                <div style="background: white; padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                    <h3 style="font-family: var(--font-heading); font-size: 16px; margin-bottom: 16px;">Tips Mengedit Event</h3>
                    <ul style="font-size: 14px; color: var(--color-text-muted); margin: 0; padding-left: 20px;">
                        <li style="margin-bottom: 8px;">Pastikan judul masih relevan</li>
                        <li style="margin-bottom: 8px;">Periksa kembali tanggal dan waktu</li>
                        <li style="margin-bottom: 8px;">Update deskripsi jika ada perubahan</li>
                        <li>Ganti gambar jika perlu</li>
                    </ul>
                </div>

                <div style="background: var(--color-warning-light); padding: 20px; border-radius: 12px;">
                    <h3 style="font-size: 14px; color: var(--color-warning); margin-bottom: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        Catatan
                    </h3>
                    <p style="font-size: 13px; color: var(--color-text-secondary); margin: 0;">
                        Perubahan akan langsung diterapkan. Jika event sudah memiliki pendaftar, perubahan informasi pembayaran hanya akan berlaku untuk pendaftaran baru.
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.querySelector('input[name="image"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('#payment_info_section').previousElementSibling;
            <?php if ($eventItem['image']): ?>
            const existingPreview = document.querySelector('img[alt="Current image"]');
            if (existingPreview) {
                existingPreview.src = e.target.result;
            }
            <?php endif; ?>
        }
        reader.readAsDataURL(file);
    }
});
document.getElementById('fee_input').addEventListener('input', function(e) {
    const fee = parseFloat(e.target.value) || 0;
    const paymentSection = document.getElementById('payment_info_section');
    if (fee > 0) {
        paymentSection.style.display = 'block';
        paymentSection.querySelectorAll('input').forEach(input => input.required = true);
    } else {
        paymentSection.style.display = 'none';
        paymentSection.querySelectorAll('input').forEach(input => input.required = false);
    }
});
</script>

<?php
include APP_ROOT . '/includes/footer.php';
?>
