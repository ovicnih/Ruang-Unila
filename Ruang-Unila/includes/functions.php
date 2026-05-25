<?php
/**
 * Utility Functions - Ruang Unila
 * 
 * File ini berisi fungsi-fungsi utility yang digunakan
 * dalam sistem web Ruang Unila.
 * 
 * @package RuangUnila
 * @version 1.0.0
 */

// Cegah akses langsung
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// ============================================================
// FUNGSI SANITASI & VALIDASI
// ============================================================

/**
 * Membersihkan string dari karakter berbahaya
 * 
 * @param string $input String yang akan dibersihkan
 * @return string String yang sudah dibersihkan
 */
function cleanInput(string $input): string {
    // Trim whitespace
    $input = trim($input);
    
    // Strip tags
    $input = strip_tags($input);
    
    // Convert special characters
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    return $input;
}

/**
 * Memvalidasi format email
 * 
 * @param string $email Email yang akan divalidasi
 * @return bool True jika valid
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Memvalidasi panjang string
 * 
 * @param string $input String yang akan divalidasi
 * @param int $min Panjang minimum
 * @param int $max Panjang maksimum
 * @return bool True jika valid
 */
function isValidLength(string $input, int $min, int $max): bool {
    $length = mb_strlen($input, 'UTF-8');
    return $length >= $min && $length <= $max;
}

/**
 * Memvalidasi username (huruf, angka, underscore)
 * 
 * @param string $username Username yang akan divalidasi
 * @return bool True jika valid
 */
function isValidUsername(string $username): bool {
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username) === 1;
}

/**
 * Memvalidasi tanggal format Y-m-d
 * 
 * @param string $date Tanggal yang akan divalidasi
 * @return bool True jika valid
 */
function isValidDate(string $date): bool {
    $parsed = date_parse_from_format('Y-m-d', $date);
    return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
}

// ============================================================
// FUNGSI PASSWORD
// ============================================================

/**
 * Menghash password dengan bcrypt
 * 
 * @param string $password Password plaintext
 * @return string Password yang sudah dihash
 */
function hashPassword(string $password): string {
    return password_hash($password, HASH_ALGO, ['cost' => HASH_COST]);
}

/**
 * Memverifikasi password
 * 
 * @param string $password Password plaintext
 * @param string $hash Hash password dari database
 * @return bool True jika cocok
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Generate string acak untuk token
 * 
 * @param int $length Panjang string
 * @return string String acak
 */
function generateRandomString(int $length = 32): string {
    return bin2hex(random_bytes($length / 2));
}

// ============================================================
// FUNGSI REDIRECT & URL
// ============================================================

/**
 * Redirect ke halaman tertentu
 * 
 * @param string $path Path tujuan (relatif) atau URL lengkap
 */
function redirect(string $path): void {
    // Jika path sudah URL lengkap, gunakan langsung
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    }
    exit;
}

/**
 * Membuat URL lengkap dari path
 * 
 * @param string $path Path relatif atau URL lengkap
 * @return string URL lengkap
 */
function url(string $path = ''): string {
    // Jika path sudah URL lengkap, kembalikan langsung
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Mengecek apakah halaman aktif
 * 
 * @param string $page Path halaman
 * @return bool True jika aktif
 */
function isActivePage(string $page): bool {
    $currentPage = $_GET['p'] ?? 'home';
    return $currentPage === $page;
}

// ============================================================
// FUNGSI TANGGAL & WAKTU
// ============================================================

/**
 * Format tanggal ke format Indonesia atau custom
 * 
 * @param string $date Tanggal format Y-m-d atau Y-m-d H:i:s
 * @param string|null $format Format output (jika null, gunakan format Indonesia)
 * @return string Tanggal yang diformat
 */
function formatDate(string $date, ?string $format = null): string {
    $timestamp = strtotime($date);
    
    // Jika format custom diberikan
    if ($format !== null) {
        return date($format, $timestamp);
    }
    
    // Format Indonesia default
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$day $month $year";
}

/**
 * Mendapatkan waktu relatif (misal: "2 jam yang lalu")
 * 
 * @param string $datetime Datetime format Y-m-d H:i:s
 * @return string Waktu relatif
 */
function timeAgo(string $datetime): string {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' tahun yang lalu';
    if ($diff->m > 0) return $diff->m . ' bulan yang lalu';
    if ($diff->d > 0) return $diff->d . ' hari yang lalu';
    if ($diff->h > 0) return $diff->h . ' jam yang lalu';
    if ($diff->i > 0) return $diff->i . ' menit yang lalu';
    
    return 'Baru saja';
}

// ============================================================
// FUNGSI STRING
// ============================================================

/**
 * Memotong string dengan ellipsis
 * 
 * @param string $text Teks yang akan dipotong
 * @param int $length Panjang maksimum
 * @return string Teks yang sudah dipotong
 */
function truncateText(string $text, int $length = 150): string {
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length, 'UTF-8') . '...';
}

