#!/bin/bash

# Browsergame Docker All-in-One Start Script
# Starts the browsergame with Docker and ensures event scheduler is properly initialized
# This script provides a complete all-in-one solution including event scheduler verification

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

# Check if Docker and Docker Compose are installed
check_docker() {
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed. Please install Docker first."
        echo "Visit: https://docs.docker.com/get-docker/"
        exit 1
    fi
    
    # Check for modern docker compose or legacy docker-compose
    if docker compose version &> /dev/null; then
        log_success "Docker and Docker Compose are installed (modern syntax)"
    elif command -v docker-compose &> /dev/null; then
        log_warning "Using legacy docker-compose. Consider upgrading to modern 'docker compose'"
        # Replace docker compose with docker-compose in the script for compatibility
        sed -i 's/docker compose/docker-compose/g' "$0"
    else
        log_error "Docker Compose is not installed. Please install Docker Compose first."
        echo "Visit: https://docs.docker.com/compose/install/"
        exit 1
    fi
    
    log_success "Docker environment check complete"
}

# Wait for database to be ready
wait_for_database() {
    log_info "Waiting for database to be ready..."
    local max_attempts=30
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if docker compose exec -T db mysqladmin ping -h localhost -u browsergame -psicheresPasswort >/dev/null 2>&1; then
            log_success "Database is ready!"
            return 0
        fi
        
        if [ $attempt -eq $max_attempts ]; then
            log_error "Database failed to start within timeout"
            return 1
        fi
        
        log_info "Database not ready yet, waiting... (attempt $attempt/$max_attempts)"
        sleep 2
        attempt=$((attempt + 1))
    done
}

# Verify event scheduler is running
verify_event_scheduler() {
    log_info "Verifying event scheduler is enabled and running..."
    
    # Check if event scheduler is ON
    local scheduler_status=$(docker compose exec -T db mysql -u browsergame -psicheresPasswort browsergame -se "SHOW VARIABLES LIKE 'event_scheduler';" 2>/dev/null | awk '{print $2}')
    
    if [ "$scheduler_status" = "ON" ]; then
        log_success "✓ Event scheduler is enabled"
    else
        log_warning "Event scheduler is OFF, attempting to enable it..."
        # Try to enable the event scheduler
        docker compose exec -T db mysql -u root -proot123 -e "SET GLOBAL event_scheduler = ON;" 2>/dev/null
        
        # Check again
        scheduler_status=$(docker compose exec -T db mysql -u browsergame -psicheresPasswort browsergame -se "SHOW VARIABLES LIKE 'event_scheduler';" 2>/dev/null | awk '{print $2}')
        
        if [ "$scheduler_status" = "ON" ]; then
            log_success "✓ Event scheduler enabled successfully"
        else
            log_error "✗ Failed to enable event scheduler (status: $scheduler_status)"
            log_warning "Events may still work if they were created during initialization"
        fi
    fi
    
    # Check if UpdateResources event exists and is enabled
    local event_count=$(docker compose exec -T db mysql -u browsergame -psicheresPasswort browsergame -se "SELECT COUNT(*) FROM information_schema.EVENTS WHERE EVENT_NAME = 'UpdateResources' AND STATUS = 'ENABLED';" 2>/dev/null)
    
    if [ "$event_count" = "1" ]; then
        log_success "✓ UpdateResources event is active"
    else
        log_error "✗ UpdateResources event not found or not enabled"
        return 1
    fi
    
    # Show all active events for verification
    log_info "Active events in the system:"
    docker compose exec -T db mysql -u browsergame -psicheresPasswort browsergame -se "SELECT EVENT_NAME, STATUS, INTERVAL_VALUE, INTERVAL_FIELD FROM information_schema.EVENTS WHERE EVENT_SCHEMA = 'browsergame';" 2>/dev/null | while read line; do
        echo "  $line"
    done
    
    log_success "✓ Event scheduler verification complete - automatic resource generation is active!"
}

# Start the application
start_app() {
    log_info "Starting Browsergame with Docker Compose..."
    
    # Build and start containers
    docker compose up -d --build
    
    # Wait for database to be completely ready
    if ! wait_for_database; then
        log_error "Failed to start database"
        docker compose logs db
        exit 1
    fi
    
    # Verify event scheduler is working
    if ! verify_event_scheduler; then
        log_error "Event scheduler verification failed"
        log_warning "The game may work but automatic resource generation might not function properly"
        log_info "You can manually check with: docker compose exec db mysql -u browsergame -psicheresPasswort browsergame -e \"SHOW EVENTS;\""
    fi
    
    # Check if containers are running
    if docker compose ps | grep -q "Up"; then
        log_success "All containers are running!"
    else
        log_error "Failed to start containers"
        docker compose logs
        exit 1
    fi
}

# Display access information
show_access_info() {
    log_success "🎮 Browsergame is now running with automatic resource generation!"
    echo
    log_info "Access URLs:"
    echo "  Game: http://localhost:8080/"
    echo "  Installation Check: http://localhost:8080/installation-check.php"
    echo "  Admin Panel: http://localhost:8080/admin.php"
    echo
    log_info "Default credentials:"
    echo "  Username: admin"
    echo "  Password: admin123"
    echo
    log_info "Initial player created: 'TestPlayer' (Settlement ID: 1)"
    echo "  Direct game link: http://localhost:8080/index.php?settlementId=1"
    echo
    log_success "🕘 Event Scheduler Active: Resources are automatically updated every second"
    echo "  Wood, Stone, Ore production is now running in the background"
    echo "  Building construction queue is automatically processed"
    echo
    log_info "Container management:"
    echo "  Stop: docker compose down"
    echo "  View logs: docker compose logs -f"
    echo "  Restart: docker compose restart"
    echo "  Event status: docker compose exec db mysql -u browsergame -psicheresPasswort browsergame -e \"SHOW EVENTS;\""
    echo
    log_warning "⚠️ This setup is for development only!"
}

# Main function
main() {
    echo "========================================"
    echo "   Browsergame Docker All-in-One Start"
    echo "   with Event Scheduler Integration    "
    echo "========================================"
    echo
    
    check_docker
    start_app
    show_access_info
}

# Handle command line arguments
case "${1:-}" in
    "stop")
        log_info "Stopping Browsergame containers..."
        docker compose down
        log_success "Containers stopped"
        ;;
    "restart")
        log_info "Restarting Browsergame..."
        docker compose restart
        log_success "Containers restarted"
        ;;
    "logs")
        docker compose logs -f
        ;;
    "status")
        docker compose ps
        ;;
    "events")
        log_info "Checking event scheduler status..."
        docker compose exec db mysql -u browsergame -psicheresPasswort browsergame -e "SHOW VARIABLES LIKE 'event_scheduler'; SHOW EVENTS;"
        ;;
    *)
        main
        ;;
esac