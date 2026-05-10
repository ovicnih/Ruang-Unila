<?php
/**
 * Sidebar Template - Ruang Unila
 * 
 * Sidebar dengan navigasi sesuai role user
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

// Inisialisasi session
$session = Session::getInstance();

// Tentukan halaman aktif
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Tentukan modul aktif
$currentModule = '';
$requestUri = $_SERVER['REQUEST_URI'];
if (strpos($requestUri, '/news/') !== false) {
    $currentModule = 'news';
} elseif (strpos($requestUri, '/events/') !== false) {
    $currentModule = 'events';
} elseif (strpos($requestUri, '/profile/') !== false) {
    $currentModule = 'profile';
} elseif (strpos($requestUri, '/admin/') !== false) {
    $currentModule = 'admin';
}
?>
<!-- Sidebar -->
<div class="sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="4" y="4" width="24" height="24" rx="2"/>
                    <path d="M10 12h12M10 16h8M10 20h10"/>
                </svg>
            </div>
            <span class="sidebar-logo-text">Ruang Unila</span>
        </div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar-nav">
        <!-- Main Navigation -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Navigasi Utama</div>
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item <?= $currentPage === 'index' ? 'active' : '' ?>">
                    <a href="<?= url() ?>" class="sidebar-menu-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <span>Beranda</span>
                    </a>
                </li>
                <li class="sidebar-menu-item <?= $currentModule === 'news' ? 'active' : '' ?>">
                    <a href="<?= url('modules/news/list.php') ?>" class="sidebar-menu-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <span>Berita</span>
                    </a>
                </li>
                <li class="sidebar-menu-item <?= $currentModule === 'events' ? 'active' : '' ?>">
                    <a href="<?= url('modules/events/list.php') ?>" class="sidebar-menu-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span>Event</span>
                    </a>
                </li>
            </ul>
        </div>

        <?php if ($session->isLoggedIn()): ?>
        <!-- User Section -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Akun Saya</div>
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item <?= $currentModule === 'profile' ? 'active' : '' ?>">
                    <a href="<?= url('modules/profile/index.php') ?>" class="sidebar-menu-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Profil Saya</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="<?= url('modules/profile/edit.php') ?>" class="sidebar-menu-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        <span>Edit Profil</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="<?= url('modules/profile/password.php') ?>" class="sidebar-menu-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>Ubah Password</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="<?= url('modules/profile/photo.php') ?>" class="sidebar-menu-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <span>Foto Profil</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Organizer Section (Hanya untuk Organisasi/Admin) -->
        <?php if ($session->getUserRole() === ROLE_ORGANISATION || $session->getUserRole() === ROLE_ADMIN): ?>
        <div class="sidebar-section">
            <div class="sidebar-section-title">Panel Organisasi</div>
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="<?= url('modules/news/create.php') ?>" class="sidebar-menu-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Buat Berita</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="<?= url('modules/news/manage.php') ?>" class="sidebar-menu-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        <span>Kelola Berita</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="<?= url('modules/events/create.php') ?>" class="sidebar-menu-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="12" y1="10" x2="12" y2="16"></line>
                            <line x1="9" y1="13" x2="15" y2="13"></line>
                        </svg>
                        <span>Buat Event</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="<?= url('modules/events/admin.php') ?>" class="sidebar-menu-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span>Kelola Event</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Admin Section (Hanya untuk Admin) -->
        <?php if ($session->getUserRole() === ROLE_ADMIN): ?>
        <div class="sidebar-section">
            <div class="sidebar-section-title">Panel Admin</div>
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="<?= url('modules/events/admin_payments.php') ?>" class="sidebar-menu-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        <span>Verifikasi Pembayaran</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Logout -->
        <div class="sidebar-section">
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="<?= url('modules/auth/logout.php') ?>" class="sidebar-menu-link logout-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php else: ?>
        <!-- Guest Section -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Masuk Sistem</div>
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="<?= url('modules/auth/login.php') ?>" class="sidebar-menu-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10 17 15 12 10 7"></polyline>
                            <line x1="15" y1="12" x2="3" y2="12"></line>
                        </svg>
                        <span>Masuk</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="<?= url('modules/auth/register.php') ?>" class="sidebar-menu-link">
                        <svg class="sidebar-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                        <span>Daftar</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <?php if ($session->isLoggedIn()): ?>
                <?php $userData = $session->getUserData(); ?>
                <div class="sidebar-user-avatar">
                    <?php if ($userData['profile_photo']): ?>
                        <img src="<?= url('assets/images/uploads/profile/' . $userData['profile_photo']) ?>" 
                             alt="Foto Profil"
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <div class="sidebar-user-avatar-placeholder">
                            <?= strtoupper(substr($userData['full_name'] ?? 'U', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= htmlspecialchars($userData['full_name'] ?? 'User') ?></div>
                    <div class="sidebar-user-role"><?= ucfirst($userData['role'] ?? 'Guest') ?></div>
                </div>
            <?php else: ?>
                <div class="sidebar-user-avatar">
                    <div class="sidebar-user-avatar-placeholder">G</div>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name">Guest</div>
                    <div class="sidebar-user-role">Tamu</div>
                </div>
            <?php endif; ?>
        </div>
        <div class="sidebar-version">
            v<?= APP_VERSION ?? '1.0.0' ?>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
