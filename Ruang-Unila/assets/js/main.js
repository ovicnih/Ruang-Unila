/**
 * Main JavaScript - Ruang Unila
 * 
 * File ini berisi fungsi-fungsi JavaScript untuk
 * interaktivitas sistem web Ruang Unila.
 * 
 * @package RuangUnila
 * @version 1.0.0
 */

// ============================================================
// DOM READY
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    initSmoothScrolling();
    initImagePreview();
    initFormValidation();
    initLoadingIndicators();
    initDropdownMenus();
    initAlertDismiss();
    initLazyLoading();
});

// ============================================================
// SMOOTH SCROLLING
// ============================================================

/**
 * Inisialisasi smooth scrolling untuk anchor links
 */
function initSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#' || href === '') return;
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// ============================================================
// IMAGE PREVIEW
// ============================================================

/**
 * Inisialisasi preview gambar sebelum upload
 */
function initImagePreview() {
    // Preview untuk input file dengan data-preview attribute
    document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
        input.addEventListener('change', function(e) {
            const previewId = this.getAttribute('data-preview');
            const previewElement = document.getElementById(previewId);
            
            if (!previewElement) return;
            
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (previewElement.tagName === 'IMG') {
                        previewElement.src = e.target.result;
                    } else {
                        previewElement.style.backgroundImage = `url(${e.target.result})`;
                    }
                    previewElement.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                previewElement.style.display = 'none';
            }
        });
    });
    
    // Preview untuk semua input file image
    document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
        if (!input.hasAttribute('data-preview')) {
            input.addEventListener('change', function(e) {
                const file = this.files[0];
                const parent = this.parentElement;
                
                // Cari atau buat elemen preview
                let preview = parent.querySelector('.image-preview');
                if (!preview) {
                    preview = document.createElement('div');
                    preview.className = 'image-preview';
                    preview.style.cssText = 'display:none; margin-top:12px; max-width:200px;';
                    parent.appendChild(preview);
                }
                
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" style="width:100%; border-radius:8px;">`;
                        preview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                } else {
                    preview.style.display = 'none';
                }
            });
        }
    });
}

// ============================================================
// FORM VALIDATION
// ============================================================

/**
 * Inisialisasi validasi form client-side
 */
function initFormValidation() {
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const errors = [];
            
            // Validasi required fields
            form.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    errors.push(`${getFieldLabel(field)} wajib diisi`);
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            // Validasi email
            form.querySelectorAll('input[type="email"]').forEach(field => {
                if (field.value && !isValidEmail(field.value)) {
                    isValid = false;
                    errors.push('Format email tidak valid');
                    field.classList.add('error');
                }
            });
            
            // Validasi min length
            form.querySelectorAll('[minlength]').forEach(field => {
                const minLength = parseInt(field.getAttribute('minlength'));
                if (field.value && field.value.length < minLength) {
                    isValid = false;
                    errors.push(`${getFieldLabel(field)} minimal ${minLength} karakter`);
                    field.classList.add('error');
                }
            });
            
            // Validasi password match
            const password = form.querySelector('input[name="new_password"]');
            const confirm = form.querySelector('input[name="confirm_password"]');
            if (password && confirm && password.value !== confirm.value) {
                isValid = false;
                errors.push('Konfirmasi password tidak sesuai');
                confirm.classList.add('error');
            }
            
            if (!isValid) {
                e.preventDefault();
                showValidationErrors(errors);
            }
        });
    });
}

/**
 * Mendapatkan label field
 */
function getFieldLabel(field) {
    const label = document.querySelector(`label[for="${field.id}"]`);
    return label ? label.textContent.replace('*', '').trim() : field.name;
}

/**
 * Validasi format email
 */
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * Tampilkan error validasi
 */
function showValidationErrors(errors) {
    // Hapus error lama
    const oldAlert = document.querySelector('.validation-errors');
    if (oldAlert) oldAlert.remove();
    
    // Buat alert baru
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger validation-errors';
    alert.innerHTML = '<strong>Terdapat kesalahan:</strong><ul>' + 
        errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
    
    // Insert di awal form
    const form = document.querySelector('form');
    if (form) {
        form.insertBefore(alert, form.firstChild);
        alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// ============================================================
// LOADING INDICATORS
// ============================================================

/**
 * Inisialisasi loading indicators untuk form submit
 */
function initLoadingIndicators() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                // Simpan teks asli
                const originalText = submitBtn.innerHTML;
                submitBtn.setAttribute('data-original-text', originalText);
                
                // Tampilkan loading
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block; vertical-align:middle; margin-right:8px; animation: spin 1s linear infinite;">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                        <path d="M12 2a10 10 0 0 1 10 10" stroke-opacity="0.75"></path>
                    </svg>
                    Memproses...
                `;
            }
        });
    });
}

// Add spin animation
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .error {
        border-color: var(--color-danger, #dc3545) !important;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }
`;
document.head.appendChild(style);

// ============================================================
// DROPDOWN MENUS
// ============================================================

/**
 * Inisialisasi dropdown menus
 */
function initDropdownMenus() {
    document.querySelectorAll('.dropdown').forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (trigger && menu) {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('show');
            });
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
    });
}

// ============================================================
// ALERT DISMISS
// ============================================================

/**
 * Inisialisasi dismiss untuk alert messages
 */
function initAlertDismiss() {
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        // Tambahkan tombol close jika belum ada
        if (!alert.querySelector('.alert-close')) {
            const closeBtn = document.createElement('button');
            closeBtn.className = 'alert-close';
            closeBtn.innerHTML = '&times;';
            closeBtn.style.cssText = 'position:absolute; top:8px; right:12px; background:none; border:none; font-size:20px; cursor:pointer; opacity:0.7;';
            closeBtn.onclick = function() {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            };
            alert.style.position = 'relative';
            alert.appendChild(closeBtn);
        }
        
        // Auto dismiss setelah 5 detik
        setTimeout(() => {
            if (alert.parentElement) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    });
}

// ============================================================
// LAZY LOADING
// ============================================================

/**
 * Inisialisasi lazy loading untuk gambar
 */
function initLazyLoading() {
    // Gunakan Intersection Observer jika tersedia
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback: load semua gambar sekaligus
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
}

// ============================================================
// UTILITY FUNCTIONS
// ============================================================

/**
 * Format angka dengan separator ribuan
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

/**
 * Format tanggal ke format Indonesia
 */
function formatDate(dateStr) {
    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    const date = new Date(dateStr);
    const day = date.getDate();
    const month = months[date.getMonth()];
    const year = date.getFullYear();
    
    return `${day} ${month} ${year}`;
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Berhasil disalin!', 'success');
        });
    } else {
        // Fallback
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showToast('Berhasil disalin!', 'success');
    }
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 12px 24px;
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add toast animations
const toastStyle = document.createElement('style');
toastStyle.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(toastStyle);

/**
 * Konfirmasi sebelum hapus
 */
function confirmDelete(message = 'Apakah Anda yakin ingin menghapus?') {
    return confirm(message);
}

/**
 * Auto resize textarea
 */
function autoResizeTextarea(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
}

// Inisialisasi auto resize untuk semua textarea
document.querySelectorAll('textarea').forEach(textarea => {
    textarea.addEventListener('input', function() {
        autoResizeTextarea(this);
    });
});