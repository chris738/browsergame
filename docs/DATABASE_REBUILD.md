# Database Rebuild Scripts

This directory contains scripts for rebuilding the database from the organized SQL structure. These scripts are essential when:

- Database schema changes are made
- Tables need to be recreated
- Complete database reset is required
- Development environment needs fresh data

**Note**: The SQL files have been reorganized into a structured directory layout. See [SQL_ORGANIZATION.md](SQL_ORGANIZATION.md) for details on the new structure.

## Scripts Overview

### 1. `rebuild-database.sh` (Main Script)
**Purpose**: Complete database rebuild from `database.sql`

**Features**:
- Automatically detects Docker vs manual installation
- Drops existing database completely
- Recreates database from `database.sql`
- Creates initial player data
- Verifies successful rebuild
- Clear error handling and logging

**Usage**:
```bash
# Interactive mode (asks for confirmation)
./rebuild-database.sh

# Force mode (no confirmation)
./rebuild-database.sh --force

# Show help
./rebuild-database.sh --help
```

**Requirements**:
- For Docker: `docker-compose.yml` and running containers
- For Manual: MariaDB/MySQL with root access
- `database.sql` file must exist

### 2. `quick-rebuild-db.sh` (Development Helper)
**Purpose**: Quick database rebuild for development

**Features**:
- Wrapper around `rebuild-database.sh --force`
- No confirmation prompts
- Perfect for rapid development cycles

**Usage**:
```bash
./quick-rebuild-db.sh
```

## Environment Support

### Docker Environment
- Automatically detected if `docker-compose.yml` exists
- Uses container database credentials
- Starts containers if not running
- Creates test player from `docker/init-player.sql`

### Manual Installation
- Prompts for root database password
- Creates `browsergame` user if needed
- Creates admin player
- Works with local MariaDB/MySQL

## What Gets Rebuilt

1. **Database Structure**:
   - All tables (Spieler, Settlement, Buildings, etc.)
   - Stored procedures (CreatePlayerWithSettlement, UpgradeBuilding, etc.)
   - Events (UpdateResources, etc.)
   - Views (OpenBuildingQueue, BuildingDetails, etc.)

2. **Initial Data**:
   - Building configuration data
   - Initial player (TestPlayer for Docker, Admin for manual)
   - Default settlements and resources

3. **Database Settings**:
   - Event scheduler enabled
   - Proper user permissions
   - Database charset/collation

## Error Handling

The scripts include comprehensive error handling:
- Validates SQL file existence
- Tests database connections
- Verifies rebuild success
- Clear error messages with color coding
- Graceful exit on failures

## Integration with Development Workflow

### Schema Changes
When modifying `database.sql`:
1. Make changes to `database.sql`
2. Run `./rebuild-database.sh --force`
3. Test your changes
4. Commit both schema and script changes

### Testing
```bash
# Quick rebuild for testing
./quick-rebuild-db.sh

# Verify rebuild worked
docker-compose exec db mysql -u browsergame -psicheresPasswort -e "SELECT * FROM Spieler;" browsergame
```

## Comparison with Existing Scripts

| Script | Purpose | Complexity | Use Case |
|--------|---------|------------|----------|
| `rebuild-database.sh` | Database rebuild | Simple | Schema changes, development |
| `reset-database.sh` | Database-only reset | Complex | Full reset with verification |
| `reset.sh` | Complete system reset | Complex | Full environment reset |

## Tips

1. **Backup First**: Always backup important data before rebuilding
2. **Docker Preferred**: Use Docker setup for easier management  
3. **Development Cycle**: Use `quick-rebuild-db.sh` for rapid iteration
4. **Schema Validation**: Always test schema changes after rebuild
5. **Version Control**: Commit `database.sql` changes with script updates

## Troubleshooting

### Common Issues

**"Database connection failed"**
- Check if MariaDB/MySQL is running
- Verify credentials
- Ensure database server is accessible

**"SQL file not found"**
- Make sure you're in the project root directory
- Verify `database.sql` exists and is readable

**"Containers not running"**
- Run `docker-compose up -d` to start containers
- Wait a few seconds for database to initialize

**"Permission denied"**
- Make scripts executable: `chmod +x *.sh`
- Check file permissions

### Manual Debugging

```bash
# Check Docker containers
docker-compose ps

# Check database directly
docker-compose exec db mysql -u root -proot123

# View script execution with debug
bash -x ./rebuild-database.sh --force
```

## Security Notes

- Database passwords are handled securely
- No credentials stored in scripts
- Uses environment variables where possible
- Prompts for sensitive input when needed