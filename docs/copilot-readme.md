# COPILOT README - Project Structure & Development Context

> **⚠️ IMPORTANT FOR AI ASSISTANTS**: This document must be updated by Copilot after each patch when project structure changes are made.

This document provides comprehensive context for AI assistants working on this browser game project. It explains the architecture, file organization, and development patterns to enable faster understanding and more accurate code changes.

## 🏗️ Architecture Overview

### Recent Major Restructuring (Dec 2024)
- **Before**: Monolithic `database.php` (2015 lines)  
- **After**: Modular architecture with Repository Pattern
- **Backward Compatibility**: 100% maintained - all existing code works unchanged

### Core Architecture Pattern
- **Repository Pattern**: Domain-specific data access layers
- **Dependency Injection**: Repositories injected into main Database class
- **Single Responsibility**: Each class handles one domain
- **Interface-based**: `DatabaseInterface` defines all public methods

## 📁 Detailed Project Structure

```
browsergame/
├── README.md                    # Minimal project overview  
├── docker-compose.yml          # Docker orchestration
│
├── 🌐 FRONTEND FILES
├── index.php                   # Main game interface
├── admin.php                   # Admin panel
├── kaserne.php                 # Military/Barracks interface  
├── market.php                  # Trading interface
├── map.php                     # Map view
├── settlement-info.php         # Settlement details
├── css/
│   ├── style.css              # Main game styles
│   └── admin.css              # Admin panel styles
├── js/
│   ├── backend.js             # Main game AJAX logic
│   ├── admin.js               # Admin panel functionality
│   ├── market.js              # Trading system frontend
│   └── translations.js        # Multi-language support
│
├── 🗄️ DATABASE LAYER (RESTRUCTURED)
├── php/
│   ├── database.php           # Main Database class (256 lines, was 2015)
│   ├── database/              # MODULAR ARCHITECTURE:
│   │   ├── interfaces/
│   │   │   └── DatabaseInterface.php    # 58 method definitions
│   │   ├── connection/
│   │   │   └── DatabaseConnection.php   # Connection management only
│   │   ├── schema/
│   │   │   └── DatabaseSchemaManager.php # Table creation & schema
│   │   ├── procedures/
│   │   │   └── BuildingProcedures.php   # Stored procedure definitions
│   │   └── repositories/                # DOMAIN-SPECIFIC LOGIC:
│   │       ├── ResourceRepository.php   # Resources & regeneration
│   │       ├── BuildingRepository.php   # Building operations  
│   │       ├── SettlementRepository.php # Settlements & queues
│   │       ├── MapRepository.php        # Map operations
│   │       ├── AdminRepository.php      # Player management
│   │       ├── TradingRepository.php    # Market operations
│   │       └── MilitaryRepository.php   # Military & research
│   │
│   ├── 📡 API ENDPOINTS
│   ├── backend.php             # Main game API
│   ├── admin-backend.php       # Admin API endpoints
│   ├── market-backend.php      # Trading API
│   └── navigation.php          # UI navigation components
│
├── 🗃️ SQL ORGANIZATION (RESTRUCTURED)
├── sql/
│   ├── database.sql           # Main consolidated schema file
│   ├── tables/
│   │   └── core_tables.sql    # All CREATE TABLE statements
│   ├── views/  
│   │   └── game_views.sql     # All CREATE VIEW statements
│   ├── procedures/
│   │   ├── player_procedures.sql    # Player/settlement creation
│   │   └── building_procedures.sql  # Building upgrade procedures
│   ├── data/
│   │   └── initial_data.sql   # Configuration INSERT statements
│   └── [legacy files]         # Various migration/update files
│
├── 🔧 SCRIPTS & UTILITIES
├── scripts/
│   ├── fresh-start.sh         # Complete environment reset
│   ├── docker-start.sh        # Docker with event scheduler
│   ├── rebuild-database.sh    # Database reconstruction
│   ├── reset-database.sh      # Game data reset only
│   └── install.sh            # System installation
│
├── 📚 DOCUMENTATION
├── docs/
│   ├── copilot-readme.md        # THIS FILE - AI context & structure
│   ├── INSTALLATION.md          # Setup troubleshooting
│   ├── GAME_MECHANICS.md        # Complete gameplay mechanics  
│   ├── ADMIN_README.md          # Admin panel usage
│   ├── DEVELOPMENT.md           # Contributing guidelines
│   ├── API_DOCUMENTATION.md     # REST endpoint reference
│   ├── FRESH-START.md           # Environment reset guide
│   ├── DATABASE_REBUILD.md      # Database management docs
│   ├── DATABASE_EXECUTION_ORDER.md # Database setup order
│   ├── BUGFIX_RESOURCE_GENERATION.md # Resource system fix docs
│   ├── INTEGRATION_SUMMARY.md   # Integration documentation
│   ├── PRODUCTION_DEPLOYMENT.md # Production setup
│   └── examples/
│       └── enhanced-views-examples.php # Database views examples
│
└── 🧪 TESTING
└── tests/
    ├── test-admin-login.php      # Admin authentication
    ├── test-advanced-sql.php     # SQL functionality  
    ├── test-barracks-upgrade.php # Military system
    ├── test-data-integrity.php   # Database integrity
    ├── test-error-scenarios.php  # Error handling
    ├── test-sql-data-reading.php # Data access
    ├── test-validation.php       # Input validation
    ├── test-web-interface.php    # UI functionality
    ├── test-enhanced-views.php   # Database views testing
    ├── test-events.sh            # Event system testing
    ├── final-validation-script.sh # Complete validation
    ├── final-validation.php     # Final system validation
    ├── final-verification.php   # System verification
    └── validate-sql.sh          # SQL validation script
```

