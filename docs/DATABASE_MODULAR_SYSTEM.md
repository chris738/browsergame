# Database Modular System - README

## Overview

The Browsergame database has been completely reorganized from a monolithic 1400+ line SQL file into a **modular, maintainable system** with clear separation of concerns and proper dependency management.

## Problem Solved

**Before (Monolithic System):**
- Single huge `database.sql` file (1423 lines)  
- Everything mixed together: setup, tables, data, procedures, views, events
- Hard to maintain and debug
- Difficult to understand dependencies
- Risk of conflicts when multiple developers work on it

**After (Modular System):**
- Clean separation by functionality and loading phase
- Easy to find and modify specific components
- Clear dependency order prevents loading issues
- Better collaboration and maintenance

## File Structure

```
sql/
├── 01-database-setup.sql              # Bootstrap: DB creation, users, permissions
├── 99-final-setup.sql                 # Finalization: events, validation
├── tables/                            # Table definitions (dependency order)
│   ├── core_tables.sql                # Core game tables (Player, Settlement, Map)
│   ├── military_tables.sql            # Military units and training systems
│   ├── research_tables.sql            # Research and technology system
│   ├── kaserne_tables.sql             # Kaserne (barracks) system
│   ├── travel_tables.sql              # Travel and movement system
│   └── battle_tables.sql              # Battle and combat system
├── data/                              # Configuration data (when needed)
│   ├── initial_data.sql               # Core configurations (ALWAYS needed)
│   ├── military_data.sql              # Military unit configurations
│   ├── research_data.sql              # Research configurations
│   ├── kaserne_data.sql               # Kaserne configurations
│   ├── military_travel_data.sql       # Travel system configurations
│   └── database_events.sql            # Automated events (resource generation)
├── procedures/                        # Stored procedures (by functionality)
│   ├── player_procedures.sql          # Player and settlement management
│   ├── building_procedures.sql        # Building operations and upgrades
│   ├── military_procedures.sql        # Military training and combat
│   ├── travel_procedures.sql          # Travel and movement procedures
│   └── initialization_procedures.sql  # Database initialization utilities
├── views/                             # Database views (for application layer)
│   ├── game_views.sql                 # Core game views
│   └── enhanced_views.sql             # Advanced views for complex queries
├── database.sql                       # 📋 DOCUMENTATION ORCHESTRATOR (this explains everything)
├── database_modular_working.sql       # Manual deployment script (uses SOURCE)
├── database_monolithic_backup.sql     # 💾 Backup of the old monolithic file
└── archive/                           # Old files kept for reference
    └── replaced_by_modular_system/    # Files replaced by the new modular system
```

## Loading Phases

The database is built in **7 phases** with proper dependency management:

### Phase 1: Setup (01-)
- Database creation
- User accounts and permissions  
- Basic infrastructure

### Phase 2: Tables (02-07)
- Core game structure first
- Feature tables in dependency order
- Foreign key constraints respected

### Phase 3: Data (10-14) 
- Core configuration data (building costs, etc.)
- Feature-specific configurations
- Only after corresponding tables exist

### Phase 4: Procedures (20-24)
- Player and game management
- Business logic operations  
- Only after required tables/data exist

### Phase 5: Views (30-31)
- Application layer interfaces
- Complex query simplification
- Only after tables and procedures exist

### Phase 6: Events (40)
- Background automation (resource generation)
- Scheduled processing
- Only after all dependencies ready

### Phase 7: Finalization (99)
- Enable event scheduler
- Final validation
- System readiness confirmation

## When Data is Loaded

### Bootstrap Time (Database Creation)
- **Tables**: Core game structure
- **Config Data**: Building costs, unit stats, research requirements
- **Procedures**: Essential game operations

### Runtime (During Gameplay)  
- **Views**: Efficient data access for PHP application
- **Events**: Background processing (resource generation, queue processing)

### Development Time (Adding Features)
- **New Tables**: Added in appropriate dependency phase
- **Config Data**: Added after table creation
- **Procedures**: Added to implement new functionality
- **Views**: Added to expose new features to application

## How to Use

### Docker Deployment (Recommended)
```bash
# The docker-compose.yml automatically handles the modular loading
docker compose up -d db

# To restart with fresh database:
docker compose down
docker volume rm browsergame_db_data  
docker compose up -d db
```

### Manual MySQL Deployment
```bash
# Use the SOURCE-based orchestrator
mysql -u root -p < sql/database_modular_working.sql
```

### Development Workflow
1. **Edit** individual files in `sql/tables/`, `sql/data/`, `sql/procedures/`, `sql/views/`
2. **Add** new files following the naming convention (`02-`, `10-`, `20-`, etc.)  
3. **Update** `docker-compose.yml` if you add completely new files
4. **Test** by recreating the database:
   ```bash
   docker compose down && docker volume rm browsergame_db_data && docker compose up -d db
   ```

## Monitoring

The system tracks loading progress in the `_ModularLoadingProgress` table:

```sql
SELECT * FROM _ModularLoadingProgress ORDER BY loaded_at;
```

This shows which phases completed successfully and when.

## Benefits

1. **🔧 Maintainability**: Easy to find and modify specific functionality
2. **📦 Modularity**: Add/remove features by adding/removing files  
3. **🔍 Debugging**: Isolate issues to specific components
4. **👥 Collaboration**: Multiple developers can work on different files
5. **🧪 Testing**: Test individual components separately
6. **⚡ Performance**: Load only what you need for specific environments
7. **📊 Dependencies**: Clear loading order prevents dependency issues

## Migration Information

- **Old File**: `sql/database_monolithic_backup.sql` (1423 lines - preserved for reference)
- **New System**: 20+ modular files organized by functionality
- **Compatibility**: 100% feature-compatible, same database schema
- **Docker**: Automatically uses the new modular system
- **Manual**: Use `sql/database_modular_working.sql` for SOURCE-based deployment

## Troubleshooting

### Container won't start
```bash
# Check logs
docker logs browsergame-db-1

# Common fix: Remove old volume
docker volume rm browsergame_db_data
```

### SQL syntax errors
- Check individual modular files for syntax issues
- The loading order is shown in docker logs during startup
- Use `_ModularLoadingProgress` table to see which phase failed

### Missing tables/data
- Verify all required files are mounted in `docker-compose.yml`
- Check that loading phases completed in `_ModularLoadingProgress`
- Compare table count: should be ~41 tables, ~16 views, ~14 procedures

This modular system makes the database much more maintainable while preserving all existing functionality!