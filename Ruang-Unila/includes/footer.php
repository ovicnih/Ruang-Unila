<?php
/**
 * Footer Template - Ruang Unila
 * 
 * Template footer dengan informasi dan link
 * berdasarkan design dari Figma.
 * 
 * @package RuangUnila
 * @version 1.0.0
 */

// Cegah akses langsung
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}
?>
    </div> <!-- End Main Content -->

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <!-- Brand & Copyright -->
            <div class="footer-brand">
                <div class="footer-logo">Ruang Unila</div>
                <div class="footer-copyright">
                    © 2026 Ruang Unila. Universitas Lampung.
                </div>
            </div>
            
            <!-- Links -->
            <div class="footer-links">
                <a href="<?= url('pages/about.php') ?>" class="footer-link">
                    Tentang Kami
                </a>
                <a href="<?= url('pages/contact.php') ?>" class="footer-link">
                    Kontak
                </a>
                <a href="<?= url('pages/faq.php') ?>" class="footer-link">
                    FAQ
                </a>
                <a href="<?= url('pages/privacy.php') ?>" class="footer-link">
                    Privasi
                </a>
            </div>
            
            <!-- Social Media -->
            <div class="footer-social">
                <a href="#" class="footer-social-link" title="Instagram">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                    </svg>
                </a>
                <a href="#" class="footer-social-link" title="Twitter">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>
                    </svg>
                </a>
                <a href="#" class="footer-social-link" title="Facebook">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                    </svg>
                </a>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>