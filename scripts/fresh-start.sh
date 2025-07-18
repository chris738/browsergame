#!/bin/bash

# Browsergame Fresh Start Script
# All-in-One script für kompletten Neuaufbau der Spielumgebung
# Löscht ALLES und setzt eine völlig frische Umgebung auf
# 
# All-in-One script for complete fresh game environment setup
# Deletes EVERYTHING and sets up a completely fresh environment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Script configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
FORCE_MODE=false
REMOVE_IMAGES=false
VERBOSE=false

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

log_debug() {
    if [ "$VERBOSE" = true ]; then
        echo -e "${PURPLE}[DEBUG]${NC} $1"
    fi
}

log_step() {
    echo -e "${CYAN}[STEP]${NC} $1"
}

# Print banner
print_banner() {
    echo "=================================================================="
    echo "   🚀 Browsergame Fresh Start Script / Frischer Neustart 🚀    "
    echo "=================================================================="
    echo
    log_warning "⚠️  ACHTUNG / ATTENTION ⚠️"
    echo "Dieses Skript löscht ALLES und erstellt eine komplett frische Umgebung!"
    echo "This script deletes EVERYTHING and creates a completely fresh environment!"
    echo
    log_info "Was wird gelöscht / What will be deleted:"
    echo "  ❌ Alle Docker Container / All Docker containers"
    echo "  ❌ Alle Docker Volumes / All Docker volumes"
    echo "  ❌ Alle Docker Networks / All Docker networks"
    echo "  ❌ Alle Spielerdaten / All player data"
    echo "  ❌ Komplette Datenbank / Complete database"
    echo "  ❌ Temporäre Dateien / Temporary files"
    if [ "$REMOVE_IMAGES" = true ]; then
        echo "  ❌ Docker Images (--remove-images flag aktiv)"
    fi
    echo
    log_success "Was wird neu erstellt / What will be created fresh:"
    echo "  ✅ Neue Docker Container / New Docker containers"
    echo "  ✅ Frische Datenbank / Fresh database"
    echo "  ✅ Event Scheduler aktiviert / Event scheduler enabled"
    echo "  ✅ Test-Spieler erstellt / Test player created"
    echo "  ✅ Alle Services gestartet / All services started"
    echo
}

# Confirm action
confirm_action() {
    if [ "$FORCE_MODE" = true ]; then
        log_warning "Force-Modus aktiv - überspringe Bestätigung / Force mode active - skipping confirmation"
        return 0
    fi
    
    print_banner
    
    read -p "Sind Sie absolut sicher? / Are you absolutely sure? (KOMPLETT LÖSCHEN/DELETE EVERYTHING): " -r
    if [[ ! $REPLY =~ ^(KOMPLETT LÖSCHEN|DELETE EVERYTHING)$ ]]; then
        log_info "Vorgang abgebrochen / Operation cancelled"
        echo "Tipp: Verwende 'KOMPLETT LÖSCHEN' oder 'DELETE EVERYTHING' zur Bestätigung"
        echo "Tip: Use 'KOMPLETT LÖSCHEN' or 'DELETE EVERYTHING' to confirm"
        exit 0
    fi
    echo
}

# Check prerequisites
check_prerequisites() {
    log_step "1. Überprüfe Voraussetzungen / Checking prerequisites"
    
    # Check if we're in the right directory
    if [[ ! -f "$PROJECT_ROOT/docker-compose.yml" ]]; then
        log_error "docker-compose.yml nicht gefunden! / docker-compose.yml not found!"
        log_error "Skript muss vom Projektverzeichnis ausgeführt werden / Script must be run from project directory"
        exit 1
    fi
    
    # Check Docker
    if ! command -v docker &> /dev/null; then
        log_error "Docker ist nicht installiert! / Docker is not installed!"
        echo "Besuche / Visit: https://docs.docker.com/get-docker/"
        exit 1
    fi
    
    # Check Docker Compose
    if docker compose version &> /dev/null; then
        DOCKER_COMPOSE_CMD="docker compose"
        log_debug "Verwende docker compose (moderne Syntax) / Using docker compose (modern syntax)"
    elif command -v docker-compose &> /dev/null; then
        DOCKER_COMPOSE_CMD="docker-compose"
        log_debug "Verwende docker-compose (legacy) / Using docker-compose (legacy)"
    else
        log_error "Docker Compose nicht gefunden! / Docker Compose not found!"
        exit 1
    fi
    
    # Check if Docker is running
    if ! docker info &> /dev/null; then
        log_error "Docker läuft nicht! / Docker is not running!"
        echo "Starte Docker und versuche es erneut / Start Docker and try again"
        exit 1
    fi
    
    log_success "Alle Voraussetzungen erfüllt / All prerequisites met"
}

