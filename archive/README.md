# Archive Directory

This directory contains files that are no longer actively used in the browsergame project but are preserved for historical reference and potential future use.

## Directory Structure

### `database-original/`
Contains the original monolithic database implementation that was replaced by the new modular architecture in December 2024:
- `database_original.php` - The original monolithic database class
- `database_original.sql` - Original database schema
- `database_original_backup.sql` - Backup of original schema
- `database_original_monolithic.sql` - Monolithic version of schema

### `standalone-tests/`
Contains standalone test files that were used for development and debugging but are not part of the main test suite:
- `test-travel-system.php` - Basic travel system functionality test
- `test-event-travel-system.php` - Event-based travel system verification test

### `setup-scripts/`
Contains setup scripts that may have been superseded by newer installation methods:
- `setup-travel-system.sh` - Travel system setup script (potentially replaced by Docker setup)

## Notes

- All files in this archive were moved here to clean up the main repository structure
- The files are preserved for reference and could be restored if needed
- The main application now uses the modular architecture in `php/database/` with repositories
- Current installation is handled through Docker Compose as documented in the main README