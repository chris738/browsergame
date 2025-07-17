#!/bin/bash

# Script to initialize browsergame database with event scheduler enabled
# This script ensures the database is properly configured for automatic resource generation

echo "Browsergame Database Initialization Script"
echo "=========================================="

# Check if MariaDB/MySQL is running
if ! systemctl is-active --quiet mariadb && ! systemctl is-active --quiet mysql; then
    echo "Error: MariaDB/MySQL is not running. Please start the database service first."
    echo "Run: sudo systemctl start mariadb"
    exit 1
fi

# Try to enable event scheduler
echo "Enabling MySQL Event Scheduler..."
if sudo mysql -e "SET GLOBAL event_scheduler = ON;" 2>/dev/null; then
    echo "✓ Event scheduler enabled successfully as root with sudo"
elif mysql -u root -e "SET GLOBAL event_scheduler = ON;" 2>/dev/null; then
    echo "✓ Event scheduler enabled successfully as root"
else
    echo "✗ Failed to enable event scheduler. Trying alternative methods..."
    
    # Try with common root passwords
    for password in "" "root" "password" "admin" "123456"; do
        if [ -z "$password" ]; then
            mysql_cmd="mysql -u root"
        else
            mysql_cmd="mysql -u root -p$password"
        fi
        
        if echo "SET GLOBAL event_scheduler = ON;" | $mysql_cmd 2>/dev/null; then
            echo "✓ Event scheduler enabled with password: $password"
            break
        fi
    done
fi

# Verify event scheduler is enabled
echo "Verifying event scheduler status..."
scheduler_status=$(mysql -u browsergame -psicheresPasswort browsergame -se "SHOW VARIABLES LIKE 'event_scheduler';" 2>/dev/null | awk '{print $2}')

if [ "$scheduler_status" = "ON" ]; then
    echo "✓ Event scheduler is enabled"
else
    echo "✗ Event scheduler is not enabled. Resource auto-increment may not work."
    echo "Please manually run: mysql -u root -e \"SET GLOBAL event_scheduler = ON;\""
fi

# Check if UpdateResources event exists
echo "Checking for UpdateResources event..."
event_exists=$(mysql -u browsergame -psicheresPasswort browsergame -se "SELECT COUNT(*) FROM information_schema.EVENTS WHERE EVENT_NAME = 'UpdateResources';" 2>/dev/null)

if [ "$event_exists" = "1" ]; then
    echo "✓ UpdateResources event exists and is configured"
else
    echo "✗ UpdateResources event not found. Auto resource generation will not work."
    echo "Please run the full database initialization script."
fi

# Test basic database connectivity
echo "Testing database connectivity..."
if mysql -u browsergame -psicheresPasswort browsergame -e "SELECT 1;" >/dev/null 2>&1; then
    echo "✓ Database connection successful"
else
    echo "✗ Database connection failed"
    echo "Please check database credentials and ensure browsergame database exists"
fi

echo
echo "Initialization complete!"
echo "If all checks passed, the browsergame should now work with automatic resource generation."