# Stop and remove everything Docker-related
cleanup_docker() {
    log_step "2. Stoppe und entferne Docker-Komponenten / Stopping and removing Docker components"
    
    cd "$PROJECT_ROOT"
    
    # Stop containers if running
    log_info "Stoppe Container / Stopping containers..."
    $DOCKER_COMPOSE_CMD down --remove-orphans 2>/dev/null || true
    
    # Remove volumes with force
    log_info "Entferne Volumes / Removing volumes..."
    $DOCKER_COMPOSE_CMD down -v 2>/dev/null || true
    
    # Get project name from docker-compose
    PROJECT_NAME=$(basename "$PROJECT_ROOT" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9]//g')
    log_debug "Projektname / Project name: $PROJECT_NAME"
    
    # Remove all project-related volumes
    log_info "Entferne projekt-spezifische Volumes / Removing project-specific volumes..."
    docker volume ls -q | grep -E "${PROJECT_NAME}|browsergame" | xargs -r docker volume rm -f 2>/dev/null || true
    
    # Remove networks
    log_info "Entferne Netzwerke / Removing networks..."
    docker network ls -q | grep -E "${PROJECT_NAME}|browsergame" | xargs -r docker network rm 2>/dev/null || true
    
    # Remove containers (force)
    log_info "Entferne verwaiste Container / Removing orphaned containers..."
    docker container ls -aq | xargs -r docker container rm -f 2>/dev/null || true
    
    # Remove images if requested
    if [ "$REMOVE_IMAGES" = true ]; then
        log_info "Entferne Docker Images / Removing Docker images..."
        docker images -q "${PROJECT_NAME}*" | xargs -r docker rmi -f 2>/dev/null || true
        docker images -q "*browsergame*" | xargs -r docker rmi -f 2>/dev/null || true
        # Remove dangling images
        docker image prune -f 2>/dev/null || true
    fi
    
    # Clean up build cache
    log_info "Säubere Build-Cache / Cleaning build cache..."
    docker builder prune -f 2>/dev/null || true
    
    log_success "Docker-Cleanup abgeschlossen / Docker cleanup completed"
}

# Clean temporary files
cleanup_files() {
    log_step "3. Säubere temporäre Dateien / Cleaning temporary files"
    
    cd "$PROJECT_ROOT"
    
    # Remove temporary directories
    log_info "Entferne temporäre Verzeichnisse / Removing temporary directories..."
    rm -rf ./tmp/ ./cache/ ./sessions/ ./docker/data/ ./docker/logs/ 2>/dev/null || true
    
    # Remove log files
    log_info "Entferne Log-Dateien / Removing log files..."
    find . -name "*.log" -not -path "./.git/*" -delete 2>/dev/null || true
    find . -name "*.tmp" -not -path "./.git/*" -delete 2>/dev/null || true
    
    # Remove backup files
    log_info "Entferne Backup-Dateien / Removing backup files..."
    find . -name "*.bak" -not -path "./.git/*" -delete 2>/dev/null || true
    find . -name "*.backup" -not -path "./.git/*" -delete 2>/dev/null || true
    find . -name "database_backup_*" -not -path "./.git/*" -delete 2>/dev/null || true
    
    # Remove environment override files
    log_info "Entferne lokale Konfigurationsdateien / Removing local config files..."
    rm -f .env .env.local config.local.php database.local.php 2>/dev/null || true
    
    log_success "Dateisystem-Cleanup abgeschlossen / Filesystem cleanup completed"
}

# Build fresh environment
build_fresh() {
    log_step "4. Erstelle frische Umgebung / Building fresh environment"
    
    cd "$PROJECT_ROOT"
    
    # Create .env from example if it doesn't exist
    if [[ -f ".env.example" && ! -f ".env" ]]; then
        log_info "Erstelle .env aus Vorlage / Creating .env from template..."
        cp .env.example .env
    fi
    
    # Build and start with fresh containers
    log_info "Erstelle und starte Container (frisch) / Building and starting containers (fresh)..."
    $DOCKER_COMPOSE_CMD build --no-cache --pull
    
    log_info "Starte Services / Starting services..."
    $DOCKER_COMPOSE_CMD up -d
    
    log_success "Frische Umgebung erstellt / Fresh environment created"
}

