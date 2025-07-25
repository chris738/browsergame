# Archived Redundant SQL Files

This directory contains SQL files that were previously loaded by Docker Compose but are now redundant because their content has been consolidated into the main `database.sql` file.

## Files Archived

### Previously Loaded by Docker (redundant-files/)
- `add-kaserne.sql` - Added Kaserne building type to ENUMs (already in database.sql)
- `military-units.sql` - Defined military tables and columns (already in database.sql)  
- `schema-fixes.sql` - Added missing columns and tables (already in database.sql)
- `init-player.sql` - Created test player (now integrated in database.sql)
- `enable-events.sql` - Enabled database events (now integrated in database.sql)
- `fix-resource-generation.sql` - Fixed UpdateResources event (already in database.sql)
- `procedures/` - All stored procedures (already in database.sql)
- `views/` - All database views (already in database.sql)

### Historical Versions
- Various update scripts and older versions of schema files

## Why These Files Were Redundant

The main `database.sql` file was already a complete, self-contained database schema that included:
- All tables with correct structure
- All stored procedures
- All views  
- All database events
- All configuration data
- Player initialization

Loading these additional files after `database.sql` was redundant and could potentially cause conflicts or errors due to:
- Duplicate table definitions
- Redundant data insertion
- ENUM modifications after creation
- Multiple procedure definitions

## Restoration

If you need to restore the old multi-file loading approach:
1. Copy files from `redundant-files/` back to their original locations
2. Update `docker-compose.yml` to include all the volume mounts
3. Ensure proper loading order (01-11 numbering)

However, the consolidated approach is recommended for its simplicity and reliability.