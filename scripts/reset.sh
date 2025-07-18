#!/bin/bash

# Browsergame Complete Reset Script
# Resets the entire game including database and reinitializes everything

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running in Docker environment
is_docker_setup() {
    [ -f "docker-compose.yml" ] && command -v docker-compose &> /dev/null
}

# Confirm reset action
confirm_reset() {
    echo "========================================"
    echo "   Browsergame Complete Reset Script   "
    echo "========================================"
    echo
    log_warning "⚠️  ACHTUNG: Dieser Vorgang wird ALLE Daten löschen!"
    log_warning "⚠️  WARNING: This will DELETE ALL game data!"
    echo
    log_info "Was wird zurückgesetzt / What will be reset:"
    echo "  - Alle Spieler und Siedlungen / All players and settlements"
    echo "  - Alle Gebäude und Ressourcen / All buildings and resources"
    echo "  - Komplett Datenbank / Complete database"
    echo "  - Warteschlangen und Events / Build queues and events"
    echo
    
    read -p "Sind Sie sicher, dass Sie fortfahren möchten? / Are you sure you want to continue? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        log_info "Reset abgebrochen / Reset cancelled"
        exit 0
    fi
    echo
}

# Reset Docker setup
reset_docker() {
    log_info "Docker-Setup erkannt / Docker setup detected"
    log_info "Stoppe Docker-Container / Stopping Docker containers..."
    
    # Stop and remove containers
    docker-compose down -v 2>/dev/null || true
    
    # Remove database volume to ensure complete reset
    log_info "Lösche Datenbank-Volume / Removing database volume..."
    docker volume rm $(docker-compose config --volumes) 2>/dev/null || true
    
    # Clean up any orphaned containers
    docker container prune -f 2>/dev/null || true
    
    log_success "Docker-Container gestoppt / Docker containers stopped"
    
    # Rebuild and restart
    log_info "Starte Docker-Setup neu / Restarting Docker setup..."
    docker-compose up -d --build
    
    # Wait for database to be ready
    log_info "Warte auf Datenbank / Waiting for database..."
    sleep 15
    
    # Verify database is working
    if docker-compose exec -T db mysql -u browsergame -psicheresPasswort -e "SELECT 1" browsergame >/dev/null 2>&1; then
        log_success "Datenbank ist bereit / Database is ready"
    else
        log_error "Datenbank-Verbindung fehlgeschlagen / Database connection failed"
        exit 1
    fi
}

# Reset manual installation
reset_manual() {
    log_info "Manuelle Installation erkannt / Manual installation detected"
    
    # Prompt for database credentials
    read -s -p "MariaDB/MySQL Root-Passwort eingeben / Enter MariaDB/MySQL root password: " DB_ROOT_PASSWORD
    echo
    
    # Test database connection
    if ! mysql -u root -p$DB_ROOT_PASSWORD -e "SELECT 1" >/dev/null 2>&1; then
        log_error "Datenbank-Verbindung fehlgeschlagen / Database connection failed"
        exit 1
    fi
    
    log_info "Lösche und erstelle Datenbank neu / Dropping and recreating database..."
    
    # Drop and recreate database
    mysql -u root -p$DB_ROOT_PASSWORD << EOF
DROP DATABASE IF EXISTS browsergame;
CREATE DATABASE browsergame;
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    # Import database schema
    if [ -f "../sql/database.sql" ]; then
        log_info "Importiere Datenbankschema / Importing database schema..."
        mysql -u root -p$DB_ROOT_PASSWORD browsergame < ../sql/database.sql
        log_success "Datenbankschema importiert / Database schema imported"
    else
        log_error "../sql/database.sql nicht gefunden! / ../sql/database.sql not found!"
        exit 1
    fi
    
    # Create initial player
    log_info "Erstelle initialen Spieler / Creating initial player..."
    mysql -u root -p$DB_ROOT_PASSWORD browsergame << EOF
CALL CreatePlayerWithSettlement('Admin');
EOF
    
    # Restart web server (if systemd services are available)
    if command -v systemctl &> /dev/null; then
        log_info "Starte Webserver neu / Restarting web server..."
        if systemctl is-active --quiet apache2; then
            sudo systemctl restart apache2
        elif systemctl is-active --quiet httpd; then
            sudo systemctl restart httpd
        fi
    fi
}