# Wait for database to be ready
wait_for_database() {
    log_step "5. Warte auf Datenbank / Waiting for database"
    
    cd "$PROJECT_ROOT"
    
    local max_attempts=60
    local attempt=1
    
    log_info "Warte bis Datenbank bereit ist / Waiting for database to be ready..."
    
    # Initial wait for container to start properly
    sleep 5
    
    while [ $attempt -le $max_attempts ]; do
        # First check if database container is running
        if ! $DOCKER_COMPOSE_CMD ps db | grep -q "Up"; then
            log_error "Datenbank-Container läuft nicht / Database container is not running"
            return 1
        fi
        
        # Then check if database is responding
        if $DOCKER_COMPOSE_CMD exec -T db mysqladmin ping -h localhost -u browsergame -psicheresPasswort >/dev/null 2>&1; then
            log_success "Datenbank ist bereit! / Database is ready!"
            # Additional wait to ensure full initialization
            sleep 3
            return 0
        fi
        
        if [ $attempt -eq $max_attempts ]; then
            log_error "Datenbank-Timeout nach $max_attempts Versuchen / Database timeout after $max_attempts attempts"
            log_info "Zeige Container-Logs / Showing container logs..."
            $DOCKER_COMPOSE_CMD logs db
            return 1
        fi
        
        if [ $((attempt % 10)) -eq 0 ]; then
            log_info "Noch warten... Versuch $attempt/$max_attempts / Still waiting... attempt $attempt/$max_attempts"
        fi
        
        sleep 2
        attempt=$((attempt + 1))
    done
}

