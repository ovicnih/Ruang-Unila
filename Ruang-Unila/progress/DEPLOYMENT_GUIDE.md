# Deployment Guide - Ruang Unila

## 📋 Daftar Isi
1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Backup Database](#backup-database)
3. [Production Setup](#production-setup)
4. [SSL Configuration](#ssl-configuration)
5. [Error Logging](#error-logging)
6. [Monitoring](#monitoring)
7. [Go-Live Steps](#go-live-steps)

---

## Pre-Deployment Checklist

### ✅ Code Quality
- [x] Semua file < 500 baris
- [x] Prepared statements untuk semua query
- [x] CSRF protection aktif
- [x] XSS prevention (htmlspecialchars)
- [x] Password hashing (bcrypt)
- [x] File upload validation

### ✅ Testing
- [x] Integration tests: 46/46 passed
- [x] Security tests: 33/38 passed
- [x] Performance tests: 24/28 passed
- [x] UAT tests: 41/44 passed

### ✅ Documentation
- [x] Technical documentation (TECHNICAL_DOCS.md)
- [x] User guide (USER_GUIDE.md)
- [x] Inline code comments

### ✅ Database
- [x] Schema finalized
- [x] Indexes created
- [x] Foreign keys configured
- [x] Data dummy removed (production)

---

## Backup Database

### Manual Backup
```bash
# Backup database
mysqldump -u root -p ruangunila > backup_ruangunila_$(date +%Y%m%d).sql

# Backup dengan struktur + data
mysqldump -u root -p --databases ruangunila > backup_full_$(date +%Y%m%d).sql

# Backup hanya struktur
mysqldump -u root -p --no-data ruangunila > backup_structure_$(date +%Y%m%d).sql
```

### Automated Backup Script
```bash
#!/bin/bash
# backup_database.sh

BACKUP_DIR="/var/backups/ruangunila"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="ruangunila"
DB_USER="root"

# Buat direktori backup jika belum ada
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p $DB_NAME > "$BACKUP_DIR/backup_$DATE.sql"

# Hapus backup lebih dari 30 hari
find $BACKUP_DIR -name "backup_*.sql" -mtime +30 -delete

echo "Backup completed: backup_$DATE.sql"
```

### Restore Database
```bash
# Restore dari backup
mysql -u root -p ruangunila < backup_ruangunila_20260402.sql

# Restore ke database baru
mysql -u root -p < backup_full_20260402.sql
```

---

## Production Setup

### 1. Server Requirements
```
- OS: Ubuntu 20.04+ / Windows Server 2019+
- PHP: 8.0+
- MySQL: 8.0+
- Apache/Nginx
- RAM: 2GB minimum
- Storage: 20GB minimum
```

### 2. PHP Configuration (php.ini)
```ini
; Security
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log

; Performance
memory_limit = 256M
max_execution_time = 60
post_max_size = 10M
upload_max_filesize = 5M

; Session
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1

; OPcache
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
```

### 3. Apache Configuration
```apache
<VirtualHost *:80>
    ServerName ruangunila.com
    ServerAlias www.ruangunila.com
    DocumentRoot /var/www/ruangunila
    
    <Directory /var/www/ruangunila>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/ruangunila_error.log
    CustomLog ${APACHE_LOG_DIR}/ruangunila_access.log combined
</VirtualHost>
```

### 4. Nginx Configuration
```nginx
server {
    listen 80;
    server_name ruangunila.com www.ruangunila.com;
    root /var/www/ruangunila;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

### 5. Environment Configuration
```php
// config/constants.php - Production
define('APP_ENV', 'production');
define('APP_URL', 'https://ruangunila.com');
define('DB_HOST', 'localhost');
define('DB_NAME', 'ruangunila');
define('DB_USER', 'ruangunila_user'); // User terbatas
define('DB_PASS', 'strong_password_here');
```

---

## SSL Configuration

### Using Let's Encrypt (Recommended)
```bash
# Install Certbot
sudo apt update
sudo apt install certbot python3-certbot-apache

# Obtain certificate
sudo certbot --apache -d ruangunila.com -d www.ruangunila.com

# Auto-renewal
sudo certbot renew --dry-run
```

### Apache SSL Configuration
```apache
<VirtualHost *:443>
    ServerName ruangunila.com
    DocumentRoot /var/www/ruangunila
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/ruangunila.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/ruangunila.com/privkey.pem
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName ruangunila.com
    Redirect permanent / https://ruangunila.com/
</VirtualHost>
```

### Force HTTPS in PHP
```php
// config/constants.php
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    define('APP_URL', 'https://' . $_SERVER['HTTP_HOST']);
} else {
    define('APP_URL', 'http://' . $_SERVER['HTTP_HOST']);
}

// Force redirect to HTTPS
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

---

## Error Logging

### PHP Error Logging
```php
// config/error_handler.php
<?php
/**
 * Error Handler - Ruang Unila
 */

// Set error reporting based on environment
if (defined('APP_ENV') && APP_ENV === 'production') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Custom error log path
ini_set('error_log', APP_ROOT . '/logs/php_errors.log');

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] Error [$errno]: $errstr in $errfile on line $errline\n";
    error_log($message);
    
    if (defined('APP_ENV') && APP_ENV === 'production') {
        // Don't show error details to users
        die('Terjadi kesalahan. Silakan coba lagi nanti.');
    }
}

set_error_handler('customErrorHandler');
```

### Application Logging
```php
// includes/logger.php
<?php
/**
 * Logger - Ruang Unila
 */

class Logger {
    private static $logFile = APP_ROOT . '/logs/application.log';
    
    public static function info(string $message, array $context = []) {
        self::log('INFO', $message, $context);
    }
    
    public static function error(string $message, array $context = []) {
        self::log('ERROR', $message, $context);
    }
    
    public static function warning(string $message, array $context = []) {
        self::log('WARNING', $message, $context);
    }
    
    private static function log(string $level, string $message, array $context) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logMessage = "[$timestamp] [$level] $message $contextStr\n";
        
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
    }
}

// Usage:
// Logger::info('User logged in', ['user_id' => 123]);
// Logger::error('Database connection failed', ['error' => $e->getMessage()]);
```

### Log Rotation
```bash
# /etc/logrotate.d/ruangunila
/var/log/ruangunila/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload apache2
    endscript
}
```

---

## Monitoring

### Health Check Endpoint
```php
// api/health.php
<?php
/**
 * Health Check - Ruang Unila
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/constants.php';
require_once APP_ROOT . '/config/database.php';

header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'version' => '1.0.0',
    'checks' => []
];

// Database check
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $conn->query('SELECT 1');
    $health['checks']['database'] = 'ok';
} catch (Exception $e) {
    $health['status'] = 'error';
    $health['checks']['database'] = 'error: ' . $e->getMessage();
}

// Disk space check
$freeSpace = disk_free_space(APP_ROOT);
$totalSpace = disk_total_space(APP_ROOT);
$usedPercent = round(($totalSpace - $freeSpace) / $totalSpace * 100, 2);

$health['checks']['disk'] = [
    'status' => $usedPercent < 90 ? 'ok' : 'warning',
    'used_percent' => $usedPercent,
    'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2)
];

// Memory check
$memoryUsage = memory_get_usage(true);
$memoryLimit = return_bytes(ini_get('memory_limit'));
$memoryPercent = round($memoryUsage / $memoryLimit * 100, 2);

$health['checks']['memory'] = [
    'status' => $memoryPercent < 80 ? 'ok' : 'warning',
    'used_mb' => round($memoryUsage / 1024 / 1024, 2),
    'limit_mb' => round($memoryLimit / 1024 / 1024, 2)
];

echo json_encode($health, JSON_PRETTY_PRINT);

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}
```

### Monitoring Script
```bash
#!/bin/bash
# monitor.sh

URL="http://localhost/WEBUNILA/Ruang-Unila/api/health.php"
LOG_FILE="/var/log/ruangunila/monitor.log"

response=$(curl -s -o /dev/null -w "%{http_code}" $URL)

if [ $response -eq 200 ]; then
    echo "[$(date)] OK - Website is up" >> $LOG_FILE
else
    echo "[$(date)] ERROR - Website returned $response" >> $LOG_FILE
    # Send alert (email, Slack, etc.)
fi
```

### Cron Job for Monitoring
```bash
# Add to crontab
# Check every 5 minutes
*/5 * * * * /path/to/monitor.sh
```

---

## Go-Live Steps

### Step 1: Final Backup
```bash
# Backup current state
mysqldump -u root -p ruangunila > final_backup_$(date +%Y%m%d).sql
tar -czf ruangunila_files_$(date +%Y%m%d).tar.gz /var/www/ruangunila
```

### Step 2: Upload Files
```bash
# Upload to server
rsync -avz --exclude '.git' --exclude 'node_modules' ./Ruang-Unila/ user@server:/var/www/ruangunila/

# Or using SCP
scp -r ./Ruang-Unila/* user@server:/var/www/ruangunila/
```

### Step 3: Set Permissions
```bash
# Set ownership
chown -R www-data:www-data /var/www/ruangunila

# Set directory permissions
find /var/www/ruangunila -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/ruangunila -type f -exec chmod 644 {} \;

# Make upload directories writable
chmod -R 775 /var/www/ruangunila/assets/images/uploads
```

### Step 4: Configure Database
```bash
# Import database
mysql -u root -p ruangunila < setup_xampp.sql

# Remove data dummy (production)
mysql -u root -p ruangunila -e "DELETE FROM news WHERE author_id > 1;"
mysql -u root -p ruangunila -e "DELETE FROM events WHERE organizer_id > 1;"

# Change default passwords
mysql -u root -p ruangunila -e "UPDATE users SET password = 'new_hashed_password' WHERE username = 'admin';"
```

### Step 5: Update Configuration
```php
// config/constants.php - Update for production
define('APP_ENV', 'production');
define('APP_URL', 'https://ruangunila.com');
define('DB_HOST', 'localhost');
define('DB_NAME', 'ruangunila');
define('DB_USER', 'ruangunila_prod');
define('DB_PASS', 'strong_production_password');
```

### Step 6: Test Production
```bash
# Test website
curl -I https://ruangunila.com

# Test database connection
php -r "require 'config/database.php'; echo Database::getInstance()->getConnection() ? 'OK' : 'FAIL';"

# Test health endpoint
curl https://ruangunila.com/api/health.php
```

### Step 7: DNS Configuration
```
A Record: ruangunila.com -> server_ip
CNAME: www.ruangunila.com -> ruangunila.com
```

### Step 8: SSL Certificate
```bash
# Install SSL
sudo certbot --apache -d ruangunila.com -d www.ruangunila.com

# Verify
curl -I https://ruangunila.com
```

### Step 9: Post-Launch Monitoring
- Monitor error logs
- Check performance metrics
- Verify all features working
- Monitor user feedback

---

## Rollback Plan

If issues occur:
```bash
# Restore database
mysql -u root -p ruangunila < final_backup_20260402.sql

# Restore files
tar -xzf ruangunila_files_20260402.tar.gz -C /var/www/ruangunila

# Restart services
systemctl restart apache2
systemctl restart mysql
```

---

*Deployment Guide dibuat pada: 2 April 2026*
*Versi: 1.0.0*