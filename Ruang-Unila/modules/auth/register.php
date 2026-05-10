<?php
/**
 * Modul Register - Ruang Unila
 * 
 * Modul ini menangani proses registrasi user baru dengan
 * validasi keamanan dan pembuatan akun.
 * 
 * @package RuangUnila
 * @subpackage Auth
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

/**
 * Kelas Register untuk menangani registrasi user baru
 */
class Register {
    /**
     * Instance database
     * @var Database
     */
    public Database $db;
    
    /**
     * Instance session
     * @var Session
     */
    public Session $session;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
    }
    
    /**
     * Memproses form registrasi
     * 
     * @return array Result dengan status dan pesan
     */
    public function processRegister(): array {
        // Validasi CSRF token
        $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
        if (!$this->session->verifyCsrfToken($csrfToken)) {
            return [
                'success' => false,
                'message' => 'Token keamanan tidak valid. Silakan coba lagi.'
            ];
        }
        
        // Ambil dan sanitasi input
        $username = cleanInput($_POST['username'] ?? '');
        $email = cleanInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $fullName = cleanInput($_POST['full_name'] ?? '');
        $role = cleanInput($_POST['role'] ?? 'student');
        
        // Validasi input kosong
        if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
            return [
                'success' => false,
                'message' => 'Semua field harus diisi.'
            ];
        }
        
        // Validasi email
        if (!isValidEmail($email)) {
            return [
                'success' => false,
                'message' => 'Format email tidak valid.'
            ];
        }
        
        // Validasi username
        if (!isValidUsername($username)) {
            return [
                'success' => false,
                'message' => 'Username hanya boleh berisi huruf, angka, dan underscore (3-50 karakter).'
            ];
        }
        
        // Validasi panjang nama
        if (!isValidLength($fullName, 3, 100)) {
            return [
                'success' => false,
                'message' => 'Nama lengkap harus 3-100 karakter.'
            ];
        }
        
        // Validasi password
        if (strlen($password) < 8) {
            return [
                'success' => false,
                'message' => 'Kata sandi minimal 8 karakter.'
            ];
        }
        
        if ($password !== $confirmPassword) {
            return [
                'success' => false,
                'message' => 'Konfirmasi kata sandi tidak cocok.'
            ];
        }
        
        // Validasi role
        $allowedRoles = [ROLE_STUDENT, ROLE_ORGANISATION];
        if (!in_array($role, $allowedRoles)) {
            $role = ROLE_STUDENT;
        }
        
        // Cek email sudah terdaftar
        if ($this->isEmailExists($email)) {
            return [
                'success' => false,
                'message' => 'Email sudah terdaftar.'
            ];
        }
        
        // Cek username sudah terdaftar
        if ($this->isUsernameExists($username)) {
            return [
                'success' => false,
                'message' => 'Username sudah digunakan.'
            ];
        }
        
        // Hash password
        $hashedPassword = hashPassword($password);
        
        // Insert user ke database
        $userId = $this->insertUser($username, $email, $hashedPassword, $fullName, $role);
        
        if ($userId) {
            return [
                'success' => true,
                'message' => MSG_SUCCESS_REGISTER
            ];
        } else {
            return [
                'success' => false,
                'message' => MSG_ERROR_GENERIC
            ];
        }
    }
    
    /**
     * Mengecek apakah email sudah terdaftar
     * 
     * @param string $email Email yang dicek
     * @return bool True jika sudah ada
     */
    private function isEmailExists(string $email): bool {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = :email";
        $result = $this->db->fetchOne($sql, [':email' => $email]);
        return $result && $result['count'] > 0;
    }
    
    /**
     * Mengecek apakah username sudah terdaftar
     * 
     * @param string $username Username yang dicek
     * @return bool True jika sudah ada
     */
    private function isUsernameExists(string $username): bool {
        $sql = "SELECT COUNT(*) as count FROM users WHERE username = :username";
        $result = $this->db->fetchOne($sql, [':username' => $username]);
        return $result && $result['count'] > 0;
    }
    
    /**
     * Insert user baru ke database
     * 
     * @param string $username Username
     * @param string $email Email
     * @param string $password Password hashed
     * @param string $fullName Nama lengkap
     * @param string $role Role user
     * @return int|null ID user atau null jika gagal
     */
    private function insertUser(string $username, string $email, string $password, string $fullName, string $role): ?int {
        $sql = "INSERT INTO users (username, email, password, full_name, role, status) 
                VALUES (:username, :email, :password, :full_name, :role, :status)";
        
        $params = [
            ':username' => $username,
            ':email' => $email,
            ':password' => $password,
            ':full_name' => $fullName,
            ':role' => $role,
            ':status' => STATUS_ACTIVE
        ];
        
        $stmt = $this->db->executeQuery($sql, $params);
        
        if ($stmt) {
            return (int) $this->db->lastInsertId();
        }
        
        return null;
    }
}

// Proses form registrasi
$register = new Register();
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $register->processRegister();
    
    if ($result['success']) {
        // Set flash message
        $register->session->setFlash('success', $result['message']);
        
        // Redirect ke halaman login
        header('Location: ' . url('modules/auth/login.php'));
        exit;
    } else {
        // Set flash message error
        $register->session->setFlash('error', $result['message']);
    }
}

