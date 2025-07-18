# Browsergame - Siedlungsaufbau Spiel

Ein webbasiertes Strategiespiel zum Aufbau und zur Verwaltung von Siedlungen. Baue Gebäude, sammle Ressourcen und erweitere deine Siedlung in diesem browserbasierten Aufbauspiel.

## 🎮 Screenshots

### Hauptspiel - Siedlungsansicht
![Hauptspiel Interface](https://github.com/user-attachments/assets/3a747612-0332-4ff7-a4aa-cfb3661197f7)

Die Hauptansicht zeigt deine Siedlung mit allen Gebäuden, Ressourcen und Upgrade-Möglichkeiten.

### Kartenansicht
![Kartenansicht](https://github.com/user-attachments/assets/4b3d1e6c-7736-49d4-84ef-7943da853e58)

Auf der Karte siehst du deine Siedlung und andere Spieler in der Umgebung.

### Admin-Panel
![Admin Login](https://github.com/user-attachments/assets/0c5a52d9-94ee-4cbb-b494-847c6d431d20)

Das Admin-Panel ermöglicht die Verwaltung von Spielern und Siedlungen.

## 🚀 Installation mit Docker

### Voraussetzungen
- Docker 20.10+
- Docker Compose 2.0+

### Schnellstart

```bash
# Repository klonen
git clone https://github.com/chris738/browsergame.git
cd browsergame

# Mit Docker starten
docker compose up -d
```

Das war's! Das Spiel läuft unter **http://localhost:8080** mit vollständig aktivierter automatischer Ressourcengenerierung.

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
docker compose down -v
docker volume prune -f
docker compose up -d
```

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