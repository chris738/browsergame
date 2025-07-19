# Database Rebuild Scripts

This directory contains scripts for rebuilding and resetting the browsergame database.

## Available Scripts

### `rebuild-database.sh`
Main database rebuild script that drops the existing database and rebuilds it from `../sql/database.sql`.

**Usage:**
```bash
./rebuild-database.sh              # Interactive rebuild with confirmation
./rebuild-database.sh --force      # Rebuild without confirmation
./rebuild-database.sh --help       # Show help information
```

**Features:**
- Detects Docker vs manual installation automatically
- Drops and recreates the database completely
- Imports the full schema from the consolidated SQL file
- Creates initial test player
- Verifies the database was created correctly

### `quick-rebuild-db.sh`
A convenience wrapper that runs `rebuild-database.sh` in force mode (no confirmation required).

**Usage:**
```bash
./quick-rebuild-db.sh
```

### `reset-database.sh`
Resets only the database without affecting Docker containers or web server configuration.

**Usage:**
```bash
./reset-database.sh              # Interactive reset with confirmation
./reset-database.sh --force      # Reset without confirmation
./reset-database.sh --help       # Show help information
```

### `fresh-start.sh`
Complete environment reset that rebuilds everything from scratch including Docker containers.

**Usage:**
```bash
./fresh-start.sh                 # Interactive fresh start
./fresh-start.sh --force         # Automated fresh start
./fresh-start.sh --remove-images # Also remove Docker images
```

### `test-rebuild-scripts.sh`
Test script that verifies the rebuild scripts are working correctly without actually modifying the database.

**Usage:**
```bash
./test-rebuild-scripts.sh
```

## Database Structure

The scripts work with the restructured database schema that includes:

### Tables
- **Spieler** - Player information
- **Settlement** - Player settlements with resources
- **Map** - Settlement coordinates
- **Buildings** - Current buildings in settlements
- **BuildingQueue** - Building upgrade queue
- **BuildingConfig** - Building costs and properties
- **TradeOffers/TradeTransactions** - Trading system
- **MilitaryUnits/MilitaryUnitConfig/MilitaryTrainingQueue** - Military system
- **UnitResearch/ResearchConfig/ResearchQueue** - Research system

### Views
- **OpenBuildingQueue** - Active building upgrades with progress
- **BuildingDetails** - Building upgrade costs and information
- **SettlementSettlers** - Settler calculations
- **OpenMilitaryTrainingQueue** - Military training progress
- **OpenResearchQueue** - Research progress

### Stored Procedures
- **CreatePlayerWithSettlement** - Creates a new player with initial settlement
- **UpgradeBuilding** - Handles building upgrades with resource costs
- **UpdateQueueTimesAfterTownHallUpgrade** - Recalculates build times

### Events
- **ProcessBuildingQueue** - Automatically completes finished buildings (every 5 seconds)
- **UpdateResources** - Updates resource production (every 10 seconds)

## Environment Detection

The scripts automatically detect whether you're running:

1. **Docker Environment**: Looks for `docker-compose.yml` and Docker commands
2. **Manual Environment**: Falls back to direct MySQL connection

## Requirements

### For Docker Environment
- Docker and Docker Compose installed
- `docker-compose.yml` file in project root
- Running database container

### For Manual Environment
- MySQL/MariaDB server running
- Database credentials (root password required)
- `mysql` command line client

## Troubleshooting

### Common Issues

1. **"Database connection failed"**
   - Ensure MySQL/MariaDB is running
   - Check credentials
   - For Docker: ensure containers are running

2. **"SQL file not found"**
   - Ensure `../sql/database.sql` exists relative to the script directory
   - The main SQL file should contain all schema definitions inline (no SOURCE commands)

3. **"Docker containers not running"**
   - Run `docker-compose up -d` first
   - Or let the script start them for you when prompted

4. **"Permission denied"**
   - Ensure scripts are executable: `chmod +x *.sh`

### Verification

After running any rebuild script, you can verify success by:

1. Checking that tables exist:
   ```bash
   # Docker
   docker-compose exec db mysql -u browsergame -psicheresPasswort -e "SHOW TABLES;" browsergame
   
   # Manual
   mysql -u browsergame -psicheresPasswort -e "SHOW TABLES;" browsergame
   ```

2. Checking for initial data:
   ```bash
   # Check for players
   docker-compose exec db mysql -u browsergame -psicheresPasswort -e "SELECT * FROM Spieler;" browsergame
   ```

3. Testing the web interface:
   - Docker: http://localhost:8080/
   - Manual: http://localhost/browsergame/

## Safety Features

- All scripts require explicit confirmation before destructive operations (unless `--force` is used)
- Database backups are not created automatically - ensure you backup important data before running
- Scripts will verify database connectivity before proceeding
- Detailed logging shows what operations are being performed

## Integration with Fresh Start

The database rebuild scripts are also integrated with the main `fresh-start.sh` script, which provides a complete environment reset including:

- Docker container cleanup
- Database rebuild (using these scripts)
- Web server restart
- Event scheduler activation
- Verification of all components