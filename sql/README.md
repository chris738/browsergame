# SQL Database Structure

## Consolidated Database Setup

This folder now uses a consolidated approach for database initialization:

### Active Files

- **`database.sql`** - Complete, self-contained database schema including:
  - All table definitions
  - All stored procedures  
  - All views
  - All database events
  - Initial configuration data
  - Test player creation

### Archived Files

The following files have been moved to `archive/redundant-files/` as their content was redundant with `database.sql`:

- `add-kaserne.sql` - Kaserne building type already included in database.sql
- `military-units.sql` - Military tables already defined in database.sql
- `schema-fixes.sql` - Schema fixes already incorporated in database.sql
- `init-player.sql` - Player creation now integrated in database.sql
- `enable-events.sql` - Event enabling now integrated in database.sql  
- `fix-resource-generation.sql` - Fixed event already included in database.sql
- `procedures/` directory - All procedures already in database.sql
- `views/` directory - All views already in database.sql

### Modular Structure

The `tables/` and `data/` directories contain organized SQL files that represent the modular structure referenced in `database_modular.sql`. These are kept for development purposes but are not used in the actual Docker initialization.

### Docker Initialization

Docker Compose now loads only:
1. `database.sql` - Complete database setup

This simplifies the initialization process from 11 separate SQL files to 1 comprehensive file, reducing complexity and potential loading order issues.

### Benefits of Consolidation

1. **Simplified Setup** - Single file contains everything needed
2. **Faster Initialization** - No dependency on loading order of multiple files
3. **Reduced Redundancy** - Eliminates duplicate table/procedure definitions
4. **Easier Maintenance** - Changes only need to be made in one place
5. **Clear Structure** - One source of truth for database schema

### For Development

If you need to make changes to the database schema:
1. Edit `database.sql` directly, or
2. Use the modular files in `tables/` and `data/` and regenerate `database.sql`

The modular structure is preserved for development convenience but is not used in production deployment.