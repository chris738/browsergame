# Database Structure Documentation

## Overview

The database schema for the browsergame has been modularized into logical components for better maintainability and organization. This document explains the structure and how to use it.

## File Structure

```
sql/
├── database.sql              # Legacy monolithic file (kept for compatibility)
├── database_modular.sql      # Main orchestrator file (recommended)
├── data/                     # Initial data and configuration
│   ├── database_setup.sql    # Database and user creation
│   ├── initial_data.sql      # Building configuration data
│   ├── military_data.sql     # Military unit configurations
│   ├── research_data.sql     # Research system data
│   ├── travel_data.sql       # Travel system configuration
│   └── kaserne_data.sql      # Kaserne system data
├── tables/                   # Table definitions organized by system
│   ├── core_tables.sql       # Core game tables (players, settlements, buildings)
│   ├── military_tables.sql   # Military system tables
│   ├── research_tables.sql   # Research system tables
│   ├── travel_tables.sql     # Travel and movement tables
│   ├── battle_tables.sql     # Battle system tables
│   └── kaserne_tables.sql    # Kaserne system tables
├── procedures/               # Stored procedures by functionality
│   ├── player_procedures.sql # Player creation and management
│   ├── building_procedures.sql # Building upgrade logic
│   ├── military_procedures.sql # Military unit training
│   ├── travel_procedures.sql # Travel and battle processing
│   └── initialization_procedures.sql # Database setup procedures
├── views/                    # Database views
│   ├── game_views.sql        # Core game views
│   └── enhanced_views.sql    # Enhanced views for PHP access
└── events/                   # Database events for automation
    ├── game_events.sql       # Resource updates and queue processing
    ├── travel_events.sql     # Travel arrival processing
    └── enable_events.sql     # Event scheduler setup
```

## Usage

### For Development (Recommended)

Use the modular approach for easier maintenance:

```bash
# Load the complete database using modular structure
mysql -u root -p < sql/database_modular.sql
```

### For Legacy Support

The original single file is still available:

```bash
# Load using the legacy single file
mysql -u root -p < sql/database.sql
```

### Loading Individual Components

You can load individual components as needed:

```bash
# Setup database and users
mysql -u root -p < sql/data/database_setup.sql

# Load only core tables
mysql -u root -p < sql/tables/core_tables.sql

# Load only military system
mysql -u root -p < sql/tables/military_tables.sql
mysql -u root -p < sql/data/military_data.sql
mysql -u root -p < sql/procedures/military_procedures.sql
```

## Loading Order

The modular files must be loaded in this specific order:

1. **Database Setup** - Create database and users
2. **Tables** - Create all table structures
3. **Procedures** - Create stored procedures
4. **Views** - Create database views  
5. **Data** - Insert initial configuration data
6. **Events** - Create and enable database events

The `database_modular.sql` file handles this ordering automatically.

## Benefits of Modular Structure

- **Easier Maintenance**: Changes to specific systems only require editing relevant files
- **Better Organization**: Related functionality is grouped together
- **Selective Loading**: Load only the components you need for testing
- **Clear Dependencies**: Easy to see what depends on what
- **Team Development**: Multiple developers can work on different systems simultaneously
- **Version Control**: Smaller, focused changes in git history

## Migration from Legacy

If you're currently using `database.sql`, you can migrate to the modular approach:

1. Back up your current database
2. Use `database_modular.sql` instead of `database.sql`
3. The functionality is identical, just better organized

## Customization

To customize specific systems:

- **Buildings**: Edit `sql/data/initial_data.sql`
- **Military Units**: Edit `sql/data/military_data.sql`
- **Research**: Edit `sql/data/research_data.sql`
- **Game Logic**: Edit relevant procedure files
- **Database Events**: Edit files in `sql/events/`

## Testing

A validation script is available to check the modular structure:

```bash
./validate_sql.sh
```

This will verify that all referenced files exist and have basic SQL syntax validation.