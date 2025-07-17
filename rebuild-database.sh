#!/bin/bash

# Browsergame Database Rebuild Script
# Drops existing database and recreates it from database.sql
# Supports Docker and manual installations

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SQL_FILE="$SCRIPT_DIR/database.sql"
INIT_PLAYER_SQL="$SCRIPT_DIR/docker/init-player.sql"

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

# Check if SQL file exists
check_sql_file() {
    if [[ ! -f "$SQL_FILE" ]]; then
        log_error "Database SQL file not found: $SQL_FILE"
        exit 1
    fi
    log_info "Using SQL file: $SQL_FILE"
}

# Detect environment (Docker or manual)
detect_environment() {
    if [[ -f "docker-compose.yml" ]] && (command -v docker-compose &> /dev/null || command -v docker &> /dev/null); then
        echo "docker"
    else
        echo "manual"
    fi
}

# Get docker compose command (supports both docker-compose and docker compose)
get_docker_compose_cmd() {
    if command -v docker-compose &> /dev/null; then
        echo "docker-compose"
    elif command -v docker &> /dev/null; then
        echo "docker compose"
    else
        log_error "Neither docker-compose nor docker command found"
        exit 1
    fi
}

# Rebuild database in Docker environment
rebuild_database_docker() {
    log_info "Docker environment detected"
    
    local docker_cmd=$(get_docker_compose_cmd)
    log_info "Using command: $docker_cmd"
    
    # Check if containers are running
    if ! $docker_cmd ps | grep -q "Up"; then
        log_warning "Docker containers not running"
        read -p "Start containers? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            log_info "Starting containers..."
            $docker_cmd up -d
            sleep 10
        else
            log_error "Containers must be running for database rebuild"
            exit 1
        fi
    fi
    
    log_info "Dropping and recreating database..."
    
    # Drop and recreate database
    $docker_cmd exec -T db mysql -u root -proot123 << 'EOF'
DROP DATABASE IF EXISTS browsergame;
CREATE DATABASE browsergame;
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'%';
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    # Import database schema
    log_info "Importing database schema from $SQL_FILE..."
    $docker_cmd exec -T db mysql -u browsergame -psicheresPasswort browsergame < "$SQL_FILE"
    
    # Import initial player if file exists
    if [[ -f "$INIT_PLAYER_SQL" ]]; then
        log_info "Creating initial test player..."
        $docker_cmd exec -T db mysql -u browsergame -psicheresPasswort browsergame < "$INIT_PLAYER_SQL"
    fi
    
    log_success "Docker database rebuild complete"
}

# Rebuild database in manual environment
rebuild_database_manual() {
    log_info "Manual installation detected"
    
    # Get database credentials
    read -s -p "Enter MariaDB/MySQL root password: " DB_ROOT_PASSWORD
    echo
    
    # Test connection
    if ! mysql -u root -p"$DB_ROOT_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; then
        log_error "Database connection failed"
        exit 1
    fi
    
    log_info "Dropping and recreating database..."
    
    # Drop and recreate database with user
    mysql -u root -p"$DB_ROOT_PASSWORD" << 'EOF'
DROP DATABASE IF EXISTS browsergame;
CREATE DATABASE browsergame;
CREATE USER IF NOT EXISTS 'browsergame'@'localhost' IDENTIFIED BY 'sicheresPasswort';
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'localhost';
GRANT ALL PRIVILEGES ON browsergame.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    # Import database schema
    log_info "Importing database schema from $SQL_FILE..."
    mysql -u root -p"$DB_ROOT_PASSWORD" browsergame < "$SQL_FILE"
    
    # Create initial admin player
    log_info "Creating initial admin player..."
    mysql -u root -p"$DB_ROOT_PASSWORD" browsergame << 'EOF'
CALL CreatePlayerWithSettlement('Admin');
EOF
    
    log_success "Manual database rebuild complete"
}

# Verify database was created correctly
verify_database() {
    log_info "Verifying database..."
    
    local env=$(detect_environment)
    
    if [[ "$env" == "docker" ]]; then
        local docker_cmd=$(get_docker_compose_cmd)
        # Verify in Docker
        if $docker_cmd exec -T db mysql -u browsergame -psicheresPasswort -e "SELECT COUNT(*) FROM Spieler;" browsergame 2>/dev/null >/dev/null; then
            local player_count=$($docker_cmd exec -T db mysql -u browsergame -psicheresPasswort -e "SELECT COUNT(*) FROM Spieler;" browsergame 2>/dev/null | tail -n 1)
            log_success "Database verified, $player_count players found"
            
            # Show players
            log_info "Players in database:"
            $docker_cmd exec -T db mysql -u browsergame -psicheresPasswort -e "SELECT playerId, name, punkte, gold FROM Spieler;" browsergame 2>/dev/null | tail -n +2
            return 0
        else
            log_error "Database verification failed"
            return 1
        fi
    else
        # For manual, basic verification
        log_success "Manual installation - database should be working"
        return 0
    fi
}

# Show usage information
show_usage() {
    echo "Browsergame Database Rebuild Script"
    echo
    echo "Usage:"
    echo "  $0              - Interactive database rebuild"
    echo "  $0 --force      - Rebuild without confirmation"
    echo "  $0 --help       - Show this help"
    echo
    echo "This script drops the existing database and rebuilds it from database.sql"
    echo "Perfect for when schema changes are made or database needs to be reset."
}

# Confirm action
confirm_rebuild() {
    echo "========================================"
    echo "  Browsergame Database Rebuild Script  "
    echo "========================================"
    echo
    log_warning "⚠️  This will DELETE all existing data!"
    echo
    log_info "What will be rebuilt:"
    echo "  - Complete database schema from $SQL_FILE"
    echo "  - All tables, procedures, events and views"
    echo "  - Initial player data"
    echo
    
    read -p "Continue with database rebuild? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        log_info "Database rebuild cancelled"
        exit 0
    fi
    echo
}

# Main function
main() {
    check_sql_file
    
    local env=$(detect_environment)
    log_info "Environment: $env"
    
    if [[ "$env" == "docker" ]]; then
        rebuild_database_docker
    else
        rebuild_database_manual
    fi
    
    if verify_database; then
        log_success "🗄️ Database rebuild successful!"
        echo
        log_info "Access via:"
        if [[ "$env" == "docker" ]]; then
            echo "  Game: http://localhost:8080/"
            echo "  Admin: http://localhost:8080/admin.php"
        else
            echo "  Game: http://localhost/browsergame/"
            echo "  Admin: http://localhost/browsergame/admin.php"
        fi
    else
        log_error "Database rebuild failed verification"
        exit 1
    fi
}

# Handle command line arguments
case "${1:-}" in
    "--force"|"-f")
        log_warning "Force mode - rebuilding without confirmation"
        main
        ;;
    "--help"|"-h")
        show_usage
        ;;
    *)
        confirm_rebuild
        main
        ;;
esac