# Verify reset completion
verify_reset() {
    log_info "Überprüfe Reset / Verifying reset..."
    
    if is_docker_setup; then
        # Check Docker setup
        if docker-compose ps | grep -q "Up"; then
            # Test database connection
            if docker-compose exec -T db mysql -u browsergame -psicheresPasswort -e "SELECT COUNT(*) FROM Spieler;" browsergame >/dev/null 2>&1; then
                PLAYER_COUNT=$(docker-compose exec -T db mysql -u browsergame -psicheresPasswort -e "SELECT COUNT(*) FROM Spieler;" browsergame 2>/dev/null | tail -n 1)
                log_success "Datenbank funktioniert, $PLAYER_COUNT Spieler gefunden / Database working, $PLAYER_COUNT players found"
            else
                log_error "Datenbank-Verbindung fehlgeschlagen / Database connection failed"
                return 1
            fi
        else
            log_error "Docker-Container laufen nicht / Docker containers not running"
            return 1
        fi
    else
        # Check manual setup
        if command -v mysql &> /dev/null; then
            log_info "Manuelle Installation überprüft / Manual installation verified"
        fi
    fi
}

# Display completion information
show_completion_info() {
    log_success "🎮 Reset erfolgreich abgeschlossen! / Reset completed successfully!"
    echo
    
    if is_docker_setup; then
        log_info "Zugriff über / Access via:"
        echo "  Spiel / Game: http://localhost:8080/"
        echo "  Admin-Panel: http://localhost:8080/admin.php"
        echo "  Installation Check: http://localhost:8080/installation-check.php"
        echo
        log_info "Standard-Zugangsdaten / Default credentials:"
        echo "  Benutzername / Username: admin"
        echo "  Passwort / Password: admin123"
        echo
        log_info "Initialer Spieler / Initial player: 'TestPlayer' (Settlement ID: 1)"
        echo "  Direkter Spiellink / Direct game link: http://localhost:8080/index.php?settlementId=1"
    else
        log_info "Zugriff über / Access via:"
        echo "  Spiel / Game: http://localhost/browsergame/"
        echo "  Admin-Panel: http://localhost/browsergame/admin.php"
        echo "  Installation Check: http://localhost/browsergame/installation-check.php"
        echo
        log_info "Standard-Zugangsdaten / Default credentials:"
        echo "  Benutzername / Username: admin"
        echo "  Passwort / Password: admin123"
        echo
        log_info "Initialer Spieler / Initial player: 'Admin' (Settlement ID: 1)"
        echo "  Direkter Spiellink / Direct game link: http://localhost/browsergame/index.php?settlementId=1"
    fi
    
    echo
    log_info "Docker-Befehle / Docker commands:"
    echo "  Status: docker-compose ps"
    echo "  Logs: docker-compose logs -f"
    echo "  Stoppen: docker-compose down"
    echo
    log_warning "⚠️ Dies ist nur für Entwicklungsumgebungen geeignet! / This is for development environments only!"
}

# Main function
main() {
    confirm_reset
    
    log_info "Starte Reset-Prozess / Starting reset process..."
    
    if is_docker_setup; then
        reset_docker
    else
        reset_manual
    fi
    
    # Verify everything is working
    if verify_reset; then
        show_completion_info
    else
        log_error "Reset-Überprüfung fehlgeschlagen / Reset verification failed"
        exit 1
    fi
}

# Handle command line arguments
case "${1:-}" in
    "--force"|"-f")
        log_warning "Force-Modus aktiviert / Force mode enabled"
        # Skip confirmation in force mode
        if is_docker_setup; then
            reset_docker
        else
            reset_manual
        fi
        verify_reset && show_completion_info
        ;;
    "--help"|"-h")
        echo "Browsergame Reset Script"
        echo
        echo "Verwendung / Usage:"
        echo "  ./reset.sh          - Interaktiver Reset / Interactive reset"
        echo "  ./reset.sh --force  - Reset ohne Bestätigung / Reset without confirmation"
        echo "  ./reset.sh --help   - Diese Hilfe / This help"
        echo
        echo "Das Skript erkennt automatisch Docker- oder manuelle Installation."
        echo "The script automatically detects Docker or manual installation."
        ;;
    *)
        main
        ;;
esac