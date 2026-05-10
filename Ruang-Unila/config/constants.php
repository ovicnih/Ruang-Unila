<?php
/**
 * Konstanta Aplikasi - Ruang Unila
 * 
 * File ini berisi semua konstanta yang digunakan
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
// KONFIGURASI APLIKASI
// ============================================================

/** Nama sistem web */
const APP_NAME = 'Ruang Unila';

/** Versi sistem */
const APP_VERSION = '1.0.0';

/** Environment (development atau production) */
const APP_ENV = 'development';

// ============================================================
// SECURITY HEADERS
// ============================================================
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self' fonts.googleapis.com fonts.gstatic.com; img-src 'self' data:; style-src 'self' 'unsafe-inline' fonts.googleapis.com; script-src 'self' 'unsafe-inline';");


/** Root path sistem (sudah didefinisikan di index.php) */
// const APP_ROOT = dirname(__DIR__);

/** Base URL sistem */
const APP_URL = 'http://localhost/WEBUNILA/Ruang-Unila';

// ============================================================
// KONFIGURASI DATABASE
// ============================================================

/** Host database */
const DB_HOST = 'localhost:3306';

/** Port database */
const DB_PORT = 3306;

/** Nama database */
const DB_NAME = 'RuangUnila';

/** Username database */
const DB_USER = 'root';

/** Password database */
const DB_PASS = '';

/** Charset database */
const DB_CHARSET = 'utf8mb4';

// ============================================================
// KONFIGURASI SESSION
// ============================================================

/** Nama session */
const SESSION_NAME = 'ruangunila_session';

/** Waktu timeout session (2 jam dalam detik) */
const SESSION_LIFETIME = 7200;

/** Path session */
const SESSION_PATH = '/';

// ============================================================
// KONFIGURASI SECURITY
// ============================================================

/** Algoritma password hashing */
const HASH_ALGO = PASSWORD_BCRYPT;

/** Cost factor untuk bcrypt */
const HASH_COST = 12;

/** Nama token CSRF */
const CSRF_TOKEN_NAME = 'ruangunila_csrf_token';

/** Panjang token acak */
const TOKEN_LENGTH = 32;

// ============================================================
// KONFIGURASI FILE UPLOAD
// ============================================================

/** Direktori upload relatif terhadap root */
const UPLOAD_DIR = 'assets/images/uploads/';

/** Direktori foto profil */
const UPLOAD_PROFILE = 'assets/images/uploads/profile/';

/** Direktori gambar berita */
const UPLOAD_NEWS = 'assets/images/uploads/news/';

/** Direktori gambar event */
const UPLOAD_EVENT = 'assets/images/uploads/events/';

/** Ukuran maksimal file (2MB dalam byte) */
const MAX_FILE_SIZE = 2 * 1024 * 1024;

/** Ekstensi file gambar yang diizinkan */
const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];

/** Tipe MIME gambar yang diizinkan */
const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

// ============================================================
// KONFIGURASI PAGINATION
// ============================================================

/** Jumlah item per halaman */
const ITEMS_PER_PAGE = 10;

/** Jumlah item per halaman untuk admin */
const ADMIN_ITEMS_PER_PAGE = 20;

// ============================================================
// ROLE PENGGUNA
// ============================================================

/** Role: Admin */
const ROLE_ADMIN = 'admin';

/** Role: Organisasi */
const ROLE_ORGANIZATION = 'organisasi';
const ROLE_ORGANISATION = 'organisasi'; // British spelling alias

/** Role: Mahasiswa */
const ROLE_STUDENT = 'mahasiswa';

// ============================================================
// STATUS PENGGUNA
// ============================================================

/** Status: Aktif */
const STATUS_ACTIVE = 'active';

/** Status: Nonaktif */
const STATUS_INACTIVE = 'inactive';

// ============================================================
// STATUS BERITA
// ============================================================

/** Status berita: Draft */
const NEWS_STATUS_DRAFT = 'draft';

/** Status berita: Pending (menunggu validasi) */
const NEWS_STATUS_PENDING = 'pending';

