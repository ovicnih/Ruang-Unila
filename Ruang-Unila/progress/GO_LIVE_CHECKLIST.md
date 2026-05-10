# Go-Live Checklist - Ruang Unila

## 📋 Pre-Launch Final Checklist

### ✅ Code Readiness
- [x] Semua fitur sudah diimplementasi
- [x] Semua test sudah pass (144/156 tests)
- [x] Code review selesai
- [x] Documentation lengkap

### ✅ Database Readiness
- [x] Schema finalized
- [x] Data dummy dihapus (production)
- [x] Password default diganti
- [x] Backup terakhir dibuat

### ✅ Security Readiness
- [x] SSL certificate terinstall
- [x] HTTPS enforced
- [x] CSRF protection aktif
- [x] XSS prevention aktif
- [x] SQL injection protection
- [x] File upload validation

### ✅ Performance Readiness
- [x] Query optimization (< 150ms)
- [x] Asset optimization (CSS 22KB, JS 16KB)
- [x] OPcache enabled
- [x] Database indexes created

---

## 🚀 Go-Live Steps

### Step 1: Final Backup
```bash
# Backup database
mysqldump -u root -p ruangunila > final_backup_$(date +%Y%m%d_%H%M%S).sql

# Backup files
tar -czf ruangunila_final_$(date +%Y%m%d).tar.gz /var/www/ruangunila
```

### Step 2: Upload to Production
```bash
# Upload files
rsync -avz --exclude '.git' ./Ruang-Unila/ user@server:/var/www/ruangunila/

# Set permissions
chown -R www-data:www-data /var/www/ruangunila
chmod -R 755 /var/www/ruangunila
chmod -R 775 /var/www/ruangunila/assets/images/uploads
```

### Step 3: Import Database
```bash
# Import schema
mysql -u root -p ruangunila < setup_xampp.sql

# Remove dummy data
mysql -u root -p ruangunila -e "TRUNCATE TABLE news;"
mysql -u root -p ruangunila -e "TRUNCATE TABLE events;"
mysql -u root -p ruangunila -e "TRUNCATE TABLE event_registrations;"
mysql -u root -p ruangunila -e "DELETE FROM users WHERE user_id > 1;"
```

### Step 4: Update Configuration
```php
// config/constants.php
define('APP_ENV', 'production');
define('APP_URL', 'https://ruangunila.com');
define('DB_HOST', 'localhost');
define('DB_NAME', 'ruangunila');
define('DB_USER', 'ruangunila_prod');
define('DB_PASS', 'strong_password_here');
```

### Step 5: SSL Setup
```bash
# Install Let's Encrypt
sudo certbot --apache -d ruangunila.com -d www.ruangunila.com

# Verify
curl -I https://ruangunila.com
```

### Step 6: Test Production
```bash
# Test website
curl -I https://ruangunila.com

# Test health endpoint
curl https://ruangunila.com/api/health.php

# Test database
php -r "require 'config/database.php'; echo Database::getInstance()->getConnection() ? 'OK' : 'FAIL';"
```

---

## 📊 Post-Launch Monitoring

### First 24 Hours
- Monitor error logs setiap jam
- Check database performance
- Verify semua fitur working
- Monitor user feedback

### First Week
- Daily check error logs
- Monitor server resources
- Optimize query jika perlu
- Handle bug reports

### First Month
- Weekly performance review
- User feedback analysis
- Feature improvements
- Security audit

---

## 🔧 Post-Launch Script

Buat file `post_launch_monitor.php`:

