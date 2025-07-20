# Database Setup and Execution Order

This document describes the proper order for executing SQL files and sets up the database with the correct sequence.

## Execution Order

The database must be created in the following exact order for proper functionality:

### 1. Database and User Setup
- Drop existing database if it exists
- Create new database `browsergame`
- Create users with proper permissions
- Switch to the new database

### 2. Core Tables (Foundation)
Execute in this order:
- `sql/tables/core_tables.sql` - Essential game tables (Player, Settlement, Buildings, etc.)
- `sql/tables/military_tables.sql` - Military system tables
- `sql/tables/research_tables.sql` - Research system tables  
- `sql/tables/kaserne_tables.sql` - Kaserne system tables

### 3. Initial Data Population
Execute in this order:
- `sql/data/initial_data.sql` - Core game configuration data
- `sql/data/military_data.sql` - Military unit configurations
- `sql/data/research_data.sql` - Research system configuration
- `sql/data/kaserne_data.sql` - Kaserne system configuration

### 4. Stored Procedures
Execute in this order:
- `sql/procedures/player_procedures.sql` - Player management procedures
- `sql/procedures/building_procedures.sql` - Building management procedures
- `sql/procedures/military_procedures.sql` - Military management procedures
- `sql/procedures/initialization_procedures.sql` - Database initialization procedures

### 5. Views (Simple to Complex)
Execute in this order:
- `sql/views/game_views.sql` - Core game views
- `sql/views/enhanced_views.sql` - Enhanced views for simplified PHP access

### 6. Database Events
Execute:
- `sql/data/database_events.sql` - Automated processing events

### 7. Final Setup
- Enable global event scheduler
- Enable created events
- Run validation procedures
- Create initial admin player if database is empty

## Current Implementation

The main `sql/database.sql` file currently contains all components inline for compatibility. This ensures:

1. **Single File Deployment** - Easy deployment with one command
2. **Correct Execution Order** - All components are included in the right sequence
3. **Error Prevention** - No missing dependencies or incorrect ordering
4. **Automatic Initialization** - Creates admin player and validates setup

## Modular Structure Benefits

The organized modular structure in subdirectories provides:

1. **Maintainability** - Easy to update individual components
2. **Documentation** - Clear separation of concerns
3. **Testing** - Individual components can be tested separately
4. **Development** - Easier to work on specific features

## Views for Simplified PHP Access

The enhanced views replace complex JOIN queries in PHP:

### SettlementResources
```sql
SELECT * FROM SettlementResources WHERE settlementId = ?;
```
Replaces complex player+settlement joins with resource and production data.

### BuildingDetails
```sql
SELECT * FROM BuildingDetails WHERE settlementId = ? AND buildingType = ?;
```
Provides complete building information including costs and requirements.

### AllBuildingsOverview
```sql
SELECT * FROM AllBuildingsOverview WHERE settlementId = ?;
```
Complete building overview for a settlement in a single query.

### MilitaryTrainingCosts
```sql
SELECT * FROM MilitaryTrainingCosts WHERE settlementId = ?;
```
Military unit costs with affordability checks.

### ResearchCosts
```sql
SELECT * FROM ResearchCosts WHERE settlementId = ?;
```
Research costs with prerequisite and affordability checks.

### GameStatistics
```sql
SELECT * FROM GameStatistics;
```
Game-wide statistics in a single query.

## PHP Database Access

The PHP layer uses these views through:

1. **Repository Pattern** - Organized database access through repositories
2. **Simple Queries** - Views eliminate complex JOINs in PHP code
3. **Data Validation** - Views include affordability and validation logic
4. **Performance** - Database-optimized queries instead of multiple PHP queries

## Initialization Procedures

### CreatePlayerWithSettlement(playerName)
- Creates player with default starting values
- Creates settlement with proper resource amounts
- Initializes all building types (some at level 0)
- Sets up military units and research status
- Places settlement on map with unique coordinates

### InitializeGameDatabase()
- Clears existing data safely
- Creates admin player
- Resets auto-increment counters
- Validates setup

### ValidateDatabase()
- Checks database structure
- Counts tables, views, procedures, events
- Validates data integrity
- Returns comprehensive status report

## Event System

Automated processing events handle:

1. **Resource Production** - Updates resources every second based on building levels
2. **Building Completion** - Processes building queue every 5 seconds
3. **Military Training** - Processes military training queue every 5 seconds
4. **Research Completion** - Processes research queue every 5 seconds

All events respect storage limits and building dependencies.

## Validation and Testing

Use the validation script to check setup:
```bash
./validate-sql.sh
```

This checks:
- SQL file structure and syntax
- PHP syntax for all files
- Required components and counts
- Database schema validation

The test script `test-enhanced-views.php` validates:
- View functionality
- Procedure execution
- Event status
- Data integrity