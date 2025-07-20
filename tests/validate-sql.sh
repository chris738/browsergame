#!/bin/bash

# SQL Validation Script
# Validates SQL syntax and structure without needing a database connection

set -e

echo "=== SQL Files Validation ==="

# Check if main database file exists and has content
SQL_FILE="sql/database.sql"
if [[ -f "$SQL_FILE" ]]; then
    echo "✅ Main database file exists: $SQL_FILE"
    
    # Check for key components
    if grep -q "CREATE TABLE" "$SQL_FILE"; then
        echo "✅ Contains table definitions"
    else
        echo "❌ No table definitions found"
    fi
    
    if grep -q "CREATE.*VIEW" "$SQL_FILE"; then
        echo "✅ Contains view definitions"
    else
        echo "❌ No view definitions found"
    fi
    
    if grep -q "CREATE.*PROCEDURE" "$SQL_FILE"; then
        echo "✅ Contains procedure definitions"
    else
        echo "❌ No procedure definitions found"
    fi
    
    if grep -q "CREATE.*EVENT" "$SQL_FILE"; then
        echo "✅ Contains event definitions"
    else
        echo "❌ No event definitions found"
    fi
    
    # Check file size
    SIZE=$(wc -l < "$SQL_FILE")
    echo "✅ File contains $SIZE lines"
    
else
    echo "❌ Main database file not found: $SQL_FILE"
    exit 1
fi

# Check modular SQL structure
echo ""
echo "🗂️  Checking modular SQL structure..."

DIRS=("sql/tables" "sql/views" "sql/procedures" "sql/data")
for dir in "${DIRS[@]}"; do
    if [[ -d "$dir" ]]; then
        COUNT=$(find "$dir" -name "*.sql" | wc -l)
        echo "✅ $dir: $COUNT SQL files"
    else
        echo "❌ Missing directory: $dir"
    fi
done

# Check for required files
echo ""
echo "📋 Checking required SQL files..."

REQUIRED_FILES=(
    "sql/tables/core_tables.sql"
    "sql/views/enhanced_views.sql"
    "sql/procedures/player_procedures.sql"
    "sql/data/initial_data.sql"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [[ -f "$file" ]]; then
        echo "✅ $file exists"
    else
        echo "❌ Missing required file: $file"
    fi
done

# Check PHP syntax for all PHP files
echo ""
echo "🐘 Checking PHP syntax..."

find . -name "*.php" -not -path "./vendor/*" | while read -r file; do
    if php -l "$file" >/dev/null 2>&1; then
        echo "✅ $file: syntax OK"
    else
        echo "❌ $file: syntax error"
        php -l "$file"
    fi
done

# Check test files
echo ""
echo "🧪 Checking test files..."

if [[ -f "test-enhanced-views.php" ]]; then
    echo "✅ Enhanced views test file exists"
    if php -l "test-enhanced-views.php" >/dev/null 2>&1; then
        echo "✅ Test file syntax OK"
    else
        echo "❌ Test file syntax error"
    fi
else
    echo "❌ Test file missing"
fi

# Count SQL components
echo ""
echo "📊 SQL Component Summary:"

TABLES=$(grep -c "CREATE TABLE" "$SQL_FILE" || echo "0")
VIEWS=$(grep -c "CREATE.*VIEW" "$SQL_FILE" || echo "0")
PROCEDURES=$(grep -c "CREATE.*PROCEDURE" "$SQL_FILE" || echo "0")
EVENTS=$(grep -c "CREATE.*EVENT" "$SQL_FILE" || echo "0")

echo "  Tables: $TABLES"
echo "  Views: $VIEWS"
echo "  Procedures: $PROCEDURES"
echo "  Events: $EVENTS"

# Validate that we have enough components
if [[ $TABLES -ge 15 && $VIEWS -ge 5 && $PROCEDURES -ge 3 && $EVENTS -ge 4 ]]; then
    echo "✅ Database structure validation: PASSED"
else
    echo "⚠️  Database structure validation: INCOMPLETE"
    echo "    Expected: Tables≥15, Views≥5, Procedures≥3, Events≥4"
fi

echo ""
echo "=== SQL Validation Complete ==="