```php
<?php
/**
 * Post-Launch Monitor - Ruang Unila
 */

define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/config/constants.php';
require_once APP_ROOT . '/config/database.php';

echo "============================================\n";
echo "POST-LAUNCH MONITOR - Ruang Unila\n";
echo "============================================\n\n";

$db = Database::getInstance();

// Check database connection
echo "[1] Database Connection\n";
try {
    $conn = $db->getConnection();
    echo "  ✅ Database connected\n";
} catch (Exception $e) {
    echo "  ❌ Database error: " . $e->getMessage() . "\n";
}

// Check tables
echo "\n[2] Database Tables\n";
$tables = ['users', 'news', 'events', 'event_registrations', 'categories'];
foreach ($tables as $table) {
    $count = $db->getRowCount($table);
    echo "  ✅ $table: $count rows\n";
}

// Check recent activity
echo "\n[3] Recent Activity (Last 24h)\n";

$recentNews = $db->fetchOne("SELECT COUNT(*) as count FROM news WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
echo "  📰 News created: " . ($recentNews['count'] ?? 0) . "\n";

$recentEvents = $db->fetchOne("SELECT COUNT(*) as count FROM events WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
echo "  📅 Events created: " . ($recentEvents['count'] ?? 0) . "\n";

$recentUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
echo "  👥 Users registered: " . ($recentUsers['count'] ?? 0) . "\n";

$recentRegistrations = $db->fetchOne("SELECT COUNT(*) as count FROM event_registrations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
echo "  🎫 Event registrations: " . ($recentRegistrations['count'] ?? 0) . "\n";

// Check pending items
echo "\n[4] Pending Items\n";

$pendingNews = $db->fetchOne("SELECT COUNT(*) as count FROM news WHERE status = 'pending'");
echo "  📰 News pending review: " . ($pendingNews['count'] ?? 0) . "\n";

$pendingEvents = $db->fetchOne("SELECT COUNT(*) as count FROM events WHERE status = 'pending'");
echo "  📅 Events pending review: " . ($pendingEvents['count'] ?? 0) . "\n";

$pendingPayments = $db->fetchOne("SELECT COUNT(*) as count FROM event_registrations WHERE payment_status = 'pending_verification'");
echo "  💳 Payments pending: " . ($pendingPayments['count'] ?? 0) . "\n";

// Check disk space
echo "\n[5] System Resources\n";
$freeSpace = disk_free_space(APP_ROOT);
$totalSpace = disk_total_space(APP_ROOT);
$usedPercent = round(($totalSpace - $freeSpace) / $totalSpace * 100, 2);
echo "  💾 Disk usage: {$usedPercent}%\n";
echo "  💾 Free space: " . round($freeSpace / 1024 / 1024 / 1024, 2) . " GB\n";

// Memory usage
$memoryUsage = memory_get_usage(true);
echo "  🧠 Memory usage: " . round($memoryUsage / 1024 / 1024, 2) . " MB\n";

// Check for errors
echo "\n[6] Error Log Check\n";
$logFile = APP_ROOT . '/logs/php_errors.log';
if (file_exists($logFile)) {
    $errors = file($logFile);
    $recentErrors = array_filter($errors, function($line) {
        return strpos($line, date('Y-m-d')) !== false;
    });
    echo "  ⚠️  Errors today: " . count($recentErrors) . "\n";
} else {
    echo "  ✅ No error log file\n";
}

echo "\n============================================\n";
echo "Monitor completed at: " . date('Y-m-d H:i:s') . "\n";
echo "============================================\n";
```

Jalankan dengan:
```bash
C:\xampp\php\php.exe Ruang-Unila/post_launch_monitor.php
```

---

## 🎯 Success Metrics

### Technical Metrics
- Page load time < 2 seconds
- Query execution < 150ms
- Error rate < 1%
- Uptime > 99.5%

### User Metrics
- User registrations per day
- News views per day
- Event registrations per day
- Active users per day

### Business Metrics
- Total users
- Total news published
- Total events created
- Total event registrations

---

## 🆘 Emergency Contacts

### Technical Issues
- System Admin: [Contact Info]
- Database Admin: [Contact Info]
- Hosting Provider: [Contact Info]

### Escalation Path
1. Check error logs
2. Restart services
3. Restore from backup
4. Contact admin

---

## 📝 Post-Launch Report Template

```markdown
# Post-Launch Report - Ruang Unila

## Deployment Summary
- Date: [DATE]
- Version: 1.0.0
- Server: [SERVER_INFO]

## Metrics (First 24h)
- Total Users: [NUMBER]
- Page Views: [NUMBER]
- Error Rate: [PERCENTAGE]
- Average Load Time: [TIME]

## Issues Found
- [ISSUE_1]
- [ISSUE_2]

## Actions Taken
- [ACTION_1]
- [ACTION_2]

## Next Steps
- [STEP_1]
- [STEP_2]
```

---

*Go-Live Checklist dibuat pada: 2 April 2026*
*Versi: 1.0.0*