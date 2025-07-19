# SQL File Organization

The SQL files in this project have been reorganized into a structured directory layout for better maintainability and clarity.

## Directory Structure

```
sql/
├── data/                 # Data insertion and update scripts
│   ├── initial_data.sql     # Core initial data
│   ├── kaserne_data.sql     # Kaserne configuration data
│   ├── military_data.sql    # Military unit configuration
│   ├── research_data.sql    # Research system data
│   └── database_events.sql  # Automated processing events
├── procedures/          # Stored procedures
│   ├── building_procedures.sql  # Building management procedures
│   ├── player_procedures.sql    # Player management procedures
│   └── military_procedures.sql  # Military operations procedures
├── tables/              # Table definitions and modifications
│   ├── core_tables.sql      # Core game tables
│   ├── kaserne_tables.sql   # Kaserne table modifications
│   ├── military_tables.sql  # Military system tables
│   └── research_tables.sql  # Research system tables
├── views/               # Database views
│   ├── game_views.sql       # Core game views
│   └── enhanced_views.sql   # Enhanced views for simplified queries
├── archive/             # Archived monolithic files
└── database.sql         # Main database initialization file
```

## File Categories

### Tables (`tables/`)
Contains CREATE TABLE and ALTER TABLE statements that define the database schema:
- **core_tables.sql**: Essential game tables (Player, Settlement, Buildings, etc.)
- **kaserne_tables.sql**: Table modifications to support Kaserne buildings
- **military_tables.sql**: Tables for military units and training
- **research_tables.sql**: Tables for the research system

### Data (`data/`)
Contains INSERT and UPDATE statements for initial data and configuration:
- **initial_data.sql**: Core game data and configurations
- **kaserne_data.sql**: Configuration data for Kaserne buildings
- **military_data.sql**: Military unit configurations and initial data
- **research_data.sql**: Research system configuration
- **database_events.sql**: Automated event definitions for queue processing

### Procedures (`procedures/`)
Contains stored procedure definitions:
- **building_procedures.sql**: Procedures for building operations
- **player_procedures.sql**: Procedures for player management
- **military_procedures.sql**: Procedures for military operations (training, research)

### Views (`views/`)
Contains view definitions to simplify complex queries:
- **game_views.sql**: Core views (OpenBuildingQueue, BuildingDetails, SettlementSettlers)
- **enhanced_views.sql**: Additional views for simplified PHP queries

## Enhanced Views for PHP Simplification

The following views have been created to replace complex JOIN queries in PHP files:

### SettlementResources
Complete settlement resource information with player data:
```sql
SELECT * FROM SettlementResources WHERE settlementId = ?;
```

### SettlementOverview
Comprehensive settlement overview with key metrics:
```sql
SELECT * FROM SettlementOverview WHERE settlementId = ?;
```

### MilitaryOverview
Military unit summary per settlement:
```sql
SELECT * FROM MilitaryOverview WHERE settlementId = ?;
```

### ResearchStatus
Research completion status per settlement:
```sql
SELECT * FROM ResearchStatus WHERE settlementId = ?;
```

### BuildingUpgradeCosts
Building upgrade costs with affordability check:
```sql
SELECT * FROM BuildingUpgradeCosts WHERE settlementId = ? AND buildingType = ?;
```

### ActiveQueues
All active queues (building, military, research) in one view:
```sql
SELECT * FROM ActiveQueues WHERE settlementId = ?;
```

## Usage in PHP

Instead of complex JOIN queries, PHP files can now use simple SELECT statements on views:

**Before (complex query):**
```php
$sql = "SELECT s.wood, s.stone, s.ore, p.name as playerName, p.gold 
        FROM Settlement s 
        JOIN Spieler p ON s.playerId = p.playerId 
        WHERE s.settlementId = ?";
```

**After (simple view query):**
```php
$sql = "SELECT * FROM SettlementResources WHERE settlementId = ?";
```

## Database Initialization

The main `database.sql` file orchestrates the database creation by including essential components inline for compatibility. For development and maintenance, reference the individual organized files.

## Migration Notes

- Original monolithic files have been moved to `sql/archive/`
- The main `database.sql` includes all essential components inline
- Views are designed to maintain backward compatibility with existing PHP code
- All functionality remains intact while improving maintainability

## Benefits

1. **Better Organization**: Related SQL code is grouped together
2. **Easier Maintenance**: Changes can be made to specific components without affecting others
3. **Simplified PHP**: Complex queries replaced with simple view selections
4. **Better Documentation**: Each file has a clear purpose and documentation
5. **Reusability**: Components can be executed independently for testing or migration