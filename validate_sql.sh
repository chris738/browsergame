#!/bin/bash
# SQL Syntax Validator for modular database structure
# This script validates SQL syntax without requiring database access

set -e

SQL_DIR="$(dirname "$0")/sql"

echo "Validating modular database structure..."
echo "======================================="

# Function to check basic SQL syntax
validate_sql_file() {
    local file="$1"
    local filename=$(basename "$file")
    
    if [ ! -f "$file" ]; then
        echo "❌ Missing file: $file"
        return 1
    fi
    
    # Basic syntax checks
    local errors=0
    
    # Check for unterminated strings
    if grep -n "'" "$file" | grep -v "'[^']*'" | grep -v "--" >/dev/null 2>&1; then
        echo "❌ $filename: Possible unterminated string"
        errors=$((errors + 1))
    fi
    
    # Check for missing semicolons on CREATE/INSERT/UPDATE/DELETE statements
    if grep -E "^(CREATE|INSERT|UPDATE|DELETE|DROP)" "$file" | grep -v ";" >/dev/null 2>&1; then
        echo "⚠️  $filename: Some statements may be missing semicolons"
    fi
    
    # Check for basic DELIMITER usage
    local delimiter_count=$(grep -c "DELIMITER" "$file" 2>/dev/null || echo 0)
    if [ $((delimiter_count % 2)) -ne 0 ]; then
        echo "❌ $filename: Unmatched DELIMITER statements"
        errors=$((errors + 1))
    fi
    
    # Check for SQL keywords and structure
    if grep -E "(CREATE|SELECT|INSERT|UPDATE|DELETE|DROP)" "$file" >/dev/null 2>&1; then
        if [ $errors -eq 0 ]; then
            echo "✅ $filename: Basic syntax appears valid"
        fi
    else
        echo "⚠️  $filename: No SQL statements found"
    fi
    
    return $errors
}

# Function to check file dependencies
check_dependencies() {
    local file="$1"
    local filename=$(basename "$file")
    
    # Check for table references
    if grep -E "REFERENCES|FOREIGN KEY" "$file" >/dev/null 2>&1; then
        echo "ℹ️  $filename: Contains foreign key references"
    fi
    
    # Check for SOURCE statements
    if grep "SOURCE" "$file" >/dev/null 2>&1; then
        echo "ℹ️  $filename: Contains SOURCE statements for modular loading"
    fi
}

# Validate modular files in order
echo "1. Validating database setup files..."
files_setup=(
    "$SQL_DIR/data/database_setup.sql"
)

echo "2. Validating table definition files..."
files_tables=(
    "$SQL_DIR/tables/core_tables.sql" 
    "$SQL_DIR/tables/military_tables.sql"
    "$SQL_DIR/tables/research_tables.sql"
    "$SQL_DIR/tables/travel_tables.sql"
    "$SQL_DIR/tables/battle_tables.sql"
    "$SQL_DIR/tables/kaserne_tables.sql"
)

echo "3. Validating procedure files..."
files_procedures=(
    "$SQL_DIR/procedures/player_procedures.sql"
    "$SQL_DIR/procedures/building_procedures.sql"
    "$SQL_DIR/procedures/military_procedures.sql"
    "$SQL_DIR/procedures/travel_procedures.sql"
    "$SQL_DIR/procedures/initialization_procedures.sql"
)

echo "4. Validating view files..."
files_views=(
    "$SQL_DIR/views/game_views.sql"
    "$SQL_DIR/views/enhanced_views.sql"
)

echo "5. Validating data files..."
files_data=(
    "$SQL_DIR/data/initial_data.sql"
    "$SQL_DIR/data/military_data.sql"
    "$SQL_DIR/data/research_data.sql"
    "$SQL_DIR/data/travel_data.sql"
    "$SQL_DIR/data/kaserne_data.sql"
)

echo "6. Validating event files..."
files_events=(
    "$SQL_DIR/events/game_events.sql"
    "$SQL_DIR/events/travel_events.sql"
    "$SQL_DIR/events/enable_events.sql"
)

# Combine all file arrays
all_files=("${files_setup[@]}" "${files_tables[@]}" "${files_procedures[@]}" "${files_views[@]}" "${files_data[@]}" "${files_events[@]}")

total_errors=0
total_files=0

echo ""
echo "Validation Results:"
echo "=================="

for file in "${all_files[@]}"; do
    if validate_sql_file "$file"; then
        check_dependencies "$file"
    else
        total_errors=$((total_errors + 1))
    fi
    total_files=$((total_files + 1))
done

echo ""
echo "7. Validating orchestrator file..."
validate_sql_file "$SQL_DIR/database_modular.sql"
check_dependencies "$SQL_DIR/database_modular.sql"

echo ""
echo "8. Checking modular structure integrity..."

# Verify all referenced files exist
missing_refs=0
while IFS= read -r line; do
    if [[ $line =~ SOURCE[[:space:]]+([^;]+) ]]; then
        ref_file="${BASH_REMATCH[1]}"
        full_path="$SQL_DIR/../$ref_file"
        if [ ! -f "$full_path" ]; then
            echo "❌ Missing referenced file: $ref_file"
            missing_refs=$((missing_refs + 1))
        fi
    fi
done < "$SQL_DIR/database_modular.sql"

if [ $missing_refs -eq 0 ]; then
    echo "✅ All referenced files exist"
fi

echo ""
echo "Summary:"
echo "========"
echo "Total files validated: $total_files"
echo "Files with errors: $total_errors"
echo "Missing references: $missing_refs"

if [ $total_errors -eq 0 ] && [ $missing_refs -eq 0 ]; then
    echo ""
    echo "🎉 Modular database structure validation successful!"
    echo ""
    echo "Structure Summary:"
    echo "- Database setup: Separate user/database creation"
    echo "- Tables: Organized by system (core, military, research, travel, battle)"
    echo "- Procedures: Organized by functionality" 
    echo "- Views: Separated into basic and enhanced views"
    echo "- Data: Initial configuration data separated by system"
    echo "- Events: Automated processing events separated by type"
    echo ""
    echo "Benefits of modular structure:"
    echo "✅ Easier maintenance and updates"
    echo "✅ Better organization by functional area"
    echo "✅ Ability to load only needed components"
    echo "✅ Clearer dependencies between components"
    echo "✅ Supports both modular development and deployment"
    
    exit 0
else
    echo ""
    echo "❌ Validation found issues that need to be resolved"
    exit 1
fi