/**
 * Membuat slug dari string
 * 
 * @param string $text Teks yang akan dijadikan slug
 * @return string Slug
 */
function createSlug(string $text): string {
    // Convert to lowercase
    $slug = strtolower($text);
    
    // Replace non-alphanumeric with hyphen
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    
    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * Mendapatkan inisial dari nama
 * 
 * @param string $name Nama lengkap
 * @return string Inisial (2 huruf)
 */
function getInitials(string $name): string {
    $words = explode(' ', trim($name));
    $initials = '';
    
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= mb_substr($word, 0, 1, 'UTF-8');
        }
        if (mb_strlen($initials, 'UTF-8') >= 2) break;
    }
    
    return strtoupper($initials);
}

// ============================================================
// FUNGSI FILE & UPLOAD
// ============================================================

/**
 * Mengecek apakah ekstensi file diizinkan
 * 
 * @param string $filename Nama file
 * @param array $allowedExtensions Ekstensi yang diizinkan
 * @return bool True jika diizinkan
 */
function isAllowedExtension(string $filename, array $allowedExtensions): bool {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $allowedExtensions);
}

/**
 * Mengecek apakah ukuran file diizinkan
 * 
 * @param int $fileSize Ukuran file dalam byte
 * @param int $maxSize Ukuran maksimal dalam byte
 * @return bool True jika diizinkan
 */
function isAllowedFileSize(int $fileSize, int $maxSize): bool {
    return $fileSize <= $maxSize;
}

/**
 * Generate nama file unik
 * 
 * @param string $originalName Nama asli file
 * @return string Nama file unik
 */
function generateUniqueFilename(string $originalName): string {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Mendapatkan path upload lengkap
 * 
 * @param string $type Tipe upload (profile, news, event)
 * @return string Path upload
 */
function getUploadPath(string $type = 'general'): string {
    $paths = [
        'profile' => UPLOAD_PROFILE,
        'news' => UPLOAD_NEWS,
        'event' => UPLOAD_EVENT,
        'general' => UPLOAD_DIR
    ];
    
    $path = $paths[$type] ?? UPLOAD_DIR;
    $fullPath = APP_ROOT . '/' . $path;
    
    // Buat direktori jika belum ada
    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0755, true);
    }
    
    return $fullPath;
}

/**
 * Menangani upload file gambar
 * 
 * @param array $file Array $_FILES['field']
 * @param string $type Tipe upload (profile, news, event)
 * @param array $allowedTypes Tipe MIME yang diizinkan
 * @param int $maxSize Ukuran maksimal dalam byte (default: 2MB)
 * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
 */
function handleImageUpload(array $file, string $type = 'general', array $allowedTypes = [], int $maxSize = 2097152): array {
    // Default allowed types
    if (empty($allowedTypes)) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    }
    
    // Cek apakah ada file yang diupload
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [
            'success' => false,
            'filename' => null,
            'error' => 'Tidak ada file yang diupload'
        ];
    }
    
    // Cek error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas yang ditentukan php.ini',
            UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas yang ditentukan form',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION => 'Upload terhenti karena extension PHP'
        ];
        
        $errorMessage = $errorMessages[$file['error']] ?? 'Error upload tidak diketahui';
        return [
            'success' => false,
            'filename' => null,
            'error' => $errorMessage
        ];
    }
    
    // Cek ekstensi file
    if (!isAllowedExtension($file['name'], ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return [
            'success' => false,
            'filename' => null,
            'error' => 'Tipe file tidak diizinkan. Hanya JPG, PNG, GIF, dan WebP yang diperbolehkan'
        ];
    }
    
    // Cek tipe MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return [
            'success' => false,
            'filename' => null,
            'error' => 'Tipe file tidak valid'
        ];
    }
    
    // Cek ukuran file
    if (!isAllowedFileSize($file['size'], $maxSize)) {
        $maxSizeMB = round($maxSize / 1024 / 1024, 2);
        return [
            'success' => false,
            'filename' => null,
            'error' => "Ukuran file melebihi batas {$maxSizeMB}MB"
        ];
    }
    
    // Generate nama file unik
    $filename = generateUniqueFilename($file['name']);
    
    // Dapatkan path upload
    $uploadPath = getUploadPath($type);
    $destination = $uploadPath . '/' . $filename;
    
    // Pindahkan file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => false,
            'filename' => null,
            'error' => 'Gagal mengupload file'
        ];
    }
    
    // Set permission file
    chmod($destination, 0644);
    
    return [
        'success' => true,
        'filename' => $filename,
        'error' => null
    ];
}

