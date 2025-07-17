# Browsergame - Siedlungsaufbau Spiel

Ein webbasiertes Strategiespiel, bei dem Spieler ihre eigenen Siedlungen aufbauen und verwalten können. Baue Gebäude, sammle Ressourcen und erweitere deine Siedlung in diesem browserbasierten Aufbauspiel.

## 🚀 Quick Start

```bash
# Ubuntu/Debian Quick Install
sudo apt update && sudo apt install apache2 php php-mysql mariadb-server git -y
sudo mysql_secure_installation
cd /var/www/html
sudo git clone https://github.com/chris738/browsergame.git game
sudo chown -R www-data:www-data /var/www/html/game

# Datenbank einrichten
mysql -u root -p < /var/www/html/game/database.sql

# Installation überprüfen
# Browser: http://localhost/game/installation-check.php
```

## 🎮 Spielfeatures

- **Siedlungsverwaltung**: Baue und upgrade verschiedene Gebäude
- **Ressourcensystem**: Sammle und verwalte Holz, Stein und Erz
- **Echtzeitproduktion**: Ressourcen werden automatisch über die Zeit generiert
- **Bausystem**: Gebäude-Upgrades mit Warteschlange und Bauzeiten
- **Karte**: Siedlungen werden auf einer Koordinatenkarte platziert
- **Admin-Panel**: Vollständige Verwaltung von Spielern und Siedlungen

### Verfügbare Gebäude
- **Holzfäller**: Produziert Holz
- **Steinbruch**: Produziert Stein  
- **Erzbergwerk**: Produziert Erz
- **Lager**: Erhöht die Lagerkapazität für Ressourcen
- **Farm**: Stellt Siedler für andere Gebäude bereit

## 🛠️ Systemanforderungen

- **Webserver**: Apache 2.4+
- **PHP**: 7.4+ (empfohlen: 8.0+)
- **Datenbank**: MySQL 8.0+ oder MariaDB 10.4+
- **Browser**: Moderne Browser (Chrome, Firefox, Safari, Edge)

## 📋 Installation

> **⚠️ Wichtiger Hinweis**: Diese Anleitung richtet nur eine Entwicklungsumgebung ein und ist **nicht für Produktivsysteme geeignet**!

### Ubuntu/Debian Installation

#### 1. Systemvorbereitung
```bash
# System aktualisieren
sudo apt update && sudo apt upgrade -y

# Benötigte Pakete installieren
sudo apt install apache2 php php-mysql mariadb-server git -y
```

#### 2. Webserver konfigurieren
```bash
# Apache-Module aktivieren
sudo a2enmod rewrite
sudo systemctl restart apache2

# PHP-Konfiguration überprüfen
php --version
```

#### 3. Datenbank einrichten
```bash
# MariaDB sichern und konfigurieren
sudo mysql_secure_installation
```

Bei der Konfiguration:
- Root-Passwort setzen (z.B. `root123`)
- Anonyme Benutzer entfernen: **Y**
- Root-Remote-Login deaktivieren: **Y**
- Test-Datenbank entfernen: **Y**
- Privilegien neu laden: **Y**

#### 4. Projekt installieren
```bash
# Projekt klonen
cd /var/www/html
sudo git clone https://github.com/chris738/browsergame.git game
sudo chown -R www-data:www-data /var/www/html/game
sudo chmod -R 755 /var/www/html/game
```

#### 5. Datenbank aufsetzen
```bash
# Mit MySQL/MariaDB verbinden
mysql -u root -p

# Datenbankskript ausführen (in MySQL-Konsole):
```

```sql
-- Skript aus database.sql ausführen
source /var/www/html/game/database.sql;

-- Ersten Spieler erstellen
CALL CreatePlayerWithSettlement('DeinSpielerName');

-- Verbindung beenden
exit;
```

#### 6. Datenbankverbindung konfigurieren
Falls andere Zugangsdaten gewünscht sind, bearbeite die Datei `database.php`:
```php
private $host = 'localhost';
private $dbname = 'browsergame';
private $username = 'browsergame';  
private $password = 'sicheresPasswort';
```

### Windows (XAMPP) Installation

