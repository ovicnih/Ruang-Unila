<?php
/**
 * Modul Logout - Ruang Unila
 * 
 * Modul ini menangani proses logout user dengan
 * penghancuran session dan redirect.
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
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/includes/functions.php';

// Include classes
require_once APP_ROOT . '/classes/User.php';

/**
 * Kelas Logout untuk menangani proses logout user
 */
class Logout {
    /**
     * Instance session
     * @var Session
     */
    public Session $session;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->session = Session::getInstance();
    }
    
    /**
     * Memproses logout
     * 
     * @return array Result dengan status dan pesan
     */
    public function processLogout(): array {
        // Cek apakah user sudah login
        if (!$this->session->isLoggedIn()) {
            return [
                'success' => false,
                'message' => 'Anda belum login.'
            ];
        }
        
        // Dapatkan data user sebelum logout (untuk logging)
        $userData = $this->session->getUserData();
        
        // Hancurkan session
        $this->session->destroy();
        
        return [
            'success' => true,
            'message' => MSG_SUCCESS_LOGOUT,
            'user' => $userData
        ];
    }
}

// Proses logout
$logout = new Logout();
$result = $logout->processLogout();

if ($result['success']) {
    // Set flash message untuk halaman login
    // (session baru akan dibuat saat redirect)
    session_start();
    $_SESSION['flash']['success'] = $result['message'];
    
    // Redirect ke halaman login
    header('Location: ' . url('modules/auth/login.php'));
    exit;
} else {
    // Jika tidak login, redirect ke beranda
    header('Location: ' . url('index.php'));
    exit;
}