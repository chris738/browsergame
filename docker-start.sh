#!/bin/bash

# Browsergame Docker Quick Start Script

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
    
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose is not installed. Please install Docker Compose first."
        echo "Visit: https://docs.docker.com/compose/install/"
        exit 1
    fi
    
    log_success "Docker and Docker Compose are installed"
}

# Start the application
start_app() {
    log_info "Starting Browsergame with Docker Compose..."
    
    # Build and start containers
    docker-compose up -d --build
    
    log_info "Waiting for database to be ready..."
    sleep 10
    
    # Check if containers are running
    if docker-compose ps | grep -q "Up"; then
        log_success "Containers are running!"
    else
        log_error "Failed to start containers"
        docker-compose logs
        exit 1
    fi
}

# Display access information
show_access_info() {
    log_success "🎮 Browsergame is now running!"
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
    log_info "Container management:"
    echo "  Stop: docker-compose down"
    echo "  View logs: docker-compose logs -f"
    echo "  Restart: docker-compose restart"
    echo
    log_warning "⚠️ This setup is for development only!"
}

# Main function
main() {
    echo "========================================"
    echo "   Browsergame Docker Quick Start      "
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
        docker-compose down
        log_success "Containers stopped"
        ;;
    "restart")
        log_info "Restarting Browsergame..."
        docker-compose restart
        log_success "Containers restarted"
        ;;
    "logs")
        docker-compose logs -f
        ;;
    "status")
        docker-compose ps
        ;;
    "reset")
        log_warning "⚠️ This will completely reset the game including all data!"
        read -p "Are you sure? (yes/no): " -r
        if [[ $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
            if [ -f "./reset.sh" ]; then
                ./reset.sh --force
            else
                log_error "reset.sh not found!"
                exit 1
            fi
        else
            log_info "Reset cancelled"
        fi
        ;;
    "reset-db")
        log_warning "⚠️ This will reset the database only!"
        read -p "Are you sure? (yes/no): " -r
        if [[ $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
            if [ -f "./reset-database.sh" ]; then
                ./reset-database.sh --force
            else
                log_error "reset-database.sh not found!"
                exit 1
            fi
        else
            log_info "Database reset cancelled"
        fi
        ;;
    "help"|"--help")
        echo "Browsergame Docker Management Script"
        echo
        echo "Usage:"
        echo "  ./docker-start.sh           - Start the game"
        echo "  ./docker-start.sh stop      - Stop containers"
        echo "  ./docker-start.sh restart   - Restart containers"
        echo "  ./docker-start.sh logs      - Show container logs"
        echo "  ./docker-start.sh status    - Show container status"
        echo "  ./docker-start.sh reset     - Complete reset (ALL DATA LOST)"
        echo "  ./docker-start.sh reset-db  - Database reset only"
        echo "  ./docker-start.sh help      - Show this help"
        ;;
    *)
        main
        ;;
esac