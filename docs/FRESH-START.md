# Fresh Start Script - Dokumentation

## Überblick / Overview

Das Fresh Start Script (`fresh-start.sh`) ist ein umfassendes All-in-One Skript, das eine komplett frische Browsergame-Umgebung erstellt. Es löscht ALLE bestehenden Daten und setzt das gesamte System zurück, um eine saubere Entwicklungsumgebung ohne Altlasten zu gewährleisten.

The Fresh Start Script (`fresh-start.sh`) is a comprehensive all-in-one script that creates a completely fresh Browsergame environment. It deletes ALL existing data and resets the entire system to provide a clean development environment without legacy issues.

## Verwendung / Usage

```bash
# Interaktiver Fresh Start (empfohlen)
./fresh-start.sh

# Automatischer Fresh Start ohne Bestätigung
./fresh-start.sh --force

# Vollständiger Reset inklusive Docker Images
./fresh-start.sh --force --remove-images

# Mit ausführlicher Ausgabe für Debugging
./fresh-start.sh --verbose

# Hilfe anzeigen
./fresh-start.sh --help
```

## Optionen / Options

| Option | Beschreibung / Description |
|--------|---------------------------|
| `--force`, `-f` | Keine Bestätigung erforderlich / No confirmation required |
| `--remove-images` | Docker Images ebenfalls entfernen / Also remove Docker images |
| `--verbose`, `-v` | Ausführliche Ausgabe / Verbose output |
| `--help`, `-h` | Hilfe anzeigen / Show help |

## Was wird gelöscht / What gets deleted

### Docker-Komponenten / Docker Components
- ❌ Alle Container / All containers
- ❌ Alle Volumes / All volumes  
- ❌ Alle Netzwerke / All networks
- ❌ Build-Cache / Build cache
- ❌ Docker Images (mit `--remove-images` / with `--remove-images`)

### Daten / Data
- ❌ Komplette Datenbank / Complete database
- ❌ Alle Spielerdaten / All player data
- ❌ Alle Siedlungen / All settlements
- ❌ Bauqueues / Building queues
- ❌ Events / Events

### Dateien / Files
- ❌ Temporäre Dateien / Temporary files
- ❌ Log-Dateien / Log files
- ❌ Backup-Dateien / Backup files
- ❌ Lokale Konfigurationsdateien / Local config files

## Was wird neu erstellt / What gets created fresh

### Docker-Umgebung / Docker Environment
- ✅ Neue Container aus frischen Images / New containers from fresh images
- ✅ Neue Volumes / New volumes
- ✅ Neue Netzwerke / New networks

### Datenbank / Database
- ✅ Frische MariaDB-Installation / Fresh MariaDB installation
- ✅ Komplettes Schema / Complete schema
- ✅ Event Scheduler aktiviert / Event scheduler enabled
- ✅ Alle Events funktionsfähig / All events functional

### Spieldaten / Game Data
- ✅ Test-Spieler "TestPlayer" / Test player "TestPlayer"
- ✅ Startsiedlung mit Ressourcen / Starting settlement with resources
- ✅ Automatische Ressourcenproduktion / Automatic resource production

## Skriptphasen / Script Phases

Das Skript durchläuft 8 Hauptphasen / The script goes through 8 main phases:

1. **Voraussetzungen prüfen / Check Prerequisites**
   - Docker-Installation / Docker installation
   - Docker Compose / Docker Compose
   - Projektstruktur / Project structure

2. **Docker-Cleanup**
   - Container stoppen / Stop containers
   - Volumes entfernen / Remove volumes
   - Netzwerke säubern / Clean networks
   - Cache leeren / Clear cache

3. **Dateisystem-Cleanup**
   - Temporäre Dateien / Temporary files
   - Logs entfernen / Remove logs
   - Backup-Dateien löschen / Delete backups

4. **Frische Umgebung erstellen**
   - Container neu bauen / Rebuild containers
   - Services starten / Start services

5. **Datenbank-Bereitschaft**
   - Auf Datenbank warten / Wait for database
   - Verbindung testen / Test connection

6. **Datenbank-Verifikation**
   - Schema überprüfen / Verify schema
   - Spielerdaten prüfen / Check player data

7. **Event Scheduler**
   - Scheduler-Status / Scheduler status
   - Events überprüfen / Verify events

8. **Finale Verifikation**
   - Container-Status / Container status
   - Web-Service testen / Test web service

## Sicherheitshinweise / Security Notes

⚠️ **WICHTIG / IMPORTANT:**

- **Nur für Entwicklung / Development Only**: Dieses Skript ist NUR für Entwicklungsumgebungen gedacht!
- **Datenverlust / Data Loss**: ALLE Daten gehen unwiderruflich verloren!
- **Produktionsumgebung / Production**: Niemals in Produktionsumgebungen verwenden!

## Fehlerbehebung / Troubleshooting

### Docker-Probleme / Docker Issues

```bash
# Docker-Status prüfen
docker --version
docker compose version
docker info

# Container-Status
docker compose ps

# Logs anzeigen
docker compose logs -f
```

### Netzwerk-Probleme / Network Issues

```bash
# Port-Konflikte prüfen
netstat -tlnp | grep :8080
lsof -i :8080

# Docker-Netzwerke
docker network ls
```

### Datenbank-Probleme / Database Issues

```bash
# Datenbank-Verbindung testen
docker compose exec db mysql -u browsergame -psicheresPasswort browsergame -e "SELECT 1;"

# Event Scheduler Status
docker compose exec db mysql -u browsergame -psicheresPasswort browsergame -e "SHOW VARIABLES LIKE 'event_scheduler';"

# Events anzeigen
docker compose exec db mysql -u browsergame -psicheresPasswort browsergame -e "SHOW EVENTS;"
```

## Integration mit bestehenden Skripten

Das Fresh Start Script nutzt die bestehende Infrastruktur:

- `docker-compose.yml` - Docker-Konfiguration
- `sql/database.sql` - Datenbankschema  
- `sql/init-player.sql` - Initialer Spieler
- `.env.example` - Umgebungsvariablen

## Vergleich mit anderen Skripten / Comparison with other scripts

| Script | Zweck / Purpose | Umfang / Scope |
|--------|----------------|----------------|
| `fresh-start.sh` | **Kompletter Reset** / Complete reset | Alles / Everything |
| `reset.sh` | Datenbank & Container / Database & containers | Mittel / Medium |
| `rebuild-database.sh` | Nur Datenbank / Database only | Begrenzt / Limited |
| `docker-start.sh` | Start & Verifikation / Start & verification | Start only |

## Beispiele / Examples

### Schneller Neustart für Entwicklung
```bash
./fresh-start.sh --force
```

### Debugging mit ausführlicher Ausgabe
```bash
./fresh-start.sh --force --verbose 2>&1 | tee fresh-start.log
```

### Kompletter Reset inklusive Images
```bash
./fresh-start.sh --force --remove-images
```

### Sicherer interaktiver Reset
```bash
./fresh-start.sh
# Bestätigung mit: "KOMPLETT LÖSCHEN" oder "DELETE EVERYTHING"
```

## Nach dem Fresh Start

Nach erfolgreichem Abschluss:

- **Spiel**: http://localhost:8080/
- **Admin**: http://localhost:8080/admin.php (admin/admin123)
- **Test-Spieler**: http://localhost:8080/index.php?settlementId=1

Das System ist vollständig funktionsfähig mit automatischer Ressourcenproduktion!