# Initialize and verify database
verify_database() {
    log_step "6. Überprüfe und initialisiere Datenbank / Verifying and initializing database"
    
    cd "$PROJECT_ROOT"
    
    # Check database connection
    log_info "Teste Datenbankverbindung / Testing database connection..."
    
    # Try multiple times with increasing delays
    local conn_attempts=5
    local conn_attempt=1
    
    while [ $conn_attempt -le $conn_attempts ]; do
        if $DOCKER_COMPOSE_CMD exec -T db mysql -u browsergame -psicheresPasswort -e "SELECT 1;" browsergame >/dev/null 2>&1; then
            log_success "Datenbankverbindung erfolgreich / Database connection successful"
            break
        fi
        
        if [ $conn_attempt -eq $conn_attempts ]; then
            log_error "Datenbankverbindung fehlgeschlagen nach $conn_attempts Versuchen / Database connection failed after $conn_attempts attempts"
            log_info "Zeige Datenbankcontainer-Status / Showing database container status:"
            $DOCKER_COMPOSE_CMD ps db
            log_info "Zeige letzte Datenbanklogeinträge / Showing recent database logs:"
            $DOCKER_COMPOSE_CMD logs --tail=20 db
            return 1
        fi
        
        log_info "Verbindungsversuch $conn_attempt/$conn_attempts fehlgeschlagen, warte... / Connection attempt $conn_attempt/$conn_attempts failed, waiting..."
        sleep 5
        conn_attempt=$((conn_attempt + 1))
    done
    
    # Check if tables exist
    log_info "Überprüfe Tabellen / Checking tables..."
    local table_count=$($DOCKER_COMPOSE_CMD exec -T db mysql -u browsergame -psicheresPasswort -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'browsergame';" 2>/dev/null | tail -n 1)
    
    if [ "$table_count" -gt 0 ]; then
        log_success "Datenbank-Schema geladen, $table_count Tabellen gefunden / Database schema loaded, $table_count tables found"
    else
        log_error "Keine Tabellen in der Datenbank gefunden / No tables found in database"
        return 1
    fi
    
    # Check players
    local player_count=$($DOCKER_COMPOSE_CMD exec -T db mysql -u browsergame -psicheresPasswort -e "SELECT COUNT(*) FROM Spieler;" browsergame 2>/dev/null | tail -n 1)
    log_info "Spieler in Datenbank / Players in database: $player_count"
    
    if [ "$player_count" -eq 0 ]; then
        log_warning "Keine Spieler gefunden, könnte normal sein bei frischer Installation / No players found, might be normal for fresh installation"
    else
        log_info "Gefundene Spieler / Found players:"
        $DOCKER_COMPOSE_CMD exec -T db mysql -u browsergame -psicheresPasswort -e "SELECT playerId, name, punkte, gold FROM Spieler;" browsergame 2>/dev/null | tail -n +2
    fi
    
    log_success "Datenbank-Überprüfung abgeschlossen / Database verification completed"
}

# Verify event scheduler
verify_event_scheduler() {
    log_step "7. Überprüfe Event Scheduler / Verifying Event Scheduler"
    
    cd "$PROJECT_ROOT"
    
    # Check if event scheduler is enabled
    local scheduler_status=$($DOCKER_COMPOSE_CMD exec -T db mysql -u browsergame -psicheresPasswort browsergame -se "SHOW VARIABLES LIKE 'event_scheduler';" 2>/dev/null | awk '{print $2}')
    
    if [ "$scheduler_status" = "ON" ]; then
        log_success "✓ Event Scheduler ist aktiviert / Event Scheduler is enabled"
    else
        log_warning "Event Scheduler ist deaktiviert, versuche zu aktivieren / Event Scheduler is disabled, trying to enable..."
        $DOCKER_COMPOSE_CMD exec -T db mysql -u root -proot123 -e "SET GLOBAL event_scheduler = ON;" 2>/dev/null || true
        
        scheduler_status=$($DOCKER_COMPOSE_CMD exec -T db mysql -u browsergame -psicheresPasswort browsergame -se "SHOW VARIABLES LIKE 'event_scheduler';" 2>/dev/null | awk '{print $2}')
        
        if [ "$scheduler_status" = "ON" ]; then
            log_success "✓ Event Scheduler erfolgreich aktiviert / Event Scheduler successfully enabled"
        else
            log_error "✗ Event Scheduler konnte nicht aktiviert werden / Could not enable Event Scheduler"
        fi
    fi
    
    # Check for events
    log_info "Überprüfe Events / Checking events..."
    local event_count=$($DOCKER_COMPOSE_CMD exec -T db mysql -u browsergame -psicheresPasswort browsergame -se "SELECT COUNT(*) FROM information_schema.EVENTS WHERE EVENT_SCHEMA = 'browsergame';" 2>/dev/null)
    
    if [ "$event_count" -gt 0 ]; then
        log_success "✓ $event_count Events gefunden / $event_count events found"
        log_info "Aktive Events / Active events:"
        $DOCKER_COMPOSE_CMD exec -T db mysql -u browsergame -psicheresPasswort browsergame -se "SELECT EVENT_NAME, STATUS, INTERVAL_VALUE, INTERVAL_FIELD FROM information_schema.EVENTS WHERE EVENT_SCHEMA = 'browsergame';" 2>/dev/null | while read line; do
            echo "    $line"
        done
    else
        log_warning "Keine Events gefunden / No events found"
    fi
    
    log_success "Event Scheduler Überprüfung abgeschlossen / Event Scheduler verification completed"
}

# Final verification
final_verification() {
    log_step "8. Finale Überprüfung / Final verification"
    
    cd "$PROJECT_ROOT"
    
    # Check if all containers are running
    log_info "Überprüfe Container-Status / Checking container status..."
    if $DOCKER_COMPOSE_CMD ps | grep -q "Up"; then
        log_success "✓ Alle Container laufen / All containers are running"
        
        # Show detailed status
        log_info "Container-Details / Container details:"
        $DOCKER_COMPOSE_CMD ps
    else
        log_error "✗ Nicht alle Container laufen / Not all containers are running"
        $DOCKER_COMPOSE_CMD ps
        return 1
    fi
    
    # Test web service
    log_info "Teste Web-Service / Testing web service..."
    local web_check_count=0
    while [ $web_check_count -lt 10 ]; do
        if curl -f -s http://localhost:8080/ >/dev/null 2>&1; then
            log_success "✓ Web-Service ist erreichbar / Web service is reachable"
            break
        fi
        sleep 2
        web_check_count=$((web_check_count + 1))
    done
    
    if [ $web_check_count -eq 10 ]; then
        log_warning "⚠ Web-Service nicht erreichbar, möglicherweise noch startend / Web service not reachable, possibly still starting"
    fi
    
    log_success "Finale Überprüfung abgeschlossen / Final verification completed"
}

# Show completion information
show_completion_info() {
    echo
    echo "=================================================================="
    log_success "🎉 FRESH START ERFOLGREICH ABGESCHLOSSEN! / FRESH START COMPLETED SUCCESSFULLY! 🎉"
    echo "=================================================================="
    echo
    
    log_info "🌐 Zugriff über / Access via:"
    echo "    Spiel / Game:              http://localhost:8080/"
    echo "    Admin-Panel:               http://localhost:8080/admin.php"
    echo "    Installation Check:        http://localhost:8080/installation-check.php"
    echo
    
    log_info "🔐 Standard-Zugangsdaten / Default credentials:"
    echo "    Benutzername / Username:   admin"
    echo "    Passwort / Password:       admin123"
    echo
    
    log_info "🎮 Test-Spieler / Test player:"
    echo "    Name:                      TestPlayer"
    echo "    Settlement ID:             1"
    echo "    Direktlink / Direct link:  http://localhost:8080/index.php?settlementId=1"
    echo
    
    log_info "🐳 Docker-Befehle / Docker commands:"
    echo "    Status anzeigen / Show status:     $DOCKER_COMPOSE_CMD ps"
    echo "    Logs anzeigen / Show logs:         $DOCKER_COMPOSE_CMD logs -f"
    echo "    Stoppen / Stop:                    $DOCKER_COMPOSE_CMD down"
    echo "    Neustarten / Restart:              $DOCKER_COMPOSE_CMD restart"
    echo
    
    log_info "🔄 Event-System:"
    echo "    Automatische Ressourcenproduktion läuft!"
    echo "    Automatic resource production is running!"
    echo "    Event-Status prüfen / Check events: $DOCKER_COMPOSE_CMD exec db mysql -u browsergame -psicheresPasswort browsergame -e \"SHOW EVENTS;\""
    echo
    
    log_warning "⚠️  Wichtiger Sicherheitshinweis / Important Security Notice:"
    echo "    Diese Konfiguration ist NUR für Entwicklungsumgebungen geeignet!"
    echo "    This configuration is ONLY suitable for development environments!"
    echo "    Für Produktion Passwörter ändern und Sicherheitsmaßnahmen implementieren."
    echo "    For production, change passwords and implement security measures."
    echo
    
    log_success "Viel Spaß beim Spielen! / Have fun playing! 🎮"
}

# Handle cleanup on exit
cleanup_on_exit() {
    if [ $? -ne 0 ]; then
        log_error "Skript mit Fehler beendet / Script exited with error"
        log_info "Für Debug-Informationen verwende --verbose / For debug information use --verbose"
        log_info "Container-Logs anzeigen / Show container logs: $DOCKER_COMPOSE_CMD logs"
    fi
}

# Parse command line arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --force|-f)
                FORCE_MODE=true
                shift
                ;;
            --remove-images)
                REMOVE_IMAGES=true
                shift
                ;;
            --verbose|-v)
                VERBOSE=true
                shift
                ;;
            --help|-h)
                show_help
                exit 0
                ;;
            *)
                log_error "Unbekannte Option: $1 / Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
}

