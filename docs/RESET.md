# Browsergame Reset Guide / Reset-Anleitung

## Übersicht / Overview

Dieses Dokument beschreibt die verschiedenen Reset-Optionen für das Browsergame.
This document describes the different reset options for the Browsergame.

## 🔄 Verfügbare Reset-Skripte / Available Reset Scripts

### 1. Kompletter Reset: `reset.sh`

**Beschreibung / Description:**
- Setzt das komplette Spiel zurück / Resets the complete game
- Löscht alle Daten und initialisiert neu / Deletes all data and reinitializes
- Funktioniert mit Docker und manueller Installation / Works with Docker and manual installation

**Verwendung / Usage:**
```bash
# Interaktiv mit Bestätigung / Interactive with confirmation
./reset.sh

# Automatisch ohne Bestätigung / Automatic without confirmation
./reset.sh --force

# Hilfe anzeigen / Show help
./reset.sh --help
```

**Was wird zurückgesetzt / What gets reset:**
- ✅ Alle Spieler und Siedlungen / All players and settlements
- ✅ Alle Gebäude und Ressourcen / All buildings and resources
- ✅ Komplette Datenbank / Complete database
- ✅ Docker-Container (bei Docker-Setup) / Docker containers (if Docker setup)
- ✅ Warteschlangen und Events / Build queues and events

### 2. Nur Datenbank: `reset-database.sh`

**Beschreibung / Description:**
- Setzt nur die Datenbank zurück / Resets only the database
- Docker-Container und Webserver bleiben unverändert / Docker containers and web server remain unchanged
- Schneller als kompletter Reset / Faster than complete reset

**Verwendung / Usage:**
```bash
# Interaktiv mit Bestätigung / Interactive with confirmation
./reset-database.sh

# Automatisch ohne Bestätigung / Automatic without confirmation
./reset-database.sh --force

# Hilfe anzeigen / Show help
./reset-database.sh --help
```

**Was wird zurückgesetzt / What gets reset:**
- ✅ Alle Spieler und Siedlungen / All players and settlements
- ✅ Alle Gebäude und Ressourcen / All buildings and resources
- ✅ Warteschlangen und Events / Build queues and events
- ❌ Docker-Container bleiben aktiv / Docker containers stay active
- ❌ Webserver-Konfiguration unverändert / Web server configuration unchanged

### 3. Docker-integriert / Docker-integrated

**Beschreibung / Description:**
- Reset-Funktionen direkt im docker-start.sh Script / Reset functions directly in docker-start.sh script
- Nur für Docker-Setups verfügbar / Only available for Docker setups

**Verwendung / Usage:**
```bash
# Kompletter Reset / Complete reset
./docker-start.sh reset

# Nur Datenbank / Database only
./docker-start.sh reset-db

# Alle Optionen anzeigen / Show all options
./docker-start.sh help
```

## 🚨 Wichtige Warnungen / Important Warnings

### ⚠️ Datenverlust / Data Loss
- **ALLE SPIELDATEN GEHEN VERLOREN** / **ALL GAME DATA WILL BE LOST**
- Es werden **keine Backups** erstellt / **No backups** are created
- Der Vorgang ist **irreversibel** / The process is **irreversible**

### ⚠️ Nur für Entwicklung / Development Only
- Diese Skripte sind für **Entwicklungsumgebungen** gedacht / These scripts are intended for **development environments**
- **Nicht in Produktionsumgebungen** verwenden / **Do not use in production environments**
- Standardpasswörter werden wiederhergestellt / Default passwords are restored

## 📋 Nach dem Reset / After Reset

### Automatisch erstellt / Automatically created:
- Neuer Testspieler / New test player
- Grundgebäude / Basic buildings
- Startressourcen / Starting resources
- Event-Scheduler aktiviert / Event scheduler enabled

### Docker-Setup:
- **Spieler:** TestPlayer (Settlement ID: 1)
- **URL:** http://localhost:8080/index.php?settlementId=1

### Manuelle Installation:
- **Spieler:** Admin (Settlement ID: 1)  
- **URL:** http://localhost/browsergame/index.php?settlementId=1

### Standard-Zugangsdaten / Default Credentials:
- **Benutzername / Username:** admin
- **Passwort / Password:** admin123

## 🔧 Fehlerbehebung / Troubleshooting

### Script findet database.sql nicht / Script can't find database.sql
```bash
# Sicherstellen, dass Sie im richtigen Verzeichnis sind
# Make sure you're in the correct directory
cd /path/to/browsergame
ls -la database.sql
```

### Docker-Container laufen nicht / Docker containers not running
```bash
# Container starten / Start containers
./docker-start.sh

# Status prüfen / Check status
./docker-start.sh status
```

### Datenbankverbindung fehlgeschlagen / Database connection failed
```bash
# Bei Docker: Container neu starten / For Docker: restart containers
docker-compose restart

# Bei manueller Installation: MariaDB prüfen / For manual: check MariaDB
sudo systemctl status mariadb
```

### Permission denied Fehler / Permission denied errors
```bash
# Skript ausführbar machen / Make script executable
chmod +x reset.sh reset-database.sh

# Als root ausführen (wenn nötig) / Run as root (if needed)
sudo ./reset.sh
```

## 📝 Beispiel-Workflow / Example Workflow

### Schneller Datenbank-Reset für Entwicklung / Quick database reset for development:
```bash
# 1. Datenbank zurücksetzen / Reset database
./reset-database.sh --force

# 2. Spiel testen / Test game
curl http://localhost:8080/installation-check.php

# 3. Zum Spiel wechseln / Go to game
# Browser: http://localhost:8080/index.php?settlementId=1
```

### Kompletter Reset bei Problemen / Complete reset when having issues:
```bash
# 1. Alles zurücksetzen / Reset everything
./reset.sh --force

# 2. Status prüfen / Check status
./docker-start.sh status

# 3. Logs überprüfen / Check logs
./docker-start.sh logs
```

## 💡 Tipps / Tips

1. **Backup vor Reset:** Wenn wichtige Daten vorhanden sind, erstellen Sie ein Backup
   **Backup before reset:** If you have important data, create a backup first

2. **Force-Modus sparsam verwenden:** Nutzen Sie `--force` nur wenn Sie sicher sind
   **Use force mode sparingly:** Only use `--force` when you're certain

3. **Container-Status prüfen:** Bei Docker-Setup immer den Container-Status überprüfen
   **Check container status:** For Docker setups, always check container status

4. **Logs zur Fehlerbehebung:** Bei Problemen die Logs überprüfen
   **Logs for troubleshooting:** Check logs when having issues