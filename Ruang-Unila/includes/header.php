<?php
/**
 * Header Template - Ruang Unila
 * 
 * Template header dengan navigasi utama
 * berdasarkan design dari Figma.
 * 
 * @package RuangUnila
 * @version 1.0.0
 */

// Cegah akses langsung
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Include konfigurasi
require_once APP_ROOT . '/config/constants.php';
require_once APP_ROOT . '/config/session.php';

// Include classes
require_once APP_ROOT . '/classes/User.php';

// Inisialisasi session
$session = Session::getInstance();

// Tentukan halaman aktif
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
    
    <!-- Google Fonts - Manrope -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Google Fonts - Newsreader untuk Heading -->
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-container">
            <!-- Logo -->
            <a href="<?= url() ?>" class="navbar-brand">
                Ruang Unila
            </a>
            
            <!-- Navigation -->
            <div class="navbar-nav">
                <a href="<?= url() ?>" 
                   class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">
                    Beranda
                </a>
                <a href="<?= url('modules/news/list.php') ?>" 
                   class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/news/') !== false ? 'active' : '' ?>">
                    Berita
                </a>
                <a href="<?= url('modules/events/list.php') ?>" 
                   class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/events/') !== false ? 'active' : '' ?>">
                    Event
                </a>
            </div>
            
            <!-- Search Bar -->
            <div class="navbar-search">
                <input type="text" placeholder="Cari berita..." class="navbar-search-input">
                <svg class="navbar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
            </div>
            
            <!-- Auth Buttons -->
            <div style="display: flex; align-items: center; gap: 16px;">
                <?php if ($session->isLoggedIn()): ?>
                    <?php if ($session->getUserRole() === ROLE_ADMIN): ?>
                        <a href="<?= url('modules/admin/dashboard.php') ?>" class="btn btn-primary btn-sm">
                            Admin Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="<?= url('modules/profile/index.php') ?>" class="btn btn-secondary btn-sm">
                        Profil
                    </a>
                    <a href="<?= url('modules/auth/logout.php') ?>" class="btn btn-outline btn-sm">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="<?= url('modules/auth/register.php') ?>" class="btn btn-secondary btn-sm">
                        Daftar
                    </a>
                    <a href="<?= url('modules/auth/login.php') ?>" class="btn btn-primary btn-sm">
                        Masuk
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="main-content"></div>