# Show help
show_help() {
    echo "Browsergame Fresh Start Script"
    echo
    echo "Verwendung / Usage:"
    echo "  $0 [OPTIONEN]"
    echo
    echo "Optionen / Options:"
    echo "  --force, -f         Keine Bestätigung erforderlich / No confirmation required"
    echo "  --remove-images     Docker Images ebenfalls entfernen / Also remove Docker images"
    echo "  --verbose, -v       Ausführliche Ausgabe / Verbose output"
    echo "  --help, -h          Diese Hilfe anzeigen / Show this help"
    echo
    echo "Beispiele / Examples:"
    echo "  $0                  Interaktiver Fresh Start / Interactive fresh start"
    echo "  $0 --force          Automatischer Fresh Start / Automatic fresh start"
    echo "  $0 --force --remove-images    Vollständiger Reset inkl. Images / Complete reset including images"
    echo
    echo "WARNUNG / WARNING:"
    echo "Dieses Skript löscht ALLE Daten und erstellt eine komplett frische Umgebung!"
    echo "This script deletes ALL data and creates a completely fresh environment!"
}

# Main function
main() {
    # Set up exit handler
    trap cleanup_on_exit EXIT
    
    # Parse arguments
    parse_arguments "$@"
    
    # Execute steps
    confirm_action
    check_prerequisites
    cleanup_docker
    cleanup_files
    build_fresh
    wait_for_database
    verify_database
    verify_event_scheduler
    final_verification
    show_completion_info
    
    log_success "Fresh Start Script erfolgreich abgeschlossen! / Fresh Start Script completed successfully!"
}

# Run main function with all arguments
main "$@"