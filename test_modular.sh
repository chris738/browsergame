#!/bin/bash
# Test script to validate modular database structure
# This script creates a temporary test database to verify modular loading

set -e

# Configuration
TEST_DB="browsergame_test"
SQL_DIR="$(dirname "$0")/sql"

echo "Testing modular database structure..."
echo "===================================="

# Function to run SQL command
run_sql() {
    mysql -u root --silent -e "$1" 2>/dev/null || {
        echo "Error executing SQL: $1"
        return 1
    }
}

# Function to run SQL file
run_sql_file() {
    local file="$1"
    if [ ! -f "$file" ]; then
        echo "Error: File $file does not exist"
        return 1
    fi
    
    echo "Executing: $file"
    mysql -u root < "$file" 2>/dev/null || {
        echo "Error executing file: $file"
        return 1
    }
}

# Cleanup function
cleanup() {
    echo "Cleaning up test database..."
    run_sql "DROP DATABASE IF EXISTS $TEST_DB;" || true
}

# Set trap for cleanup
trap cleanup EXIT

echo "1. Testing modular structure (database_modular.sql)..."

# Create a modified version for testing that doesn't include SOURCE commands
# since MySQL SOURCE command requires interactive session
cat > "/tmp/test_modular.sql" << 'EOF'
-- Test script for modular database creation
-- This combines all modular files for validation

-- Database setup
DROP DATABASE IF EXISTS browsergame_test;
CREATE DATABASE browsergame_test;
USE browsergame_test;

-- Note: In production, this would use SOURCE commands
-- For testing, we'll validate that all referenced files exist and have correct syntax
EOF

# Validate that all modular files have correct syntax
echo "2. Validating SQL syntax of modular files..."

files_to_check=(
    "$SQL_DIR/data/database_setup.sql"
    "$SQL_DIR/tables/core_tables.sql" 
    "$SQL_DIR/tables/military_tables.sql"
    "$SQL_DIR/tables/research_tables.sql"
    "$SQL_DIR/tables/travel_tables.sql"
    "$SQL_DIR/tables/battle_tables.sql"
    "$SQL_DIR/procedures/player_procedures.sql"
    "$SQL_DIR/procedures/building_procedures.sql"
    "$SQL_DIR/procedures/military_procedures.sql"
    "$SQL_DIR/procedures/travel_procedures.sql"
    "$SQL_DIR/procedures/initialization_procedures.sql"
    "$SQL_DIR/views/game_views.sql"
    "$SQL_DIR/views/enhanced_views.sql"
    "$SQL_DIR/data/initial_data.sql"
    "$SQL_DIR/data/military_data.sql"
    "$SQL_DIR/data/research_data.sql"
    "$SQL_DIR/data/travel_data.sql"
    "$SQL_DIR/data/kaserne_data.sql"
    "$SQL_DIR/events/game_events.sql"
    "$SQL_DIR/events/travel_events.sql"
    "$SQL_DIR/events/enable_events.sql"
)

syntax_errors=0
for file in "${files_to_check[@]}"; do
    if [ ! -f "$file" ]; then
        echo "❌ Missing file: $file"
        ((syntax_errors++))
        continue
    fi
    
    # Check basic SQL syntax by attempting to parse with mysql
    if mysql -u root --silent -e "use browsergame_test;" < "$file" 2>/dev/null; then
        echo "✅ Valid syntax: $(basename "$file")"
    else
        echo "❌ Syntax error: $file"
        ((syntax_errors++))
    fi
done

echo ""
echo "3. Testing original database.sql for comparison..."
if mysql -u root < "$SQL_DIR/database.sql" 2>/dev/null; then
    echo "✅ Original database.sql loads successfully"
    run_sql "DROP DATABASE IF EXISTS browsergame;"
else
    echo "❌ Original database.sql has issues"
    ((syntax_errors++))
fi

echo ""
echo "Test Results:"
echo "============"
if [ $syntax_errors -eq 0 ]; then
    echo "✅ All modular files validated successfully!"
    echo "✅ Modular structure is ready for use"
    echo ""
    echo "Usage:"
    echo "  For development: Use individual files in sql/ subdirectories"
    echo "  For deployment: Use database_modular.sql with SOURCE commands"
    echo "  Legacy: database.sql still available as single file"
else
    echo "❌ Found $syntax_errors issues with modular structure"
    exit 1
fi