## 🎯 Domain Logic Organization

### Resource Management (`ResourceRepository.php`)
- `getResources()` - Fetch player resources
- `getRegen()` - Get resource regeneration rates  
- `updateResources()` - Apply resource changes
- Methods: 15+ resource-related functions

### Building System (`BuildingRepository.php`)
- `getBuilding()` - Fetch building details
- `upgradeBuilding()` - Handle building upgrades
- `getBuildQueue()` - Queue management
- Methods: 12+ building-related functions

### Settlement Operations (`SettlementRepository.php`)
- `getSettlement()` - Settlement data
- `createSettlement()` - New settlement creation
- `getQueue()` - Construction queues
- Methods: 8+ settlement functions

### Military System (`MilitaryRepository.php`)
- `getUnits()` - Military units
- `trainUnit()` - Unit training
- `getResearch()` - Research system
- Methods: 10+ military functions

### Trading System (`TradingRepository.php`)
- `getOffers()` - Market offers
- `createOffer()` - Place trades
- `acceptOffer()` - Execute trades
- Methods: 6+ trading functions

### Map System (`MapRepository.php`)
- `getMap()` - Map data retrieval
- `updateCoords()` - Coordinate updates
- Methods: 4+ map functions

### Admin Operations (`AdminRepository.php`)
- `getAllPlayers()` - Player management
- `deletePlayer()` - Player deletion
- `getAdminStats()` - System statistics  
- Methods: 8+ admin functions

## 🔄 Database Interaction Patterns

### Connection Management
```php
// Connection handled by DatabaseConnection class
$connection = new DatabaseConnection();
$pdo = $connection->getConnection();
```

### Repository Usage
```php
// Each repository handles its domain
$resourceRepo = new ResourceRepository($connection);
$resources = $resourceRepo->getResources($playerId);
```

### Main Database Class (Facade Pattern)
```php
// Public interface remains unchanged for backward compatibility
class Database implements DatabaseInterface {
    private $resourceRepo;
    private $buildingRepo;
    // ... other repositories
    
    public function getResources($id) {
        return $this->resourceRepo->getResources($id);
    }
}
```

## 🛠️ Development Patterns

### Adding New Features
1. **Identify Domain**: Which repository should handle the new feature?
2. **Add Method**: Add to appropriate repository class
3. **Update Interface**: Add method signature to `DatabaseInterface.php`
4. **Update Main Class**: Add delegation method to `Database.php`
5. **Test**: Ensure backward compatibility

### File Modification Guidelines
- **Never modify**: `database.php` beyond delegation
- **Add methods to**: Appropriate repository classes
- **Update interface**: When adding public methods
- **Test extensively**: Backward compatibility is critical

### SQL File Organization
- **Tables**: Only CREATE TABLE statements in `tables/core_tables.sql`
- **Views**: Only CREATE VIEW statements in `views/game_views.sql`
- **Procedures**: Domain-grouped in `procedures/` directory
- **Data**: Configuration INSERTs in `data/initial_data.sql`
- **Main File**: `database.sql` includes all components

## 🚀 Deployment & Scripts

### Environment Reset
- `fresh-start.sh` - Complete Docker + database reset
- `rebuild-database.sh` - Database-only reconstruction  
- `reset-database.sh` - Game data reset (preserve structure)

### Docker Setup
- `docker-compose.yml` - Web server + MySQL containers
- Event Scheduler enabled for resource generation
- Automatic database initialization

### Testing Strategy
- **Syntax Tests**: PHP syntax validation
- **Database Tests**: Connection and data integrity
- **Integration Tests**: Full workflow testing
- **Admin Tests**: Administrative functionality

## ⚡ Performance Considerations

### Database Queries
- Prepared statements used throughout
- Repository pattern enables query optimization
- Connection pooling via Docker MySQL

### Resource Generation
- Event Scheduler handles automatic resource updates
- No polling required from frontend
- Efficient UPDATE queries in batches

## 🔐 Security Context

### Development vs Production
- Default admin credentials: admin/admin123 (DEV ONLY)
- SQL injection prevention via prepared statements
- Input validation in all repositories

### Admin Panel
- Session-based authentication
- Player management capabilities
- System statistics and controls

## 📝 Code Style & Standards

### PHP Standards
- PSR-4 autoloading structure
- Class-per-file organization
- Interface-based design

### Database Standards  
- Consistent naming conventions
- Proper foreign key relationships
- Stored procedures for complex operations

## 🔄 Update Guidelines for AI Assistants

**When updating this document:**
1. **Architecture Changes**: Update domain organization when repositories change
2. **File Structure**: Update file tree when files are added/removed/moved
3. **New Patterns**: Document any new development patterns introduced
4. **Database Changes**: Update SQL organization when schema changes
5. **Performance**: Note any performance considerations for new features

---

> **Last Updated**: December 2024 - Major database restructuring completed  
> **Next Update Triggers**: Repository changes, new domains, file structure modifications