#### 1. XAMPP installieren
- Download von [xampp.org](https://www.apachefriends.org/)
- Apache und MySQL aktivieren

#### 2. Projekt einrichten
```batch
# Projekt nach xampp/htdocs kopieren
git clone https://github.com/chris738/browsergame.git C:\xampp\htdocs\game
```

#### 3. Datenbank einrichten
- phpMyAdmin öffnen: `http://localhost/phpmyadmin`
- Neue Datenbank "browsergame" erstellen
- SQL-Datei `database.sql` importieren
- In SQL-Tab ausführen: `CALL CreatePlayerWithSettlement('DeinSpielerName');`

### Docker Installation (Alternative)

```bash
# Docker-Compose erstellen
cat > docker-compose.yml << EOF
version: '3'
services:
  web:
    image: php:8.1-apache
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    depends_on:
      - db
  db:
    image: mariadb:10.9
    environment:
      MYSQL_ROOT_PASSWORD: root123
      MYSQL_DATABASE: browsergame
      MYSQL_USER: browsergame
      MYSQL_PASSWORD: sicheresPasswort
    ports:
      - "3306:3306"
EOF

# Container starten
docker-compose up -d
```

## ✅ Installation überprüfen

Nach der Installation kannst du mit diesem Script überprüfen, ob alles korrekt eingerichtet ist:

```
http://localhost/game/installation-check.php
```

Dieses Script überprüft:
- PHP-Version und Extensions
- Datenbankverbindung
- Dateiberechtigungen
- Verfügbarkeit aller Komponenten

## 🚀 Verwendung

### Spiel starten
1. Browser öffnen
2. Navigiere zu: `http://localhost/game/index.php?settlementId=1`
3. Beginne mit dem Aufbau deiner Siedlung!

### Admin-Panel
- URL: `http://localhost/game/admin.php`
- Standard-Zugangsdaten:
  - Benutzername: `admin`
  - Passwort: `admin123`

**Admin-Funktionen:**
- Spielerverwaltung (erstellen, bearbeiten, löschen)
- Siedlungsressourcen verwalten
- Bauaufträge überwachen und verwalten
- Systemstatistiken einsehen

## 🎯 Spielanleitung

1. **Ressourcen sammeln**: Deine Gebäude produzieren automatisch Ressourcen
2. **Gebäude upgraden**: Klicke auf "Upgrade" bei einem Gebäude
3. **Bauzeiten**: Upgrades dauern eine bestimmte Zeit und werden in der Warteschlange angezeigt
4. **Lagerkapazität**: Vergiss nicht dein Lager zu erweitern!
5. **Siedler**: Die Farm bestimmt, wie viele Siedler verfügbar sind

## 🔧 Konfiguration

### Datenbankverbindung ändern
Bearbeite `database.php` für andere Datenbankeinstellungen:
```php
private $host = 'dein-host';
private $dbname = 'dein-datenbankname';
private $username = 'dein-benutzer';
private $password = 'dein-passwort';
```

### Admin-Zugangsdaten ändern
In `admin.php` die Zeilen anpassen:
```php
// Ändere diese Werte für mehr Sicherheit
if ($username === 'admin' && $password === 'admin123') {
```

### Performance-Optimierung
Für bessere Performance können folgende MySQL-Einstellungen angepasst werden:
```sql
-- Event-Scheduler optimieren
SET GLOBAL event_scheduler = ON;

-- Tabellen-Cache erhöhen (optional)
SET GLOBAL table_open_cache = 2048;
```

## 🐛 Fehlerbehebung

### Häufige Probleme

#### "Database connection failed"
- Überprüfe MariaDB/MySQL-Status: `sudo systemctl status mariadb`
- Prüfe Zugangsdaten in `database.php`
- Stelle sicher, dass die Datenbank "browsergame" existiert

#### "Permission denied" Fehler
```bash
sudo chown -R www-data:www-data /var/www/html/game
sudo chmod -R 755 /var/www/html/game
```

#### Apache startet nicht
```bash
# Apache-Fehlerlog prüfen
sudo tail -f /var/log/apache2/error.log

# Apache neu starten
sudo systemctl restart apache2
```

#### Seite lädt nicht
- Überprüfe Apache-Status: `sudo systemctl status apache2`
- Überprüfe PHP-Installation: `php --version`
- Stelle sicher, dass mod_rewrite aktiviert ist: `sudo a2enmod rewrite`

#### MySQL-Event-Scheduler Probleme
```sql
-- Event-Scheduler aktivieren
SET GLOBAL event_scheduler = ON;

-- Events überprüfen
SHOW EVENTS;
```

### Logs überprüfen
```bash
# Apache-Logs
sudo tail -f /var/log/apache2/access.log
sudo tail -f /var/log/apache2/error.log

# PHP-Logs
sudo tail -f /var/log/apache2/error.log | grep PHP
```

## 📁 Projektstruktur

```
browsergame/
├── index.php              # Hauptspiel-Interface
├── backend.php            # API-Endpunkte für das Spiel
├── backend.js             # Frontend-JavaScript
├── style.css              # Haupt-Stylesheet
├── database.php           # Datenbankverbindung und -operationen
├── database.sql           # Datenbankschema und Initialdaten
├── installation-check.php # Installationsüberprüfung
├── admin.php              # Admin-Panel Interface
├── admin-backend.php      # Admin-API-Endpunkte
├── admin.js               # Admin-Panel JavaScript
├── admin.css              # Admin-Panel Stylesheet
├── map.php                # Kartenansicht
└── README.md              # Diese Datei
```

## 🔒 Sicherheitshinweise

> **⚠️ Nur für Entwicklungsumgebungen!**

Für Produktivumgebungen **unbedingt** beachten:
- Standard-Passwörter ändern
- HTTPS verwenden
- Firewall konfigurieren
- PHP-Sicherheitseinstellungen überprüfen
- Regelmäßige Updates
- Input-Validierung verschärfen
- Session-Sicherheit implementieren

## 📄 Lizenz

Dieses Projekt steht unter einer Open-Source-Lizenz. Siehe Repository für Details.

## 🤝 Beitragen

Contributions sind willkommen! Erstelle gerne Issues oder Pull Requests.

## 📞 Support

Bei Problemen oder Fragen erstelle ein Issue im GitHub-Repository.
