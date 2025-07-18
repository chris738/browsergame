# Browser Game - Settlement Building Game

A web-based strategy game for building and managing settlements. Build structures, collect resources, and expand your settlement in this browser-based building game.

## 🎮 Screenshots

### Main Game - Settlement View
![Main Game Interface](https://github.com/user-attachments/assets/8eb0dacd-41ab-4e82-b0f6-a85cf847512b)

The main view shows your settlement with all buildings, resources, and upgrade options featuring beautiful emoji icons.

### Map View
![Map View](https://github.com/user-attachments/assets/4b3d1e6c-7736-49d4-84ef-7943da853e58)

On the map you can see your settlement and other players in the area.

### Admin Panel
![Admin Login](https://github.com/user-attachments/assets/0c5a52d9-94ee-4cbb-b494-847c6d431d20)

The admin panel allows management of players and settlements.

## 🚀 Installation with Docker

### Prerequisites
- Docker 20.10+
- Docker Compose 2.0+

### Quick Start

```bash
# Clone repository
git clone https://github.com/chris738/browsergame.git
cd browsergame

# Start with Docker
docker compose up -d

# OR: Fresh Start for completely clean environment
./fresh-start.sh
```

That's it! The game runs at **http://localhost:8080** with fully activated automatic resource generation.

### Was passiert automatisch:
- ✅ Startet alle Docker Container (Web + Datenbank)
- ✅ Initialisiert die Datenbank komplett
- ✅ Aktiviert den Event Scheduler für automatische Ressourcengenerierung
- ✅ Erstellt einen Testspieler
- ✅ Überprüft dass alle Systeme funktionieren

### Zugriff
- **Spiel**: http://localhost:8080/
- **Admin Panel**: http://localhost:8080/admin.php
  - Username: `admin`
  - Password: `admin123`

### Docker Befehle

```bash
# Stoppen
docker compose down

# Neustarten
docker compose restart

# Logs anzeigen
docker compose logs -f

# Status prüfen
docker compose ps

# Komplett zurücksetzen (ALLE DATEN GEHEN VERLOREN!)
./fresh-start.sh --force

# Interaktiver Reset mit Bestätigung
./fresh-start.sh
```

## 🔄 Fresh Start Script - Neue Funktion!

Für eine garantiert saubere Entwicklungsumgebung nutze das neue **Fresh Start Script**:

```bash
# Komplett frische Umgebung (löscht ALLES!)
./fresh-start.sh

# Automatisch ohne Bestätigung
./fresh-start.sh --force

# Mit Entfernung aller Docker Images
./fresh-start.sh --force --remove-images
```

**Was macht das Fresh Start Script:**
- ✅ Löscht ALLE bestehenden Docker-Container, Volumes und Netzwerke
- ✅ Entfernt temporäre Dateien und Logs
- ✅ Erstellt komplett frische Umgebung von Grund auf
- ✅ Garantiert keine Altlasten oder Bug-verursachende Reste
- ✅ Ideal für saubere Entwicklungsumgebung

📖 **Detaillierte Dokumentation**: [docs/FRESH-START.md](docs/FRESH-START.md)

## 🎯 Spielfeatures

- **Siedlungsverwaltung**: Baue und upgrade verschiedene Gebäude
- **Ressourcensystem**: Sammle und verwalte Holz, Stein und Erz
- **Echtzeitproduktion**: Ressourcen werden automatisch über die Zeit generiert
- **Bausystem**: Gebäude-Upgrades mit Warteschlange und Bauzeiten
- **Karte**: Siedlungen werden auf einer Koordinatenkarte platziert
- **Admin-Panel**: Vollständige Verwaltung von Spielern und Siedlungen

### Verfügbare Gebäude
- **Rathaus**: Zentrum der Siedlung
- **Holzfäller**: Produziert Holz
- **Steinbruch**: Produziert Stein  
- **Erzbergwerk**: Produziert Erz
- **Lager**: Erhöht die Lagerkapazität für Ressourcen
- **Farm**: Stellt Siedler für andere Gebäude bereit

## 🎮 Schnellanleitung

1. **Spiel öffnen**: Navigiere zu http://localhost:8080/
2. **Ressourcen sammeln**: Deine Gebäude produzieren automatisch Ressourcen
3. **Gebäude upgraden**: Klicke auf "Upgrade" bei einem Gebäude
4. **Bauzeiten**: Upgrades dauern eine bestimmte Zeit und werden in der Warteschlange angezeigt
5. **Lagerkapazität**: Vergiss nicht dein Lager zu erweitern!

## 🔧 Fehlerbehebung

### Häufige Probleme

**Port 8080 bereits belegt:**
```bash
# Anderen Port verwenden (z.B. 8081)
sed -i 's/8080:80/8081:80/g' docker-compose.yml
docker compose up -d
```

**Container starten nicht:**
```bash
# Logs prüfen
docker compose logs

# Neustart mit Rebuild
docker compose down
docker compose up -d --build
```

**Datenbank-Probleme:**
```bash
# Volumes löschen und neu erstellen
docker compose down -v
docker volume prune -f
docker compose up -d
```

## 📄 Weitere Dokumentation

Detaillierte Installations- und Konfigurationsanleitungen findest du im `docs/` Verzeichnis:
- [Ausführliche README](docs/README.md)
- [Installation Guide](docs/INSTALLATION.md)
- [Admin Documentation](docs/ADMIN_README.md)

## 🔒 Sicherheitshinweis

⚠️ **Nur für Entwicklungsumgebungen!**

Für Produktivumgebungen **unbedingt** Standard-Passwörter ändern und weitere Sicherheitsmaßnahmen implementieren.

## 🤝 Beitragen

Contributions sind willkommen! Erstelle gerne Issues oder Pull Requests.

## 📞 Support

Bei Problemen oder Fragen erstelle ein Issue im GitHub-Repository.