// Generate CSRF token untuk form
$csrfToken = $register->session->generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <div class="navbar-container">
            <a href="<?= url() ?>" class="navbar-brand">Ruang Unila</a>
            <div class="navbar-nav">
                <a href="<?= url() ?>" class="nav-link">Beranda</a>
                <a href="<?= url('modules/news/list.php') ?>" class="nav-link">Berita</a>
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
                <a href="<?= url('modules/auth/login.php') ?>" class="btn btn-secondary btn-sm">Masuk</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container" style="max-width: 500px; margin: 0 auto; padding-top: 40px;">
            <div style="background: white; border-radius: 12px; padding: 40px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <!-- Header -->
                <div style="text-align: center; margin-bottom: 32px;">
                    <a href="<?= url() ?>" style="color: var(--color-primary); font-family: 'Newsreader', serif; font-size: 24px; font-weight: 700; letter-spacing: -0.6px; text-decoration: none;">
                        Ruang Unila
                    </a>
                    <h1 style="font-family: 'Newsreader', serif; font-size: 32px; margin-top: 24px; margin-bottom: 8px; color: var(--color-text-primary);">
                        Daftar
                    </h1>
                    <p style="color: var(--color-text-secondary); font-size: 14px;">
                        Buat akun baru untuk mengakses Ruang Unila
                    </p>
                </div>

                <!-- Flash Message -->
                <?php
                $flashError = $register->session->getFlash('error');
                $flashSuccess = $register->session->getFlash('success');
                
                if ($flashError) {
                    echo '<div class="alert alert-error">' . htmlspecialchars($flashError) . '</div>';
                }
                if ($flashSuccess) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($flashSuccess) . '</div>';
                }
                ?>

                <!-- Form Register -->
                <form method="POST" action="">
                    <!-- CSRF Token -->
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                    
                    <!-- Nama Lengkap -->
                    <div class="form-group">
                        <label class="form-label" for="full_name">Nama Lengkap</label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               class="form-input" 
                               placeholder="Nama lengkap Anda"
                               required
                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    </div>

                    <!-- Username -->
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-input" 
                               placeholder="username_unik"
                               required
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label" for="email">Alamat Email</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-input" 
                               placeholder="email@unila.ac.id"
                               required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label class="form-label" for="password">Kata Sandi</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-input" 
                               placeholder="••••••••"
                               required>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Konfirmasi Kata Sandi</label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-input" 
                               placeholder="••••••••"
                               required>
                    </div>

                    <!-- Role -->
                    <div class="form-group">
                        <label class="form-label" for="role">Daftar Sebagai</label>
                        <select id="role" name="role" class="form-input">
                            <option value="student" <?= ($_POST['role'] ?? '') === 'student' ? 'selected' : '' ?>>Mahasiswa</option>
                            <option value="organisation" <?= ($_POST['role'] ?? '') === 'organisation' ? 'selected' : '' ?>>Organisasi</option>
                        </select>
                    </div>

                    <!-- Terms -->
                    <div style="display: flex; align-items: flex-start; gap: 12px; margin-top: 24px;">
                        <input type="checkbox" id="terms" name="terms" required style="margin-top: 4px;">
                        <label for="terms" style="font-size: 14px; color: var(--color-text-secondary);">
                            Saya setuju dengan <a href="#" style="color: var(--color-primary); font-weight: 700;">Ketentuan Layanan</a> dan <a href="#" style="color: var(--color-primary); font-weight: 700;">Kebijakan Privasi</a>.
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 24px;">
                        Daftar
                    </button>
                </form>

                <!-- Divider -->
                <div style="display: flex; align-items: center; margin: 32px 0;">
                    <div style="flex: 1; height: 1px; background: var(--color-border-light);"></div>
                    <span style="padding: 0 16px; color: var(--color-text-muted); font-size: 12px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase;">
                        atau
                    </span>
                    <div style="flex: 1; height: 1px; background: var(--color-border-light);"></div>
                </div>

                <!-- Login Link -->
                <div style="text-align: center;">
                    <p style="color: var(--color-text-secondary); font-size: 14px;">
                        Sudah punya akun?
                        <a href="<?= url('modules/auth/login.php') ?>" style="color: var(--color-primary); font-weight: 700; margin-left: 4px;">
                            Masuk
                        </a>
                    </p>
                </div>
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

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value.trim();
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            
            if (!fullName || !username || !email || !password || !confirmPassword) {
                e.preventDefault();
                alert('Semua field harus diisi.');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Format email tidak valid.');
                return;
            }
            
            // Username validation
            const usernameRegex = /^[a-zA-Z0-9_]{3,50}$/;
            if (!usernameRegex.test(username)) {
                e.preventDefault();
                alert('Username hanya boleh berisi huruf, angka, dan underscore (3-50 karakter).');
                return;
            }
            
            // Password validation
            if (password.length < 8) {
                e.preventDefault();
                alert('Kata sandi minimal 8 karakter.');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Konfirmasi kata sandi tidak cocok.');
                return;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('Anda harus menyetujui Ketentuan Layanan.');
                return;
            }
        });
    </script>
</body>
</html>