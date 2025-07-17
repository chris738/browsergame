# Browsergame Installation und Troubleshooting Guide

## 🚀 Installationsoptionen

### 1. Docker Installation (Empfohlen)

**Vorteile:**
- Keine lokale Software-Installation nötig
- Konsistente Umgebung
- Einfache Wartung und Updates
- Isolation von Host-System

**Schritte:**
```bash
git clone https://github.com/chris738/browsergame.git
cd browsergame
./docker-start.sh
```

### 2. Automatisches Installationsskript

**Unterstützte Systeme:**
- Ubuntu 18.04+, 20.04+, 22.04+
- Debian 10+, 11+
- CentOS 7+, 8+
- RHEL 7+, 8+, 9+
- Rocky Linux 8+, 9+
- AlmaLinux 8+, 9+

**Schritte:**
```bash
git clone https://github.com/chris738/browsergame.git
cd browsergame
./install.sh
```

### 3. Manuelle Installation

Siehe README.md für detaillierte Anweisungen.

## 🐛 Troubleshooting

### Docker Probleme

#### "docker: command not found"
```bash
# Ubuntu/Debian
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Oder über Package Manager
sudo apt update
sudo apt install docker.io docker-compose
sudo usermod -aG docker $USER
```

#### "docker-compose: command not found"
```bash
# Option 1: Docker Compose V2 (empfohlen)
sudo apt install docker-compose-plugin

# Option 2: Legacy Docker Compose
sudo pip3 install docker-compose
```

#### Container starten nicht
```bash
# Logs prüfen
docker-compose logs

# Container Status prüfen
docker-compose ps

# Neustart mit Rebuild
docker-compose down
docker-compose up -d --build
```

#### Port 8080 bereits belegt
```bash
# Anderen Port verwenden
sed -i 's/8080:80/8081:80/g' docker-compose.yml
docker-compose up -d
```

#### Datenbank-Container startet nicht
```bash
# Volumes löschen und neu erstellen
docker-compose down -v
docker volume prune
docker-compose up -d
```

### Installationsskript Probleme

#### "Permission denied" beim Ausführen
```bash
chmod +x install.sh
./install.sh
```

#### Apache startet nicht
```bash
# Ubuntu/Debian
sudo systemctl status apache2
sudo systemctl restart apache2

# CentOS/RHEL
sudo systemctl status httpd
sudo systemctl restart httpd

# Ports prüfen
sudo netstat -tlnp | grep :80
```

#### MariaDB startet nicht
```bash
sudo systemctl status mariadb
sudo systemctl restart mariadb

# Logs prüfen
sudo journalctl -u mariadb -f
```

#### mysql_secure_installation schlägt fehl
```bash
# MariaDB ohne Passwort neu konfigurieren
sudo mysql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'neupasswort';
FLUSH PRIVILEGES;
EXIT;
```

### Datenbankprobleme

#### "Database connection failed"
```bash
# 1. MariaDB Status prüfen
sudo systemctl status mariadb

# 2. Zugangsdaten testen
mysql -u browsergame -p browsergame

# 3. Benutzer neu erstellen
mysql -u root -p
CREATE USER 'browsergame'@'localhost' IDENTIFIED BY 'sicheresPasswort';
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'localhost';
FLUSH PRIVILEGES;
```

#### Tabellen existieren nicht
```bash
# Schema erneut importieren
mysql -u root -p browsergame < database.sql

# Oder über Docker
docker-compose exec db mysql -u root -pbrowsergame < /docker-entrypoint-initdb.d/01-init.sql
```

#### Event Scheduler funktioniert nicht
```bash
mysql -u root -p
SET GLOBAL event_scheduler = ON;
SHOW EVENTS;
```

### PHP Probleme

#### "Class 'PDO' not found"
```bash
# Ubuntu/Debian
sudo apt install php-mysql

# CentOS/RHEL
sudo yum install php-mysqlnd
```

#### PHP Version zu alt
```bash
# Ubuntu - PHP 8.1 installieren
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.1 php8.1-mysql php8.1-apache2
```

### Webserver Probleme

#### 404 Error - Seite nicht gefunden
```bash
# Apache DocumentRoot prüfen
sudo apache2ctl -S

# .htaccess und mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Permissions Fehler
```bash
# Ubuntu/Debian
sudo chown -R www-data:www-data /var/www/html/browsergame
sudo chmod -R 755 /var/www/html/browsergame

# CentOS/RHEL
sudo chown -R apache:apache /var/www/html/browsergame
sudo setsebool -P httpd_can_network_connect 1
```

### Spiel-spezifische Probleme

#### Admin Login funktioniert nicht
Standard-Zugangsdaten:
- Username: `admin`
- Password: `admin123`

#### Kein Spieler vorhanden
```bash
# Neuen Spieler erstellen
mysql -u root -p browsergame
CALL CreatePlayerWithSettlement('MeinSpieler');
```

#### Ressourcen werden nicht produziert
```bash
# Event Scheduler prüfen
mysql -u root -p browsergame
SHOW VARIABLES LIKE 'event_scheduler';
SET GLOBAL event_scheduler = ON;
SHOW EVENTS;
```

## 📞 Support und Hilfe

### Logs sammeln

**Docker:**
```bash
docker-compose logs > browsergame-docker.log
```

**Systemlogs:**
```bash
# Apache Logs
sudo tail -f /var/log/apache2/error.log

# MariaDB Logs
sudo journalctl -u mariadb -f

# PHP Logs
sudo tail -f /var/log/apache2/error.log | grep PHP
```

### Installation Check ausführen
```bash
# Im Browser öffnen
http://localhost/browsergame/installation-check.php
# oder für Docker
http://localhost:8080/installation-check.php
```

### Häufige Kommandos

**Docker:**
```bash
# Status prüfen
docker-compose ps

# Logs anzeigen
docker-compose logs -f

# Neustart
docker-compose restart

# Stoppen
docker-compose down

# Komplett zurücksetzen
docker-compose down -v
docker system prune -a
```

**System:**
```bash
# Services prüfen
sudo systemctl status apache2 mariadb

# Services neustarten
sudo systemctl restart apache2 mariadb

# Firewall (falls nötig)
sudo ufw allow 80/tcp
sudo ufw allow 3306/tcp
```

Bei anhaltenden Problemen erstelle ein Issue im GitHub Repository mit:
1. Betriebssystem und Version
2. Installationsmethode
3. Fehlermeldungen
4. Relevante Logs