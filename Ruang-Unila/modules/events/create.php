<?php
/**
 * Modul Buat Event - Ruang Unila
 * 
 * Form pembuatan event oleh organisasi.
 * Mendukung upload gambar dan validasi lengkap.
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

// DEBUG MODE - Hapus setelah selesai debug
$debugMode = true;
$debugLog = [];
if ($debugMode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
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

// Require login sebagai organisasi atau admin
requireLogin();

if (!hasRole(ROLE_ORGANISATION) && !hasRole(ROLE_ADMIN)) {
    setFlashMessage('error', 'Hanya organisasi yang bisa membuat event.');
    redirect('modules/events/list.php');
}

// Inisialisasi
$event = new Event();
$errors = [];
$formData = [
    'title' => '',
    'description' => '',
    'location' => '',
    'event_date' => '',
    'registration_deadline' => '',
    'max_participants' => '',
    'fee' => '0'
];

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($debugMode) {
        $debugLog[] = 'POST received';
        $debugLog[] = 'POST data: ' . print_r($_POST, true);
        $debugLog[] = 'FILES data: ' . print_r($_FILES, true);
        $debugLog[] = 'Session user_id: ' . ($_SESSION['user_id'] ?? 'not set');
        $debugLog[] = 'Session role: ' . ($_SESSION['role'] ?? 'not set');
    }
    
    // Validasi CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if ($debugMode) {
        $debugLog[] = 'CSRF token from POST: ' . substr($csrfToken, 0, 20) . '...';
        $debugLog[] = 'CSRF token empty: ' . (empty($csrfToken) ? 'YES' : 'NO');
    }
    
    if (!verifyCSRFToken($csrfToken)) {
        $errors[] = 'Token keamanan tidak valid.';
        if ($debugMode) {
            $debugLog[] = 'CSRF verification FAILED';
        }
    } else {
        if ($debugMode) {
            $debugLog[] = 'CSRF verification PASSED';
        }
    }
    
    // Ambil dan sanitasi input
    $formData['title'] = sanitize($_POST['title'] ?? '');
    $formData['description'] = sanitize($_POST['description'] ?? '');
    $formData['location'] = sanitize($_POST['location'] ?? '');
    $formData['event_date'] = $_POST['event_date'] ?? '';
    $formData['registration_deadline'] = $_POST['registration_deadline'] ?? '';
    $formData['max_participants'] = $_POST['max_participants'] ?? '';
    $formData['fee'] = $_POST['fee'] ?? '0';
    
    // Validasi judul
    if (empty($formData['title'])) {
        $errors[] = 'Judul event wajib diisi.';
    } elseif (strlen($formData['title']) < 10) {
        $errors[] = 'Judul event minimal 10 karakter.';
    } elseif (strlen($formData['title']) > 200) {
        $errors[] = 'Judul event maksimal 200 karakter.';
    }
    
    // Validasi deskripsi
    if (empty($formData['description'])) {
        $errors[] = 'Deskripsi event wajib diisi.';
    } elseif (strlen($formData['description']) < 50) {
        $errors[] = 'Deskripsi event minimal 50 karakter.';
    }
    
    // Validasi lokasi
    if (empty($formData['location'])) {
        $errors[] = 'Lokasi event wajib diisi.';
    }
    
    // Validasi tanggal event
    if (empty($formData['event_date'])) {
        $errors[] = 'Tanggal event wajib diisi.';
    } else {
        $eventDate = new DateTime($formData['event_date']);
        $now = new DateTime();
        if ($eventDate <= $now) {
            $errors[] = 'Tanggal event harus di masa depan.';
        }
    }
    
    // Validasi deadline pendaftaran
    if (empty($formData['registration_deadline'])) {
        $errors[] = 'Deadline pendaftaran wajib diisi.';
    } else {
        $deadline = new DateTime($formData['registration_deadline']);
        $now = new DateTime();
        $eventDate = new DateTime($formData['event_date']);
        
        if ($deadline <= $now) {
            $errors[] = 'Deadline pendaftaran harus di masa depan.';
        } elseif ($deadline >= $eventDate) {
            $errors[] = 'Deadline pendaftaran harus sebelum tanggal event.';
        }
    }
    
    // Validasi kuota
    if (empty($formData['max_participants'])) {
        $errors[] = 'Kuota peserta wajib diisi.';
    } elseif (!is_numeric($formData['max_participants']) || $formData['max_participants'] < 1) {
        $errors[] = 'Kuota peserta harus angka positif.';
    } elseif ($formData['max_participants'] > 1000) {
        $errors[] = 'Kuota peserta maksimal 1000.';
    }
    
    // Validasi biaya
    if (!is_numeric($formData['fee']) || $formData['fee'] < 0) {
        $errors[] = 'Biaya pendaftaran harus angka positif atau 0.';
    }
    
    // Validasi file upload (opsional)
    $imageFilename = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        
        // Validasi tipe file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = 'Format gambar harus JPG atau PNG.';
        }
        
        // Validasi ukuran file (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran gambar maksimal 2MB.';
        }
        
        // Validasi upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Gagal mengupload gambar. Silakan coba lagi.';
        }
    }
    
    if ($debugMode) {
        $debugLog[] = 'Total errors: ' . count($errors);
        if (count($errors) > 0) {
            $debugLog[] = 'Errors: ' . print_r($errors, true);
        }
    }
    
    // Jika tidak ada error, simpan ke database
    if (count($errors) === 0) {
        if ($debugMode) {
            $debugLog[] = 'No errors, proceeding with insert';
        }
        
        // Upload gambar jika ada
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $imageFilename = 'event_' . time() . '_' . uniqid() . '.' . $extension;
            $uploadPath = APP_ROOT . '/assets/images/uploads/events/';
            
            // Buat direktori jika belum ada
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            if (!move_uploaded_file($file['tmp_name'], $uploadPath . $imageFilename)) {
                $errors[] = 'Gagal menyimpan gambar. Silakan coba lagi.';
                $imageFilename = null;
            }
        }
        
        // Insert ke database
        if (count($errors) === 0) {
            $data = [
                'title' => $formData['title'],
                'description' => $formData['description'],
                'location' => $formData['location'],
                'event_date' => $formData['event_date'],
                'registration_deadline' => $formData['registration_deadline'],
                'max_participants' => (int) $formData['max_participants'],
                'fee' => (float) $formData['fee'],
                'organizer_id' => $_SESSION['user_id'],
                'image' => $imageFilename,
                'status' => 'pending' // Menunggu validasi admin
            ];
            
            if ($debugMode) {
                $debugLog[] = 'Attempting to insert event with data: ' . print_r($data, true);
            }
            
            $eventId = $event->insert($data);
            
            if ($debugMode) {
                $debugLog[] = 'Insert result: ' . var_export($eventId, true);
            }
            
            if ($eventId) {
                if ($debugMode) {
                    $debugLog[] = 'Event inserted successfully with ID: ' . $eventId;
                }
                setFlashMessage('success', 'Event berhasil dibuat! Menunggu validasi dari admin.');
                redirect('modules/events/detail.php?id=' . $eventId);
            } else {
                if ($debugMode) {
                    $debugLog[] = 'Event insert FAILED - returned false or 0';
                }
                $errors[] = 'Gagal menyimpan event. Silakan coba lagi.';
                
                // Hapus gambar jika gagal insert
                if ($imageFilename && file_exists($uploadPath . $imageFilename)) {
                    unlink($uploadPath . $imageFilename);
                }
            }
        }
    }
    
    if ($debugMode) {
        $debugLog[] = 'End of POST processing';
        // Write debug log to file
        file_put_contents(APP_ROOT . '/debug_create_event.log', date('Y-m-d H:i:s') . " - " . implode("\n", $debugLog) . "\n\n", FILE_APPEND);
    }
}

// Set page title
$pageTitle = 'Buat Event Baru';

// Include header
include APP_ROOT . '/includes/header.php';
?>

<div class="container" style="padding: 32px 0; max-width: 900px; margin: 0 auto;">
    <!-- Breadcrumb -->
    <nav style="margin-bottom: 24px; font-size: 14px;">
        <a href="<?= url('modules/events/list.php') ?>" style="color: var(--color-text-muted);">Event</a>
        <span style="margin: 0 8px; color: var(--color-text-muted);">/</span>
        <span style="color: var(--color-text-primary);">Buat Event Baru</span>
    </nav>
    
    <h1 style="font-family: var(--font-heading); font-size: 32px; margin-bottom: 32px;">
        Buat Event Baru
    </h1>
    
    <!-- Error Messages -->
    <?php if (count($errors) > 0): ?>
        <div style="background: var(--color-danger); color: white; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
            <strong>Terdapat kesalahan:</strong>
            <ul style="margin: 8px 0 0 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Debug Info -->
    <?php if ($debugMode && !empty($debugLog)): ?>
        <div style="background: #f0f0f0; color: #333; padding: 16px; border-radius: 8px; margin-bottom: 24px; font-family: monospace; font-size: 12px; white-space: pre-wrap;">
            <strong>DEBUG INFO:</strong>
            <?php foreach ($debugLog as $log): ?>
                <?= htmlspecialchars($log) . "\n" ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px;">
            <!-- Main Form -->
            <div>
                <!-- Informasi Dasar -->
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
                        <input type="file" name="image" accept="image/jpeg,image/png,image/jpg" class="form-input">
                        <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                            Format: JPG, PNG. Maksimal 2MB. Opsional.
                        </p>
                        <div id="image-preview" style="display: none; margin-top: 12px;">
                            <img id="preview-img" src="" alt="Preview" style="max-width: 100%; border-radius: 8px;">
                        </div>
                    </div>
                </div>
                
                <!-- Jadwal & Kuota -->
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
                                   class="form-input" min="1" max="1000" placeholder="100" required>
                            <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                                Maksimal 1000 peserta
                            </p>
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Biaya Pendaftaran (Rp)</label>
                            <input type="number" name="fee" value="<?= htmlspecialchars($formData['fee']) ?>" 
                                   class="form-input" min="0" step="1000" placeholder="0">
                            <p style="font-size: 12px; color: var(--color-text-muted); margin-top: 4px;">
                                Isi 0 untuk event gratis
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Simpan Event
                    </button>
                    <a href="<?= url('modules/events/list.php') ?>" class="btn btn-outline">
                        Batal
                    </a>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div>
                <!-- Tips -->
                <div style="background: white; padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                    <h3 style="font-family: var(--font-heading); font-size: 16px; margin-bottom: 16px;">Tips Membuat Event</h3>
                    <ul style="font-size: 14px; color: var(--color-text-muted); margin: 0; padding-left: 20px;">
                        <li style="margin-bottom: 8px;">Gunakan judul yang menarik dan deskriptif</li>
                        <li style="margin-bottom: 8px;">Jelaskan secara detail apa yang akan didapat peserta</li>
                        <li style="margin-bottom: 8px;">Tentukan deadline minimal 3 hari sebelum event</li>
                        <li style="margin-bottom: 8px;">Sertakan informasi kontak untuk pertanyaan</li>
                        <li>Upload gambar yang relevan dan menarik</li>
                    </ul>
                </div>
                
                <!-- Status Info -->
                <div style="background: var(--color-warning-light); padding: 20px; border-radius: 12px;">
                    <h3 style="font-size: 14px; color: var(--color-warning); margin-bottom: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        Catatan Penting
                    </h3>
                    <p style="font-size: 13px; color: var(--color-text-secondary); margin: 0;">
                        Event yang Anda buat akan melalui proses validasi oleh admin sebelum ditampilkan ke publik. Proses ini biasanya memakan waktu 1x24 jam.
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Preview image before upload
document.querySelector('input[name="image"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('image-preview').style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        document.getElementById('image-preview').style.display = 'none';
    }
});
</script>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>