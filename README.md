# Browser Game - Settlement Building Game

A web-based strategy game for building and managing settlements. Build structures, collect resources, and expand your settlement in this browser-based building game.

## 🎮 Screenshots

### Main Game - Settlement View
![Main Game Interface](https://github.com/user-attachments/assets/8eb0dacd-41ab-4e82-b0f6-a85cf847512b)

The main view shows your settlement with all buildings, resources, and upgrade options featuring beautiful emoji icons.

### Map View
![Map View](https://github.com/user-attachments/assets/4b3d1e6c-7736-49d4-84ef-7943da853e58)

On the map you can see your settlement and other players in the area.

### Admin Panel
![Admin Login](https://github.com/user-attachments/assets/0c5a52d9-94ee-4cbb-b494-847c6d431d20)

The admin panel allows management of players and settlements.

## 🚀 Installation with Docker

### Prerequisites
- Docker 20.10+
- Docker Compose 2.0+

### Quick Start

```bash
# Clone repository
git clone https://github.com/chris738/browsergame.git
cd browsergame

# Start with Docker
docker compose up -d

# OR: Fresh Start for completely clean environment
./fresh-start.sh
```

That's it! The game runs at **http://localhost:8080** with fully activated automatic resource generation.

### What happens automatically:
- ✅ Starts all Docker containers (Web + Database)
- ✅ Completely initializes the database
- ✅ Activates the Event Scheduler for automatic resource generation
- ✅ Creates a test player
- ✅ Verifies that all systems are working

### Access
- **Game**: http://localhost:8080/
- **Admin Panel**: http://localhost:8080/admin.php
  - Username: `admin`
  - Password: `admin123`

### Docker Commands

```bash
# Stop containers
docker compose down

# Restart containers
docker compose restart

# View logs
docker compose logs -f

# Check status
docker compose ps

# Complete reset (ALL DATA WILL BE LOST!)
./fresh-start.sh --force

# Interactive reset with confirmation
./fresh-start.sh
```

## 🔄 Fresh Start Script - New Feature!

For a guaranteed clean development environment, use the new **Fresh Start Script**:

```bash
# Complete fresh environment (deletes EVERYTHING!)
./fresh-start.sh

# Automatic without confirmation
./fresh-start.sh --force

# With removal of all Docker images
./fresh-start.sh --force --remove-images
```

**What the Fresh Start Script does:**
- ✅ Deletes ALL existing Docker containers, volumes and networks
- ✅ Removes temporary files and logs
- ✅ Creates completely fresh environment from scratch
- ✅ Guarantees no legacy or bug-causing remnants
- ✅ Ideal for clean development environment

📖 **Detailed Documentation**: [docs/FRESH-START.md](docs/FRESH-START.md)

## 🎯 Game Features

- **Settlement Management**: Build and upgrade various buildings
- **Resource System**: Collect and manage wood, stone and ore
- **Real-time Production**: Resources are automatically generated over time
- **Building System**: Building upgrades with queue and construction times
- **Map**: Settlements are placed on a coordinate map
- **Military System**: Train units and manage your army through the barracks
- **Trading System**: Trade resources with other players via the market
- **Admin Panel**: Complete management of players and settlements

### Available Buildings
- **Town Hall**: Center of the settlement
- **Lumberjack**: Produces wood
- **Quarry**: Produces stone  
- **Mine**: Produces ore
- **Storage**: Increases storage capacity for resources
- **Farm**: Provides settlers for other buildings
- **Market**: Enables trading with other players
- **Barracks**: Train military units and manage your army

## 🎮 Quick Start Guide

1. **Open the game**: Navigate to http://localhost:8080/
2. **Collect resources**: Your buildings automatically produce resources
3. **Upgrade buildings**: Click "Upgrade" on any building
4. **Construction times**: Upgrades take time and are shown in the build queue
5. **Storage capacity**: Don't forget to expand your storage!
6. **Military**: Use the barracks to train units and build your army
7. **Trading**: Build a market to trade resources with other players

## 🔧 Troubleshooting

### Common Issues

**Port 8080 already in use:**
```bash
# Use different port (e.g. 8081)
sed -i 's/8080:80/8081:80/g' docker-compose.yml
docker compose up -d
```

**Containers won't start:**
```bash
# Check logs
docker compose logs

# Restart with rebuild
docker compose down
docker compose up -d --build
```

**Database problems:**
```bash
# Delete volumes and recreate
docker compose down -v
docker volume prune -f
docker compose up -d
```

## 📄 Documentation

### Quick Start
- [README](README.md) - This file, project overview and quick start
- [Installation Guide](docs/INSTALLATION.md) - Detailed installation and troubleshooting

### Game Information
- [Game Mechanics Guide](docs/GAME_MECHANICS.md) - Complete gameplay mechanics documentation
- [Admin Documentation](docs/ADMIN_README.md) - Admin panel usage guide

### Development
- [Development Guide](docs/DEVELOPMENT.md) - How to contribute and develop features
- [API Documentation](docs/API_DOCUMENTATION.md) - REST API endpoints reference

### Operations
- [Fresh Start Guide](docs/FRESH-START.md) - Complete environment reset documentation
- [Database Rebuild Guide](docs/DATABASE_REBUILD.md) - Database management scripts
- [Reset Documentation](docs/RESET.md) - Various reset options
- [Production Deployment](docs/PRODUCTION_DEPLOYMENT.md) - Production deployment guide (⚠️ Security considerations)

### Comprehensive Guide
- [Comprehensive README](docs/README.md) *(German)* - Detailed German documentation

## 📁 Project Structure

```
browsergame/
├── README.md              # This file - project overview
├── index.php              # Main game interface
├── admin.php              # Admin panel
├── kaserne.php            # Military/Barracks interface
├── market.php             # Trading interface
├── map.php                # Map view
├── settlement-info.php    # Settlement details
├── docker-compose.yml     # Docker configuration
├── fresh-start.sh         # Fresh start convenience script
├── css/                   # Stylesheets
│   ├── style.css          # Main game styles
│   └── admin.css          # Admin panel styles
├── js/                    # JavaScript files
│   ├── backend.js         # Main game logic
│   ├── admin.js           # Admin panel functionality
│   ├── market.js          # Trading system
│   └── translations.js    # Language support
├── php/                   # PHP backend files
│   ├── database.php       # Database connection and operations
│   ├── backend.php        # Main API endpoints
│   ├── admin-backend.php  # Admin API endpoints
│   ├── market-backend.php # Trading API endpoints
│   └── navigation.php     # Navigation components
├── sql/                   # Database schema and migrations
│   ├── database.sql       # Main database schema
│   ├── military-units.sql # Military system tables
│   └── add-research-system.sql # Research system
├── scripts/               # Utility scripts
│   ├── docker-start.sh    # Docker startup script
│   ├── fresh-start.sh     # Complete environment reset
│   ├── install.sh         # Automatic installation
│   └── reset.sh           # Game data reset
├── docs/                  # Documentation
│   ├── README.md          # Comprehensive guide (German)
│   ├── INSTALLATION.md    # Installation troubleshooting
│   ├── ADMIN_README.md    # Admin panel documentation
│   ├── GAME_MECHANICS.md  # Complete gameplay mechanics
│   ├── DEVELOPMENT.md     # Development and contribution guide
│   ├── API_DOCUMENTATION.md # REST API endpoints
│   ├── FRESH-START.md     # Environment reset documentation
│   ├── DATABASE_REBUILD.md # Database management
│   ├── RESET.md           # Reset options
│   └── PRODUCTION_DEPLOYMENT.md # Production deployment
└── tests/                 # Test scripts for validation
    ├── test-admin-login.php     # Admin panel authentication tests
    ├── test-advanced-sql.php    # Advanced SQL functionality tests
    ├── test-barracks-upgrade.php # Military system tests
    ├── test-data-integrity.php  # Database integrity tests
    ├── test-error-scenarios.php # Error handling tests
    ├── test-sql-data-reading.php # SQL data access tests
    ├── test-validation.php      # Input validation tests
    └── test-web-interface.php   # Web interface tests
```

## 🔒 Security Notice

⚠️ **For development environments only!**

For production environments, **make sure to** change default passwords and implement additional security measures.

## 🤝 Contributing

Contributions are welcome! Please create issues or pull requests.

## 📞 Support

For problems or questions, please create an issue in the GitHub repository.