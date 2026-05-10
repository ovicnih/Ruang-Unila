<?php
/**
 * Modul Login - Ruang Unila
 * 
 * Modul ini menangani proses login user dengan
 * validasi keamanan dan session management.
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
 * Kelas Login untuk menangani autentikasi user
 */
class Login {
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
     * Memproses form login
     * 
     * @return array Result dengan status dan pesan
     */
    public function processLogin(): array {
        // Validasi CSRF token
        $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
        if (!$this->session->verifyCsrfToken($csrfToken)) {
            return [
                'success' => false,
                'message' => 'Token keamanan tidak valid. Silakan coba lagi.'
            ];
        }
        
        // Ambil dan sanitasi input
        $email = cleanInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validasi input
        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Email dan kata sandi harus diisi.'
            ];
        }
        
        if (!isValidEmail($email)) {
            return [
                'success' => false,
                'message' => 'Format email tidak valid.'
            ];
        }
        
        // Cek user di database
        $user = $this->getUserByEmail($email);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => MSG_ERROR_LOGIN
            ];
        }
        
        // Verifikasi password
        if (!verifyPassword($password, $user['password'])) {
            return [
                'success' => false,
                'message' => MSG_ERROR_LOGIN
            ];
        }
        
        // Cek status user
        if ($user['status'] !== STATUS_ACTIVE) {
            return [
                'success' => false,
                'message' => 'Akun Anda tidak aktif. Silakan hubungi admin.'
            ];
        }
        
        // Set session user
        $this->session->setUserData($user);
        
        // Regenerate session ID untuk keamanan
        session_regenerate_id(true);
        
        return [
            'success' => true,
            'message' => MSG_SUCCESS_LOGIN,
            'user' => [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ];
    }
    
    /**
     * Mendapatkan user berdasarkan email
     * 
     * @param string $email Email user
     * @return array|null Data user atau null
     */
    private function getUserByEmail(string $email): ?array {
        $sql = "SELECT user_id, username, email, password, full_name, role, profile_photo, status 
                FROM users 
                WHERE email = :email 
                LIMIT 1";
        
        return $this->db->fetchOne($sql, [':email' => $email]);
    }
    
    /**
     * Mendapatkan redirect URL berdasarkan role
     * 
     * @param string $role Role user
     * @return string URL redirect
     */
    public function getRedirectUrl(string $role): string {
        switch ($role) {
            case ROLE_ADMIN:
                return url('modules/admin/dashboard.php');
            case ROLE_ORGANISATION:
                return url('modules/news/create.php');
            case ROLE_STUDENT:
            default:
                return url('index.php');
        }
    }
}

// Proses form login
$login = new Login();
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $login->processLogin();
    
    if ($result['success']) {
        // Set flash message
        $login->session->setFlash('success', $result['message']);
        
        // Redirect berdasarkan role
        $redirectUrl = $login->getRedirectUrl($result['user']['role']);
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        // Set flash message error
        $login->session->setFlash('error', $result['message']);
    }
}

// Generate CSRF token untuk form
$csrfToken = $login->session->generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
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
                <a href="<?= url('modules/auth/register.php') ?>" class="btn btn-secondary btn-sm">Daftar</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container" style="max-width: 500px; margin: 0 auto; padding-top: 60px;">
            <div style="background: white; border-radius: 12px; padding: 40px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <!-- Header -->
                <div style="text-align: center; margin-bottom: 32px;">
                    <h1 style="font-family: 'Newsreader', serif; font-size: 32px; margin-bottom: 8px; color: var(--color-text-primary);">
                        Masuk
                    </h1>
                    <p style="color: var(--color-text-secondary); font-size: 14px;">
                        Selamat datang kembali di Ruang Unila
                    </p>
                </div>

                <!-- Flash Message -->
                <?php
                $flashError = $login->session->getFlash('error');
                $flashSuccess = $login->session->getFlash('success');
                
                if ($flashError) {
                    echo '<div class="alert alert-error">' . htmlspecialchars($flashError) . '</div>';
                }
                if ($flashSuccess) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($flashSuccess) . '</div>';
                }
                ?>

                <!-- Form Login -->
                <form method="POST" action="">
                    <!-- CSRF Token -->
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                    
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
                        <div style="text-align: right; margin-top: 8px;">
                            <a href="#" style="font-size: 12px; color: var(--color-primary); font-weight: 700;">
                                Lupa kata sandi?
                            </a>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 16px;">
                        Masuk
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

                <!-- Register Link -->
                <div style="text-align: center;">
                    <p style="color: var(--color-text-secondary); font-size: 14px;">
                        Belum punya akun?
                        <a href="<?= url('modules/auth/register.php') ?>" style="color: var(--color-primary); font-weight: 700; margin-left: 4px;">
                            Daftar
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
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Email dan kata sandi harus diisi.');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Format email tidak valid.');
                return;
            }
        });
    </script>
</body>
</html>