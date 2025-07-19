# Production Deployment Guide

⚠️ **Important**: This game is currently designed for development environments. This guide provides considerations for production deployment, but additional security hardening is required.

## 🚨 Security Considerations

### Critical Security Requirements

Before deploying to production, you **MUST** implement these security measures:

#### 1. Change Default Credentials
```php
// In admin.php - Change these values
if ($username === 'admin' && $password === 'STRONG_SECURE_PASSWORD_HERE') {
    // Admin authentication
}
```

#### 2. Database Security
```sql
-- Create secure database user
CREATE USER 'browsergame_prod'@'localhost' IDENTIFIED BY 'very_strong_password_here';
GRANT SELECT, INSERT, UPDATE, DELETE ON browsergame.* TO 'browsergame_prod'@'localhost';
FLUSH PRIVILEGES;

-- Remove test data
DELETE FROM Spieler WHERE playerName = 'TestPlayer';
```

#### 3. PHP Security Configuration
```php
// In a new config/security.php file
<?php
// Disable debug output
ini_set('display_errors', 0);
error_reporting(0);

// Enable secure sessions
ini_set('session.cookie_secure', 1);     // HTTPS only
ini_set('session.cookie_httponly', 1);   // No JS access
ini_set('session.use_strict_mode', 1);   // Strict session handling

// Set secure headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
?>
```

#### 4. Input Validation
Enhance input validation in all backend files:
```php
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateSettlementId($id) {
    return filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
}
```

## 🏗️ Production Architecture Options

### Option 1: Traditional LAMP Stack

#### Server Requirements
- **OS**: Ubuntu 20.04 LTS or CentOS 8+
- **CPU**: 2+ cores
- **RAM**: 4GB minimum, 8GB recommended
- **Storage**: 20GB SSD minimum
- **Network**: Static IP with domain name

#### Software Stack
- **Web Server**: Apache 2.4+ with mod_rewrite
- **PHP**: 8.0+ with extensions: mysqli, json, session
- **Database**: MariaDB 10.4+ or MySQL 8.0+
- **SSL**: Let's Encrypt or commercial certificate

#### Installation Steps
```bash
# 1. Update system
sudo apt update && sudo apt upgrade -y

# 2. Install LAMP stack
sudo apt install apache2 mariadb-server php php-mysqli -y

# 3. Secure MariaDB
sudo mysql_secure_installation

# 4. Configure Apache with SSL
sudo a2enmod ssl rewrite
sudo a2ensite default-ssl

# 5. Deploy application
sudo git clone https://github.com/your-fork/browsergame.git /var/www/html/game
sudo chown -R www-data:www-data /var/www/html/game
sudo chmod -R 755 /var/www/html/game

# 6. Setup database
mysql -u root -p < /var/www/html/game/sql/database.sql

# 7. Configure SSL certificate (Let's Encrypt)
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d yourdomain.com
```

### Option 2: Docker Production Deployment

#### Production Docker Setup
```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  web:
    build: .
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./ssl:/etc/ssl/certs
      - ./logs:/var/log/apache2
    environment:
      - DB_HOST=db
      - DB_NAME=browsergame
      - DB_USER=browsergame_prod
      - DB_PASSWORD=${DB_PASSWORD}
    depends_on:
      - db
    restart: unless-stopped

  db:
    image: mariadb:10.9
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=browsergame
      - MYSQL_USER=browsergame_prod
      - MYSQL_PASSWORD=${DB_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
      - ./backups:/backups
    restart: unless-stopped
    command: --event-scheduler=ON

  backup:
    image: mariadb:10.9
    volumes:
      - db_data:/var/lib/mysql
      - ./backups:/backups
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
    entrypoint: |
      bash -c '
      while true; do
        mysqldump -h db -u root -p$$MYSQL_ROOT_PASSWORD browsergame > /backups/backup_$$(date +%Y%m%d_%H%M%S).sql
        find /backups -name "backup_*.sql" -mtime +7 -delete
        sleep 86400
      done'
    depends_on:
      - db

volumes:
  db_data:
```

#### Environment Configuration
```bash
# Create .env.prod file
cat > .env.prod << EOF
DB_PASSWORD=your_very_secure_database_password_here
MYSQL_ROOT_PASSWORD=your_very_secure_root_password_here
EOF
```

#### Deployment Commands
```bash
# Deploy to production
docker-compose -f docker-compose.prod.yml --env-file .env.prod up -d

# Check status
docker-compose -f docker-compose.prod.yml ps

# View logs
docker-compose -f docker-compose.prod.yml logs -f web
```

## 🔧 Configuration Changes

### Environment Variables
Create a production configuration system:

```php
// config/production.php
<?php
return [
    'db_host' => $_ENV['DB_HOST'] ?? 'localhost',
    'db_name' => $_ENV['DB_NAME'] ?? 'browsergame',
    'db_user' => $_ENV['DB_USER'] ?? 'browsergame_prod',
    'db_pass' => $_ENV['DB_PASSWORD'] ?? '',
    'admin_user' => $_ENV['ADMIN_USER'] ?? 'admin',
    'admin_pass' => $_ENV['ADMIN_PASSWORD'] ?? '',
    'debug_mode' => false,
    'log_level' => 'ERROR'
];
?>
```

