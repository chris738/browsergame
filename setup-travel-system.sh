#!/bin/bash

# Setup script for travel time system
# This script creates the necessary database tables and initial data

echo "Setting up Travel Time System..."

# Database connection details
DB_HOST="localhost"
DB_USER="browsergame"
DB_PASS="sicheresPasswort"
DB_NAME="browsergame"

# Function to run SQL file
run_sql_file() {
    local file=$1
    echo "Running $file..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$file"
    if [ $? -eq 0 ]; then
        echo "✓ $file executed successfully"
    else
        echo "✗ Error executing $file"
        exit 1
    fi
}

# Create travel tables
if [ -f "sql/tables/travel_tables.sql" ]; then
    run_sql_file "sql/tables/travel_tables.sql"
else
    echo "✗ travel_tables.sql not found"
    exit 1
fi

# Insert initial military and travel data
if [ -f "sql/data/military_travel_data.sql" ]; then
    run_sql_file "sql/data/military_travel_data.sql"
else
    echo "✗ military_travel_data.sql not found"
    exit 1
fi

echo ""
echo "✓ Travel Time System setup completed successfully!"
echo ""
echo "Next steps:"
echo "1. Access the admin panel at: http://localhost:8080/admin-travel.php"
echo "2. Configure travel speeds and unit settings"
echo "3. The system uses MySQL events for automated processing (no cron jobs needed)"
echo ""
echo "Features enabled:"
echo "- Travel times for attacks (2-10 seconds per block based on unit speed)"
echo "- Travel times for trades (configurable, default 5 seconds per block)"
echo "- Configurable military unit speeds and loot amounts"
echo "- Admin panel for managing all settings"
echo "- Automated event-based processing (ProcessTravelArrivals event every 5 seconds)"
echo "- Real-time travel progress tracking"