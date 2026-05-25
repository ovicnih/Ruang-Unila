<?php
/**
 * Modul Tiket & Tagihan Event - Ruang Unila
 * 
 * Menampilkan tiket resmi (jika sudah paid/verified) atau status menunggu verifikasi (pending_verification),
 * serta instruksi pembayaran (jika unpaid) dan penolakan (jika rejected).
 * Dilengkapi dengan fitur Cetak (Print/PDF) dan terintegrasi dengan sistem pembayaran.
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

// Start output buffering
ob_start();

// Include konfigurasi
require_once APP_ROOT . '/config/constants.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/includes/functions.php';

// Include classes
require_once APP_ROOT . '/classes/User.php';
require_once APP_ROOT . '/classes/Event.php';

// Require login
requireLogin();

// Inisialisasi
$session = Session::getInstance();
$userId = $session->getUserId();
$userRole = $session->getUserRole();
$db = Database::getInstance();

// Validasi parameter ID (Registration ID)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID Pendaftaran tidak valid.');
    redirect('modules/profile/my-registrations.php');
}

$registrationId = (int) $_GET['id'];

// Ambil data pendaftaran beserta detail event dan user
$sql = "SELECT er.*, 
               e.title as event_title, e.event_date, e.location, e.fee, e.description, e.image, 
               e.bank_name, e.bank_account_number, e.bank_account_name, e.organizer_id,
               u.full_name as participant_name_db, u.email as participant_email_db, u.phone as participant_phone
        FROM event_registrations er
        INNER JOIN events e ON er.event_id = e.event_id
        INNER JOIN users u ON er.user_id = u.user_id
        WHERE er.registration_id = :registration_id";

$reg = $db->fetchOne($sql, [':registration_id' => $registrationId]);

if (!$reg) {
    setFlashMessage('error', 'Data pendaftaran tidak ditemukan.');
    redirect('modules/profile/my-registrations.php');
}

// Cek hak akses: Hanya pemilik pendaftaran, organizer event, atau admin yang boleh melihat tiket ini
if ($userRole !== ROLE_ADMIN && $reg['user_id'] !== $userId && $reg['organizer_id'] !== $userId) {
    setFlashMessage('error', 'Anda tidak memiliki hak akses untuk melihat tiket ini.');
    redirect('modules/profile/my-registrations.php');
}

$pageTitle = 'Tiket & Status: ' . htmlspecialchars($reg['event_title']);

// Normalisasi payment_status untuk kemudahan mapping
$paymentStatus = $reg['payment_status'];
if ($paymentStatus === 'paid' || $paymentStatus === 'verified') {
    $statusKey = 'verified';
} elseif ($paymentStatus === 'pending_verification' || $paymentStatus === 'pending') {
    $statusKey = 'pending';
} elseif ($paymentStatus === 'rejected') {
    $statusKey = 'rejected';
} else {
    $statusKey = 'unpaid';
}

// Konfigurasi Status & Styling Badge (UI/UX Pro Max: Menggunakan SVG Icons murni, tanpa Emojis)
$statusConfig = [
    'verified' => [
        'label' => 'TIKET RESMI (VERIFIED)',
        'badge_bg' => 'rgba(158, 0, 0, 0.08)',
        'badge_color' => '#9E0000',
        'border_color' => '#9E0000',
        'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
    ],
    'pending' => [
        'label' => 'MENUNGGU VERIFIKASI',
        'badge_bg' => 'rgba(245, 158, 11, 0.12)',
        'badge_color' => '#F59E0B',
        'border_color' => '#F59E0B',
        'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 15 15"></polyline></svg>'
    ],
    'unpaid' => [
        'label' => 'BELUM DIBAYAR (UNPAID)',
        'badge_bg' => 'rgba(239, 68, 68, 0.12)',
        'badge_color' => '#EF4444',
        'border_color' => '#EF4444',
        'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
    ],
    'rejected' => [
        'label' => 'PEMBAYARAN DITOLAK',
        'badge_bg' => 'rgba(239, 68, 68, 0.15)',
        'badge_color' => '#DC2626',
        'border_color' => '#DC2626',
        'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
    ]
];

$currentStatus = $statusConfig[$statusKey];

// Nama dan Email peserta (prioritaskan dari tabel event_registrations jika ada, jika tidak dari tabel users)
$pName = !empty($reg['participant_name']) ? $reg['participant_name'] : $reg['participant_name_db'];
$pEmail = !empty($reg['participant_email']) ? $reg['participant_email'] : $reg['participant_email_db'];

// Include header
include APP_ROOT . '/includes/header.php';
ob_end_flush();
?>

<!-- Khusus Styling Tiket & Print (UI/UX Pro Max Premium Specification) -->
<style>
    :root {
        --ticket-bg: #FFFFFF;
        --ticket-border: #E2E8F0;
        --ticket-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --ticket-shadow-hover: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .ticket-container {
        max-width: 850px;
        margin: 40px auto 80px;
        padding: 0 24px;
    }

    .ticket-wrapper {
        background: var(--ticket-bg);
        border-radius: 24px;
        border: 2px solid <?= $currentStatus['border_color'] ?>;
        box-shadow: var(--ticket-shadow);
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .ticket-wrapper:hover {
        box-shadow: var(--ticket-shadow-hover);
        transform: translateY(-4px);
    }

    /* Efek Potongan Tiket (Perforated cutouts) */
    .ticket-divider {
        position: relative;
        height: 32px;
        background: var(--ticket-bg);
        display: flex;
        align-items: center;
    }

    .ticket-divider::before,
    .ticket-divider::after {
        content: '';
        position: absolute;
        width: 32px;
        height: 32px;
        background: var(--color-background);
        border-radius: 50%;
        border: 2px solid <?= $currentStatus['border_color'] ?>;
        transition: border-color 0.3s ease;
    }

    .ticket-divider::before {
        left: -18px;
        border-right-color: transparent;
        border-top-color: transparent;
        transform: rotate(45deg);
    }

    .ticket-divider::after {
        right: -18px;
        border-left-color: transparent;
        border-bottom-color: transparent;
        transform: rotate(45deg);
    }

    .ticket-divider-line {
        width: 100%;
        border-top: 2px dashed <?= $currentStatus['border_color'] ?>;
        margin: 0 20px;
    }

    .ticket-header {
        padding: 36px 40px 28px;
        background: linear-gradient(180deg, rgba(255,255,255,1) 0%, rgba(248,250,252,0.6) 100%);
    }

    .ticket-body {
        padding: 28px 40px 36px;
    }

    .ticket-footer {
        padding: 36px 40px;
        background: #F8FAFC;
        border-top: 1px solid var(--ticket-border);
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .info-group {
        margin-bottom: 20px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .info-group-icon {
        padding: 10px;
        background: #F1F5F9;
        border-radius: 12px;
        color: var(--color-primary);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .info-content {
        flex: 1;
    }

    .info-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--color-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 16px;
        font-weight: 700;
        color: var(--color-text-primary);
    }

    .qr-box {
        width: 160px;
        height: 160px;
        padding: 12px;
        background: white;
        border: 2px solid var(--color-border-light);
        border-radius: 18px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        transition: transform 0.3s ease;
    }

    .qr-box:hover {
        transform: scale(1.03);
    }

    .btn-icon {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    /* Print & PDF Export Styles */
    @media print {
        body {
            background: white !important;
            color: black !important;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
        .navbar, footer, .no-print {
            display: none !important;
        }
        .ticket-container {
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100% !important;
        }
        .ticket-wrapper {
            border: 2px solid #000 !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }
        .ticket-divider::before, .ticket-divider::after {
            background: white !important;
            border-color: #000 !important;
        }
        .ticket-divider-line {
            border-top-color: #000 !important;
        }
        .info-group-icon {
            border: 1px solid #000 !important;
            background: transparent !important;
            color: #000 !important;
        }
    }
</style>

<div class="ticket-container">
    <!-- Top Action Bar -->
    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
        <a href="<?= url('modules/profile/my-registrations.php') ?>" class="btn btn-secondary btn-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali ke Daftar Pendaftaran
        </a>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <?php if ($statusKey === 'unpaid' || $statusKey === 'pending' || $statusKey === 'rejected'): ?>
                <a href="<?= url('modules/events/payment.php?reg=' . urlencode($reg['registration_number'])) ?>" class="btn btn-primary btn-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    Upload Bukti Pembayaran
                </a>
            <?php endif; ?>
            <?php if ($statusKey === 'verified'): ?>
                <button onclick="window.print()" class="btn btn-outline btn-icon" style="border-color: var(--color-primary); color: var(--color-primary);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Cetak Tiket
                </button>
                <button onclick="window.print()" class="btn btn-primary btn-icon" style="background: var(--color-primary);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Download PDF
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ticket Wrapper -->
    <div class="ticket-wrapper">
        
        <!-- Bagian Atas: Header Tiket -->
        <div class="ticket-header">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                <div>
                    <span style="padding: 8px 16px; background: <?= $currentStatus['badge_bg'] ?>; color: <?= $currentStatus['badge_color'] ?>; border-radius: 99px; font-size: 12px; font-weight: 800; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <?= $currentStatus['icon'] ?> <?= $currentStatus['label'] ?>
                    </span>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 12px; color: var(--color-text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">No. Registrasi</div>
                    <div style="font-family: var(--font-primary); font-size: 20px; font-weight: 800; color: var(--color-primary);">
                        <?= htmlspecialchars($reg['registration_number']) ?>
                    </div>
                </div>
            </div>

            <h1 style="font-family: var(--font-heading); font-size: 38px; color: var(--color-text-primary); margin-bottom: 14px; line-height: 1.25; font-weight: 700;">
                <?= htmlspecialchars($reg['event_title']) ?>
            </h1>
            <p style="color: var(--color-text-secondary); font-size: 15px; margin: 0; display: flex; align-items: center; gap: 8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-primary);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <?= htmlspecialchars($reg['location']) ?>
            </p>
        </div>

        <!-- Pemisah Tiket (Perforasi) -->
        <div class="ticket-divider">
            <div class="ticket-divider-line"></div>
        </div>

        <!-- Bagian Tengah: Detail Peserta & Event -->
        <div class="ticket-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 28px; margin-bottom: 32px;">
                <div class="info-group">
                    <div class="info-group-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Nama Peserta</div>
                        <div class="info-value"><?= htmlspecialchars($pName) ?></div>
                        <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 2px;"><?= htmlspecialchars($pEmail) ?></div>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-group-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Tanggal Event</div>
                        <div class="info-value"><?= formatDate($reg['event_date'], 'l, d F Y') ?></div>
                        <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 2px;">Waktu: <?= formatDate($reg['event_date'], 'H:i') ?> WIB</div>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-group-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Kategori Biaya</div>
                        <div class="info-value" style="color: var(--color-primary);">
                            <?= $reg['fee'] > 0 ? 'Rp ' . formatNumber($reg['fee']) : 'GRATIS (FREE PASS)' ?>
                        </div>
                        <div style="font-size: 13px; color: var(--color-text-muted); margin-top: 2px;">Status: <?= strtoupper($statusKey) ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($reg['notes'])): ?>
                <div style="padding-top: 28px; border-top: 1px solid var(--color-border-light); display: flex; gap: 12px;">
                    <div class="info-group-icon" style="align-self: flex-start;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Catatan Tambahan Peserta</div>
                        <p style="font-size: 14px; color: var(--color-text-secondary); margin: 0; line-height: 1.6;">
                            <?= htmlspecialchars($reg['notes']) ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bagian Bawah: QR Code / Tagihan Pembayaran -->
        <div class="ticket-footer">
            
            <?php if ($statusKey === 'verified'): ?>
                <!-- STATUS: VERIFIED (TAMPILKAN QR CODE E-TICKET) -->
                <div style="text-align: center;">
                    <?php
                    // Buat string data QR Code unik berbasis nomor registrasi dan ID
                    $qrData = urlencode('REG:' . $reg['registration_number'] . '|EVT:' . $reg['event_id'] . '|USR:' . $reg['user_id']);
                    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=" . $qrData;
                    ?>
                    <div class="qr-box">
                        <img src="<?= $qrUrl ?>" alt="QR Code E-Ticket" width="140" height="140" style="display: block; margin: 0 auto; object-fit: contain;" onerror="this.onerror=null; this.parentElement.innerHTML='<div style=\'font-size:12px;color:red;text-align:center;padding:20px;\'>Gagal memuat QR. Koneksi terputus.</div>';" />
                    </div>
                    <div style="margin-top: 20px; display: flex; flex-direction: column; align-items: center;">
                        <div style="display: inline-flex; align-items: center; gap: 8px; font-size: 18px; font-weight: 800; color: var(--color-primary);">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            E-Ticket Resmi & Valid
                        </div>
                        <p style="font-size: 13px; color: var(--color-text-muted); max-width: 450px; margin: 6px auto 0; line-height: 1.5;">
                            Tunjukkan QR Code di atas kepada panitia saat melakukan registrasi ulang (check-in) di lokasi event. QR Code ini bersifat unik untuk pendaftaran Anda.
                        </p>
                    </div>
                </div>

            <?php elseif ($statusKey === 'pending'): ?>
                <!-- STATUS: PENDING VERIFICATION -->
                <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.3); padding: 28px; border-radius: 20px; text-align: center; box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.05);">
                    <div style="display: inline-flex; padding: 16px; background: rgba(245, 158, 11, 0.15); border-radius: 50%; color: #D97706; margin-bottom: 16px;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 15 15"></polyline></svg>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 800; color: #D97706; margin-bottom: 10px;">
                        Pembayaran Sedang Diverifikasi
                    </h3>
                    <p style="font-size: 14px; color: var(--color-text-secondary); max-width: 550px; margin: 0 auto 20px; line-height: 1.6;">
                        Terima kasih telah mengupload bukti pembayaran. Panitia sedang memeriksa transaksi Anda. Tiket resmi dan QR Code akan aktif otomatis di halaman ini setelah verifikasi berhasil.
                    </p>
                    <?php if (!empty($reg['payment_proof'])): ?>
                        <a href="<?= url('assets/images/uploads/payments/' . htmlspecialchars($reg['payment_proof'])) ?>" target="_blank" class="btn btn-outline btn-sm btn-icon" style="border-color: #D97706; color: #D97706;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            Lihat Bukti yang Diupload
                        </a>
                    <?php endif; ?>
                </div>

            <?php elseif ($statusKey === 'unpaid'): ?>
                <!-- STATUS: UNPAID (TAMPILKAN INVOICE & REKENING) -->
                <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); padding: 32px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.05);">
                    <div style="display: flex; align-items: center; gap: 18px; margin-bottom: 24px; border-bottom: 1px solid rgba(239, 68, 68, 0.1); padding-bottom: 20px;">
                        <div style="padding: 14px; background: rgba(239, 68, 68, 0.15); border-radius: 16px; color: var(--color-error);">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        </div>
                        <div>
                            <h3 style="font-size: 20px; font-weight: 800; color: var(--color-error); margin-bottom: 4px;">
                                Instruksi Pembayaran
                            </h3>
                            <p style="font-size: 14px; color: var(--color-text-secondary); margin: 0;">
                                Segera lakukan pembayaran agar pendaftaran Anda dapat diproses oleh panitia.
                            </p>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; margin-bottom: 28px; background: white; padding: 24px; border-radius: 16px; border: 1px solid var(--color-border-light); box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div>
                            <div class="info-label">Bank Tujuan</div>
                            <div class="info-value" style="color: var(--color-primary); font-size: 18px;">
                                <?= htmlspecialchars($reg['bank_name'] ?? 'Bank Mandiri') ?>
                            </div>
                        </div>
                        <div>
                            <div class="info-label">Nomor Rekening</div>
                            <div class="info-value" style="font-family: monospace; font-size: 20px; letter-spacing: 1.5px; color: var(--color-text-primary);">
                                <?= htmlspecialchars($reg['bank_account_number'] ?? '123-456-7890') ?>
                            </div>
                        </div>
                        <div>
                            <div class="info-label">Atas Nama (A.N)</div>
                            <div class="info-value" style="font-size: 18px;">
                                <?= htmlspecialchars($reg['bank_account_name'] ?? 'BEM Ruang Unila') ?>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                        <div>
                            <div style="font-size: 12px; color: var(--color-text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">Total Tagihan</div>
                            <div style="font-size: 28px; font-weight: 800; color: var(--color-error);">
                                Rp <?= formatNumber($reg['fee']) ?>
                            </div>
                        </div>
                        <a href="<?= url('modules/events/payment.php?reg=' . urlencode($reg['registration_number'])) ?>" class="btn btn-primary btn-icon" style="padding: 16px 32px; font-size: 16px; border-radius: 14px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            Upload Bukti Transfer
                        </a>
                    </div>
                </div>

            <?php elseif ($statusKey === 'rejected'): ?>
                <!-- STATUS: REJECTED -->
                <div style="background: rgba(220, 38, 38, 0.08); border: 1px solid rgba(220, 38, 38, 0.3); padding: 28px; border-radius: 20px; text-align: center; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.05);">
                    <div style="display: inline-flex; padding: 16px; background: rgba(220, 38, 38, 0.15); border-radius: 50%; color: #DC2626; margin-bottom: 16px;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 800; color: #DC2626; margin-bottom: 10px;">
                        Bukti Pembayaran Ditolak
                    </h3>
                    <p style="font-size: 14px; color: var(--color-text-secondary); max-width: 550px; margin: 0 auto 16px; line-height: 1.6;">
                        Mohon maaf, bukti pembayaran yang Anda upload sebelumnya tidak valid atau tidak dapat diverifikasi oleh panitia.
                    </p>
                    <?php if (!empty($reg['payment_rejection_reason'])): ?>
                        <div style="background: white; padding: 14px 20px; border-radius: 12px; border: 1px solid #DC2626; color: #DC2626; font-size: 14px; font-weight: 700; max-width: 500px; margin: 0 auto 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                            Alasan Penolakan: <?= htmlspecialchars($reg['payment_rejection_reason']) ?>
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 24px;"></div>
                    <?php endif; ?>
                    <a href="<?= url('modules/events/payment.php?reg=' . urlencode($reg['registration_number'])) ?>" class="btn btn-primary btn-icon" style="background: #DC2626; padding: 14px 28px; border-radius: 12px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        Upload Ulang Bukti Pembayaran
                    </a>
                </div>
            <?php endif; ?>

            <!-- Bantuan / Support -->
            <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--ticket-border);">
                <p style="font-size: 13px; color: var(--color-text-muted); margin: 0; display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Jika mengalami kendala terkait pembayaran atau tiket, silakan hubungi panitia penyelenggara atau admin Ruang Unila.
                </p>
            </div>
        </div>

    </div>
</div>

<?php
// Include footer
include APP_ROOT . '/includes/footer.php';
?>