### Database Configuration
```sql
-- Production database optimizations
SET GLOBAL innodb_buffer_pool_size = 2G;
SET GLOBAL query_cache_size = 64M;
SET GLOBAL max_connections = 100;
SET GLOBAL event_scheduler = ON;

-- Setup automated backups
CREATE EVENT backup_daily
ON SCHEDULE EVERY 1 DAY STARTS '02:00:00'
DO
  CALL BackupDatabase();
```

### Web Server Configuration

#### Apache Virtual Host
```apache
# /etc/apache2/sites-available/browsergame.conf
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/browsergame
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    
    # Disable server signature
    ServerTokens Prod
    ServerSignature Off
    
    # Error and access logs
    ErrorLog ${APACHE_LOG_DIR}/browsergame_error.log
    CustomLog ${APACHE_LOG_DIR}/browsergame_access.log combined
    
    # PHP configuration
    php_admin_value display_errors Off
    php_admin_value log_errors On
    php_admin_value error_log /var/log/apache2/php_errors.log
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>
```

## 📊 Monitoring and Maintenance

### Health Monitoring
Create a health check endpoint:

```php
// health.php
<?php
header('Content-Type: application/json');

$health = [
    'status' => 'OK',
    'timestamp' => date('c'),
    'checks' => []
];

// Database check
try {
    $database = new Database();
    $health['checks']['database'] = 'OK';
} catch (Exception $e) {
    $health['checks']['database'] = 'FAIL';
    $health['status'] = 'ERROR';
}

// Event scheduler check
try {
    $result = $database->query("SHOW VARIABLES LIKE 'event_scheduler'");
    $health['checks']['event_scheduler'] = $result['Value'] === 'ON' ? 'OK' : 'FAIL';
} catch (Exception $e) {
    $health['checks']['event_scheduler'] = 'FAIL';
}

echo json_encode($health);
?>
```

### Log Management
```bash
# Setup log rotation
sudo cat > /etc/logrotate.d/browsergame << EOF
/var/log/apache2/browsergame_*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 640 www-data www-data
    postrotate
        systemctl reload apache2
    endscript
}
EOF
```

### Backup Strategy
```bash
#!/bin/bash
# backup.sh - Daily backup script

BACKUP_DIR="/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Database backup
mysqldump -u root -p$MYSQL_ROOT_PASSWORD browsergame > $BACKUP_DIR/db_backup_$DATE.sql

# Application backup
tar -czf $BACKUP_DIR/app_backup_$DATE.tar.gz /var/www/html/browsergame

# Clean old backups (keep 30 days)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

# Add to crontab: 0 2 * * * /path/to/backup.sh
```

## 🔍 Performance Optimization

### Database Optimization
```sql
-- Optimize for production load
ALTER TABLE Settlement ADD INDEX idx_player_coords (playerId, coordinateX, coordinateY);
ALTER TABLE Buildings ADD INDEX idx_settlement_type (settlementId, buildingType);
ALTER TABLE BuildingQueue ADD INDEX idx_active_queue (isActive, endTime);

-- Enable query cache
SET GLOBAL query_cache_type = ON;
SET GLOBAL query_cache_size = 64M;
```

### PHP Optimization
```php
// Enable OPcache in php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

### Caching Strategy
Consider implementing:
- **Redis**: For session storage and game state caching
- **Memcached**: For database query caching
- **CDN**: For static assets (CSS, JS, images)

## 🚨 Security Monitoring

### Fail2Ban Configuration
```ini
# /etc/fail2ban/jail.local
[apache-browsergame]
enabled = true
port = http,https
filter = apache-browsergame
logpath = /var/log/apache2/browsergame_access.log
maxretry = 5
bantime = 3600
```

### Log Monitoring
Monitor for:
- Failed login attempts
- SQL injection attempts
- Unusual API usage patterns
- Database connection failures
- Resource exhaustion

## 📋 Deployment Checklist

### Pre-Deployment
- [ ] Change all default passwords
- [ ] Configure SSL certificates
- [ ] Set up database with production credentials
- [ ] Configure security headers
- [ ] Set up monitoring and logging
- [ ] Test backup and restore procedures
- [ ] Configure firewall rules
- [ ] Set up automated security updates

### Post-Deployment
- [ ] Verify SSL configuration
- [ ] Test all game functionality
- [ ] Verify admin panel access
- [ ] Check database event scheduler
- [ ] Test backup procedures
- [ ] Monitor performance metrics
- [ ] Set up uptime monitoring
- [ ] Document runbook procedures

### Ongoing Maintenance
- [ ] Regular security updates
- [ ] Database optimization
- [ ] Log review and cleanup
- [ ] Backup verification
- [ ] Performance monitoring
- [ ] Capacity planning

## ⚠️ Known Limitations

This application has several limitations for production use:

1. **No User Registration**: Users are created through admin panel only
2. **Basic Authentication**: Simple session-based auth without proper user management
3. **No Rate Limiting**: APIs are not rate-limited
4. **Limited Input Validation**: Some endpoints need enhanced validation
5. **No Battle System**: Military units are purely cosmetic currently
6. **Single Server**: Not designed for horizontal scaling

## 📞 Production Support

For production deployments:
- Review all code for security vulnerabilities
- Implement proper user authentication system
- Add comprehensive input validation
- Set up proper monitoring and alerting
- Plan for scalability and load balancing
- Consider professional security audit

Remember: This is primarily a development/educational project. For serious production use, significant additional security hardening and feature development is required.