#!/bin/bash

# Browsergame Installation Script
# Supports Ubuntu/Debian and CentOS/RHEL systems

set -e  # Exit on any error

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

# Check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        log_warning "Running as root. This is only recommended for development environments!"
        read -p "Continue? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

# Detect OS
detect_os() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$ID
        VER=$VERSION_ID
    else
        log_error "Cannot detect OS. This script supports Ubuntu/Debian and CentOS/RHEL."
        exit 1
    fi
    log_info "Detected OS: $OS $VER"
}

# Install packages based on OS
install_packages() {
    log_info "Installing required packages..."
    
    case $OS in
        ubuntu|debian)
            sudo apt update
            sudo apt install -y apache2 php php-mysql mariadb-server git curl
            sudo systemctl enable apache2 mariadb
            sudo systemctl start apache2 mariadb
            ;;
        centos|rhel|rocky|almalinux)
            sudo yum update -y
            sudo yum install -y httpd php php-mysqlnd mariadb-server git curl
            sudo systemctl enable httpd mariadb
            sudo systemctl start httpd mariadb
            ;;
        *)
            log_error "Unsupported OS: $OS"
            exit 1
            ;;
    esac
    
    log_success "Packages installed successfully"
}

# Configure Apache
configure_apache() {
    log_info "Configuring Apache..."
    
    case $OS in
        ubuntu|debian)
            sudo a2enmod rewrite
            sudo systemctl restart apache2
            ;;
        centos|rhel|rocky|almalinux)
            sudo systemctl restart httpd
            ;;
    esac
    
    log_success "Apache configured"
}

# Secure MariaDB installation
secure_mariadb() {
    log_info "Securing MariaDB installation..."
    log_warning "You will be prompted to set a root password and answer security questions."
    log_info "Recommended answers: Y, Y, Y, Y (remove anonymous users, disable remote root, remove test DB, reload privileges)"
    
    sudo mysql_secure_installation
    
    log_success "MariaDB secured"
}

# Setup database
setup_database() {
    log_info "Setting up database..."
    
    # Prompt for database root password
    read -s -p "Enter MariaDB root password: " DB_ROOT_PASSWORD
    echo
    
    # Create database and user
    mysql -u root -p$DB_ROOT_PASSWORD << EOF
DROP DATABASE IF EXISTS browsergame;
CREATE DATABASE browsergame;
CREATE USER IF NOT EXISTS 'browsergame'@'localhost' IDENTIFIED BY 'sicheresPasswort';
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    # Import database schema
    if [ -f "database.sql" ]; then
        mysql -u root -p$DB_ROOT_PASSWORD browsergame < database.sql
        log_success "Database schema imported"
    else
        log_error "database.sql not found! Make sure you're running this script from the game directory."
        exit 1
    fi
    
    # Create initial player
    mysql -u root -p$DB_ROOT_PASSWORD browsergame << EOF
CALL CreatePlayerWithSettlement('Admin');
EOF
    
    log_success "Database setup completed"
}

# Install game files
install_game() {
    log_info "Installing game files..."
    
    # Determine web root
    case $OS in
        ubuntu|debian)
            WEB_ROOT="/var/www/html"
            WEB_USER="www-data"
            ;;
        centos|rhel|rocky|almalinux)
            WEB_ROOT="/var/www/html"
            WEB_USER="apache"
            ;;
    esac
    
    GAME_DIR="$WEB_ROOT/browsergame"
    
    # Create game directory
    sudo mkdir -p $GAME_DIR
    
    # Copy files
    sudo cp -r . $GAME_DIR/
    
    # Set permissions
    sudo chown -R $WEB_USER:$WEB_USER $GAME_DIR
    sudo chmod -R 755 $GAME_DIR
    
    # Create .env file from template
    if [ -f ".env.example" ] && [ ! -f "$GAME_DIR/.env" ]; then
        sudo cp .env.example $GAME_DIR/.env
        sudo chown $WEB_USER:$WEB_USER $GAME_DIR/.env
    fi
    
    log_success "Game files installed to $GAME_DIR"
}

# Display final information
show_completion_info() {
    log_success "Installation completed successfully!"
    echo
    log_info "Access your game at:"
    echo "  Game: http://localhost/browsergame/"
    echo "  Installation Check: http://localhost/browsergame/installation-check.php"
    echo "  Admin Panel: http://localhost/browsergame/admin.php"
    echo
    log_info "Default admin credentials:"
    echo "  Username: admin"
    echo "  Password: admin123"
    echo
    log_info "Database details:"
    echo "  Database: browsergame"
    echo "  User: browsergame"
    echo "  Password: sicheresPasswort"
    echo
    log_warning "⚠️ Security Notice:"
    echo "  - Change default passwords before production use"
    echo "  - Configure firewall rules"
    echo "  - Use HTTPS in production"
    echo "  - This setup is intended for development environments"
}

# Main installation function
main() {
    echo "========================================"
    echo "    Browsergame Installation Script    "
    echo "========================================"
    echo
    
    check_root
    detect_os
    
    log_info "Starting installation process..."
    
    install_packages
    configure_apache
    secure_mariadb
    setup_database
    install_game
    show_completion_info
    
    echo
    log_success "🎮 Browsergame installation completed! Happy gaming!"
}

# Run main function
main "$@"