/** Status berita: Published (terbit) */
const NEWS_STATUS_PUBLISHED = 'published';

/** Status berita: Rejected (ditolak) */
const NEWS_STATUS_REJECTED = 'rejected';

// ============================================================
// STATUS EVENT
// ============================================================

/** Status event: Akan datang */
const EVENT_STATUS_UPCOMING = 'upcoming';

/** Status event: Sedang berlangsung */
const EVENT_STATUS_ONGOING = 'ongoing';

/** Status event: Selesai */
const EVENT_STATUS_COMPLETED = 'completed';

/** Status event: Dibatalkan */
const EVENT_STATUS_CANCELLED = 'cancelled';

// ============================================================
// STATUS PEMBAYARAN EVENT
// ============================================================

/** Status pembayaran: Pending */
const PAYMENT_STATUS_PENDING = 'pending';

/** Status pembayaran: Terverifikasi */
const PAYMENT_STATUS_VERIFIED = 'verified';

/** Status pembayaran: Ditolak */
const PAYMENT_STATUS_REJECTED = 'rejected';

// ============================================================
// 6 KATEGORI BERITA (WAJIB)
// ============================================================

/** Array kategori berita */
const NEWS_CATEGORIES = [
    'populer'   => 'Populer',
    'kampus'    => 'Kampus',
    'ukm'       => 'UKM',
    'akademik'  => 'Akademik',
    'event'     => 'Event',
    'tren'      => 'Tren'
];

/** Slug kategori */
const NEWS_CATEGORY_SLUGS = [
    'populer', 'kampus', 'ukm', 'akademik', 'event', 'tren'
];

// ============================================================
// KONFIGURASI UI/UX
// ============================================================

/** Font utama dari Google Fonts */
const FONT_FAMILY = 'Manrope, sans-serif';

/** Warna primary */
const COLOR_PRIMARY = '#9E0000';

/** Warna secondary */
const COLOR_SECONDARY = '#CC0000';

/** Warna background */
const COLOR_BACKGROUND = '#F8F9FA';

/** Warna teks utama */
const COLOR_TEXT_PRIMARY = '#191C1D';

/** Warna teks sekunder */
const COLOR_TEXT_SECONDARY = '#5E3F3A';

/** Warna border */
const COLOR_BORDER = '#E8BDB6';

/** Lebar konten utama (dalam pixel) */
const CONTENT_WIDTH = 1280;

/** Tinggi navigation bar (dalam pixel) */
const NAVBAR_HEIGHT = 72;

/** Border radius default (dalam pixel) */
const BORDER_RADIUS = 8;

// ============================================================
// BREAKPOINTS RESPONSIVE
// ============================================================

/** Breakpoint: Mobile */
const BREAKPOINT_MOBILE = 768;

/** Breakpoint: Tablet */
const BREAKPOINT_TABLET = 1024;

/** Breakpoint: Desktop */
const BREAKPOINT_DESKTOP = 1280;

// ============================================================
// PESAN ERROR/SUCCESS
// ============================================================

/** Pesan error umum */
const MSG_ERROR_GENERIC = 'Terjadi kesalahan. Silakan coba lagi.';

/** Pesan error login */
const MSG_ERROR_LOGIN = 'Email atau kata sandi salah.';

/** Pesan error akses */
const MSG_ERROR_ACCESS = 'Anda tidak memiliki akses ke halaman ini.';

/** Pesan success login */
const MSG_SUCCESS_LOGIN = 'Login berhasil. Selamat datang!';

/** Pesan success register */
const MSG_SUCCESS_REGISTER = 'Registrasi berhasil. Silakan login.';

/** Pesan success logout */
const MSG_SUCCESS_LOGOUT = 'Logout berhasil. Sampai jumpa!';

/** Pesan success update */
const MSG_SUCCESS_UPDATE = 'Data berhasil diperbarui.';

/** Pesan success delete */
const MSG_SUCCESS_DELETE = 'Data berhasil dihapus.';

/** Pesan success upload */
const MSG_SUCCESS_UPLOAD = 'File berhasil diupload.';