// ============================================================
// FUNGSI PAGINATION
// ============================================================

/**
 * Menghitung offset untuk pagination
 * 
 * @param int $page Halaman saat ini
 * @param int $perPage Jumlah item per halaman
 * @return int Offset
 */
function getPaginationOffset(int $page, int $perPage): int {
    return ($page - 1) * $perPage;
}

/**
 * Menghitung total halaman
 * 
 * @param int $totalItems Total item
 * @param int $perPage Jumlah item per halaman
 * @return int Total halaman
 */
function getTotalPages(int $totalItems, int $perPage): int {
    return (int)ceil($totalItems / $perPage);
}

/**
 * Mendapatkan parameter halaman dari URL
 * 
 * @return int Halaman saat ini
 */
function getCurrentPage(): int {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    return max(1, $page);
}

// ============================================================
// FUNGSI TAMPILAN (VIEW)
// ============================================================

/**
 * Menampilkan pesan flash
 */
function displayFlashMessage(): void {
    $session = Session::getInstance();
    
    $types = ['success', 'error', 'warning', 'info'];
    foreach ($types as $type) {
        $message = $session->getFlash($type);
        if ($message) {
            echo '<div class="alert alert-' . htmlspecialchars($type) . '">' . 
                 htmlspecialchars($message) . '</div>';
        }
    }
}

/**
 * Menghitung estimasi waktu baca
 * 
 * @param string $content Konten berita
 * @return string Estimasi waktu baca
 */
function calculateReadTime(string $content): string {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = max(1, ceil($wordCount / 200)); // 200 kata per menit
    return $minutes . ' menit baca';
}

/**
 * Format angka dengan separator ribuan
 * 
 * @param int $number Angka yang akan diformat
 * @return string Angka yang sudah diformat
 */
function formatNumber(int $number): string {
    return number_format($number, 0, ',', '.');
}

// ============================================================
// FUNGSI DEBUG
// ============================================================

/**
 * Dump dan die untuk debugging
 * 
 * @param mixed $data Data yang akan ditampilkan
 */
function dd(mixed $data): void {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die;
}

/**
 * Menampilkan data dengan format rapi
 * 
 * @param mixed $data Data yang akan ditampilkan
 */
function prettyPrint(mixed $data): void {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

// ============================================================
// FUNGSI WRAPPER SESSION (untuk kemudahan di modul)
// ============================================================

/**
 * Sanitasi input (alias untuk cleanInput)
 * 
 * @param string $input String yang akan dibersihkan
 * @return string String yang sudah dibersihkan
 */
function sanitize(string $input): string {
    return cleanInput($input);
}

/**
 * Mengecek apakah user sudah login, redirect ke login jika belum
 */
function requireLogin(): void {
    $session = Session::getInstance();
    if (!$session->isLoggedIn()) {
        setFlashMessage('error', 'Silakan login terlebih dahulu.');
        redirect('modules/auth/login.php');
    }
}

/**
 * Mengecek apakah user memiliki role tertentu
 * 
 * @param string $role Role yang dicek
 * @return bool True jika memiliki role
 */
function hasRole(string $role): bool {
    $session = Session::getInstance();
    return $session->hasRole($role);
}

/**
 * Set flash message
 * 
 * @param string $type Tipe pesan (success, error, warning, info)
 * @param string $message Pesan
 */
function setFlashMessage(string $type, string $message): void {
    $session = Session::getInstance();
    $session->setFlash($type, $message);
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCSRFToken(): string {
    $session = Session::getInstance();
    return $session->generateCsrfToken();
}

/**
 * Verifikasi CSRF token
 * 
 * @param string $token Token dari form
 * @return bool True jika valid
 */
function verifyCSRFToken(string $token): bool {
    $session = Session::getInstance();
    return $session->verifyCsrfToken($token);
}

/**
 * Mendapatkan user ID yang sedang login
 * 
 * @return int|null User ID atau null
 */
function getUserId(): ?int {
    $session = Session::getInstance();
    return $session->getUserId();
}

/**
 * Format tanggal dengan format custom
 * 
 * @param string $date Tanggal format Y-m-d atau Y-m-d H:i:s
 * @param string|null $format Format output (default: Indonesia)
 * @return string Tanggal yang diformat
 */
function formatDateCustom(string $date, ?string $format = null): string {
    if ($format === null) {
        return formatDate($date);
    }
    
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

// Auth guard — redirect ke login jika belum login
if (class_exists('Session') && PHP_SAPI !== 'cli') {
    $currentPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $isAuthPage = strpos($currentPath, '/auth/') !== false;
    $isApiPath = strpos($currentPath, '/api/') !== false;
    if (!$isAuthPage && !$isApiPath) {
        requireLogin();
    }
}
?>
