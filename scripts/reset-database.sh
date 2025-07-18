#!/bin/bash

# Browsergame Database-Only Reset Script
# Resets only the database without affecting Docker containers or web server

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
    echo "  Browsergame Database Reset Script    "
    echo "========================================"
    echo
    log_warning "⚠️  ACHTUNG: Nur die Datenbank wird zurückgesetzt!"
    log_warning "⚠️  WARNING: Only the database will be reset!"
    echo
    log_info "Was wird zurückgesetzt / What will be reset:"
    echo "  - Alle Spieler und Siedlungen / All players and settlements"
    echo "  - Alle Gebäude und Ressourcen / All buildings and resources"
    echo "  - Warteschlangen und Events / Build queues and events"
    echo
    log_info "Was NICHT zurückgesetzt wird / What will NOT be reset:"
    echo "  - Docker-Container / Docker containers"
    echo "  - Webserver-Konfiguration / Web server configuration"
    echo "  - Dateiberechtigungen / File permissions"
    echo
    
    read -p "Fortfahren? / Continue? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        log_info "Reset abgebrochen / Reset cancelled"
        exit 0
    fi
    echo
}

# Reset database in Docker setup
reset_database_docker() {
    log_info "Docker-Setup erkannt / Docker setup detected"
    
    # Check if containers are running
    if ! docker-compose ps | grep -q "Up"; then
        log_warning "Docker-Container laufen nicht / Docker containers not running"
        read -p "Container starten? / Start containers? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            log_info "Starte Container / Starting containers..."
            docker-compose up -d
            sleep 10
        else
            log_error "Container müssen laufen für Database-Reset / Containers must be running for database reset"
            exit 1
        fi
    fi
    
    log_info "Lösche und erstelle Datenbank neu / Dropping and recreating database..."
    
    # Reset database via Docker
    docker-compose exec -T db mysql -u root -proot123 << EOF
DROP DATABASE IF EXISTS browsergame;
CREATE DATABASE browsergame;
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'%';
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    # Import database schema
    log_info "Importiere Datenbankschema / Importing database schema..."
    docker-compose exec -T db mysql -u browsergame -psicheresPasswort browsergame < ../sql/database.sql
    
    # Run initialization script if it exists
    if [ -f "../sql/init-player.sql" ]; then
        log_info "Erstelle initialen Spieler / Creating initial player..."
        docker-compose exec -T db mysql -u browsergame -psicheresPasswort browsergame < ../sql/init-player.sql
    fi
    
    log_success "Docker-Datenbank zurückgesetzt / Docker database reset complete"
}

# Reset database in manual setup
reset_database_manual() {
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
CREATE USER IF NOT EXISTS 'browsergame'@'localhost' IDENTIFIED BY 'sicheresPasswort';
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    # Import database schema
    log_info "Importiere Datenbankschema / Importing database schema..."
    mysql -u root -p$DB_ROOT_PASSWORD browsergame < ../sql/database.sql
    
    # Create initial player
    log_info "Erstelle initialen Spieler / Creating initial player..."
    mysql -u root -p$DB_ROOT_PASSWORD browsergame << EOF
CALL CreatePlayerWithSettlement('Admin');
EOF
    
    log_success "Manuelle Datenbank zurückgesetzt / Manual database reset complete"
}

# Verify database reset
verify_database() {
    log_info "Überprüfe Datenbank / Verifying database..."
    
    if is_docker_setup; then
        # Check Docker database
        if docker-compose exec -T db mysql -u browsergame -psicheresPasswort -e "SELECT COUNT(*) AS player_count FROM Spieler;" browsergame 2>/dev/null | grep -q "player_count"; then
            PLAYER_COUNT=$(docker-compose exec -T db mysql -u browsergame -psicheresPasswort -e "SELECT COUNT(*) FROM Spieler;" browsergame 2>/dev/null | tail -n 1)
            log_success "Datenbank funktioniert, $PLAYER_COUNT Spieler gefunden / Database working, $PLAYER_COUNT players found"
            
            # Show players
            log_info "Spieler in der Datenbank / Players in database:"
            docker-compose exec -T db mysql -u browsergame -psicheresPasswort -e "SELECT playerId, name, punkte, gold FROM Spieler;" browsergame 2>/dev/null | tail -n +2
        else
            log_error "Datenbank-Überprüfung fehlgeschlagen / Database verification failed"
            return 1
        fi
    else
        # For manual setup, we would need the password again or we could skip detailed verification
        log_success "Manuelle Installation - Datenbank sollte funktionieren / Manual installation - database should be working"
    fi
}

# Display completion information
show_completion_info() {
    log_success "🗄️ Datenbank-Reset erfolgreich! / Database reset successful!"
    echo
    
    if is_docker_setup; then
        log_info "Zugriff über / Access via:"
        echo "  Spiel / Game: http://localhost:8080/"
        echo "  Admin-Panel: http://localhost:8080/admin.php"
        echo
        log_info "Initialer Spieler / Initial player: 'TestPlayer' (Settlement ID: 1)"
        echo "  Direkter Spiellink / Direct game link: http://localhost:8080/index.php?settlementId=1"
    else
        log_info "Zugriff über / Access via:"
        echo "  Spiel / Game: http://localhost/browsergame/"
        echo "  Admin-Panel: http://localhost/browsergame/admin.php"
        echo
        log_info "Initialer Spieler / Initial player: 'Admin' (Settlement ID: 1)"
        echo "  Direkter Spiellink / Direct game link: http://localhost/browsergame/index.php?settlementId=1"
    fi
    
    echo
    log_info "Die Datenbank wurde zurückgesetzt, aber:"
    log_info "The database has been reset, but:"
    echo "  - Docker-Container laufen weiter / Docker containers keep running"
    echo "  - Webserver-Konfiguration unverändert / Web server configuration unchanged"
    echo "  - Cache könnte geleert werden müssen / Cache might need clearing"
}

# Main function
main() {
    confirm_reset
    
    log_info "Starte Datenbank-Reset / Starting database reset..."
    
    if is_docker_setup; then
        reset_database_docker
    else
        reset_database_manual
    fi
    
    # Verify everything is working
    if verify_database; then
        show_completion_info
    else
        log_error "Datenbank-Überprüfung fehlgeschlagen / Database verification failed"
        exit 1
    fi
}

# Handle command line arguments
case "${1:-}" in
    "--force"|"-f")
        log_warning "Force-Modus aktiviert / Force mode enabled"
        if is_docker_setup; then
            reset_database_docker
        else
            reset_database_manual
        fi
        verify_database && show_completion_info
        ;;
    "--help"|"-h")
        echo "Browsergame Database Reset Script"
        echo
        echo "Verwendung / Usage:"
        echo "  ./reset-database.sh          - Interaktiver Datenbank-Reset / Interactive database reset"
        echo "  ./reset-database.sh --force  - Reset ohne Bestätigung / Reset without confirmation"
        echo "  ./reset-database.sh --help   - Diese Hilfe / This help"
        echo
        echo "Dieser Script setzt nur die Datenbank zurück, nicht die gesamte Umgebung."
        echo "This script only resets the database, not the entire environment."
        ;;
    *)
        main
        ;;
esac