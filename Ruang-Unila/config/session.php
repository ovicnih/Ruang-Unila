<?php
/**
 * Session Management - Ruang Unila
 * 
 * Kelas ini menangani manajemen session dengan keamanan
 * untuk sistem web Ruang Unila.
 * 
 * @package RuangUnila
 * @version 1.0.0
 */

// Cegah akses langsung
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class Session {
    /**
     * Instance singleton
     * @var Session|null
     */
    private static ?Session $instance = null;

    /**
     * Constructor privat untuk singleton pattern
     */
    private function __construct() {
        // Konfigurasi session sebelum start
        $this->configureSession();
        
        // Mulai session jika belum aktif
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate ID session untuk keamanan
        $this->regenerateSession();
    }

    /**
     * Mencegah cloning instance
     */
    private function __clone() {}

    /**
     * Mendapatkan instance Session (singleton)
     * 
     * @return Session Instance session
     */
    public static function getInstance(): Session {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Mengkonfigurasi session dengan pengaturan keamanan
     */
    private function configureSession(): void {
        // Nama session
        session_name(SESSION_NAME ?? 'ruangunila_session');
        
        // Konfigurasi cookie session
        ini_set('session.cookie_httponly', '1');  // Cegah akses JavaScript
        ini_set('session.cookie_secure', '0');     // Set ke 1 jika menggunakan HTTPS
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        
        // Waktu timeout session (2 jam)
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME ?? 7200);
        ini_set('session.cookie_lifetime', SESSION_LIFETIME ?? 7200);
    }

    /**
     * Regenerate session ID untuk mencegah session fixation
     */
    private function regenerateSession(): void {
        // Regenerate setiap 30 menit
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    /**
     * Menyimpan data ke session
     * 
     * @param string $key Kunci data
     * @param mixed $value Nilai data
     */
    public function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    /**
     * Mengambil data dari session
     * 
     * @param string $key Kunci data
     * @param mixed $default Nilai default jika tidak ada
     * @return mixed Data dari session atau default
     */
    public function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Mengecek apakah key ada di session
     * 
     * @param string $key Kunci data
     * @return bool True jika ada
     */
    public function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    /**
     * Menghapus data dari session
     * 
     * @param string $key Kunci data
     */
    public function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    /**
     * Menghapus semua data session
     */
    public function clear(): void {
        session_unset();
    }

    /**
     * Menghancurkan session
     */
    public function destroy(): void {
        // Hapus semua data
        $_SESSION = [];
        
        // Hapus cookie session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Hancurkan session
        session_destroy();
    }

    /**
     * Set flash message (pesan sekali tampil)
     * 
     * @param string $type Tipe pesan (success, error, warning, info)
     * @param string $message Pesan
     */
    public function setFlash(string $type, string $message): void {
        $_SESSION['flash'][$type] = $message;
    }

    /**
     * Mendapatkan flash message
     * 
     * @param string $type Tipe pesan
     * @return string|null Pesan atau null
     */
    public function getFlash(string $type): ?string {
        if (isset($_SESSION['flash'][$type])) {
            $message = $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]);
            return $message;
        }
        return null;
    }

    /**
     * Mengecek apakah user sudah login
     * 
     * @return bool True jika sudah login
     */
    public function isLoggedIn(): bool {
        return $this->has('user_id') && $this->has('user_role');
    }

    /**
     * Mendapatkan ID user yang login
     * 
     * @return int|null ID user atau null
     */
    public function getUserId(): ?int {
        return $this->get('user_id');
    }

    /**
     * Mendapatkan role user yang login
     * 
     * @return string|null Role user atau null
     */
    public function getUserRole(): ?string {
        return $this->get('user_role');
    }

    /**
     * Mengecek apakah user memiliki role tertentu
     * 
     * @param string $role Role yang dicek
     * @return bool True jika memiliki role
     */
    public function hasRole(string $role): bool {
        return $this->getUserRole() === $role;
    }

    /**
     * Set data user setelah login berhasil
     * 
     * @param array $userData Data user dari database
     */
    public function setUserData(array $userData): void {
        $this->set('user_id', $userData['user_id']);
        $this->set('user_username', $userData['username']);
        $this->set('user_email', $userData['email']);
        $this->set('user_fullname', $userData['full_name']);
        $this->set('user_role', $userData['role']);
        $this->set('user_photo', $userData['profile_photo'] ?? null);
        $this->set('logged_in_at', date('Y-m-d H:i:s'));
    }

    /**
     * Mendapatkan data user yang login
     * 
     * @return array Data user
     */
    public function getUserData(): array {
        return [
            'user_id' => $this->getUserId(),
            'username' => $this->get('user_username'),
            'email' => $this->get('user_email'),
            'full_name' => $this->get('user_fullname'),
            'role' => $this->getUserRole(),
            'profile_photo' => $this->get('user_photo'),
            'logged_in_at' => $this->get('logged_in_at')
        ];
    }

    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public function generateCsrfToken(): string {
        if (!$this->has(CSRF_TOKEN_NAME ?? 'ruangunila_csrf_token')) {
            $token = bin2hex(random_bytes(TOKEN_LENGTH ?? 32));
            $this->set(CSRF_TOKEN_NAME ?? 'ruangunila_csrf_token', $token);
        }
        return $this->get(CSRF_TOKEN_NAME ?? 'ruangunila_csrf_token');
    }

    /**
     * Verifikasi CSRF token
     * 
     * @param string $token Token dari form
     * @return bool True jika valid
     */
    public function verifyCsrfToken(string $token): bool {
        $sessionToken = $this->get(CSRF_TOKEN_NAME ?? 'ruangunila_csrf_token');
        return hash_equals($sessionToken, $token);
    }

    /**
     * Mendapatkan CSRF token untuk form
     * 
     * @return string HTML input untuk CSRF token
     */
    public function getCsrfField(): string {
        $token = $this->generateCsrfToken();
        $name = CSRF_TOKEN_NAME ?? 'ruangunila_csrf_token';
        return '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($token) . '">';
    }
}
?>