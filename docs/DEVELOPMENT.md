# Development Guide

This guide helps developers understand how to contribute to and develop the Browser Settlement Game.

## 🛠️ Development Setup

### Prerequisites
- **Docker & Docker Compose** (recommended) OR
- **Apache/Nginx + PHP 8.0+ + MySQL/MariaDB** (manual setup)
- **Git** for version control
- **Text Editor/IDE** (VS Code, PhpStorm recommended)

### Quick Development Setup

```bash
# Clone the repository
git clone https://github.com/chris738/browsergame.git
cd browsergame

# Start development environment
./fresh-start.sh

# Access the game
# http://localhost:8080/
```

### Development URLs
- **Game**: http://localhost:8080/
- **Admin Panel**: http://localhost:8080/admin.php (admin/admin123)
- **Installation Check**: http://localhost:8080/php/installation-check.php
- **Settlement Info**: http://localhost:8080/settlement-info.php?settlementId=1
- **Map View**: http://localhost:8080/map.php
- **Market**: http://localhost:8080/market.php?settlementId=1
- **Barracks**: http://localhost:8080/kaserne.php?settlementId=1

## 🏗️ Architecture Overview

### Frontend Architecture
- **Technology**: Vanilla JavaScript, CSS3, HTML5
- **AJAX**: Used for real-time updates without page refresh
- **Responsive Design**: Mobile-friendly interface
- **Theme Support**: Light/dark theme switching

### Backend Architecture
- **Language**: PHP 8.0+
- **Database**: MySQL/MariaDB with Event Scheduler
- **API Style**: REST-like with JSON responses
- **Session Management**: PHP sessions for admin authentication

### Database Design
- **Schema**: Well-normalized with foreign key relationships
- **Event Scheduler**: Handles automatic resource generation
- **Stored Procedures**: Used for complex operations
- **Views**: Simplify data retrieval for UI

## 📁 Code Organization

### Frontend Files
```
css/
├── style.css          # Main game styles
└── admin.css          # Admin panel styles

js/
├── backend.js         # Main game API communication
├── admin.js           # Admin panel functionality
├── market.js          # Trading system
├── emoji-config.js    # Emoji definitions
├── translations.js    # Language support
└── theme-switcher.js  # Dark/light theme
```

### Backend Files
```
php/
├── database.php       # Database abstraction layer
├── backend.php        # Main game API endpoints
├── admin-backend.php  # Admin API endpoints
├── market-backend.php # Trading API endpoints
├── emoji-config.php   # Server-side emoji config
└── navigation.php     # Shared navigation component
```

### Database Files
```
sql/
├── database.sql       # Main schema and initial data
├── military-units.sql # Military system tables
├── add-research-system.sql # Research system
└── init-player.sql    # Initial player creation
```

## 🔧 Development Workflow

### 1. Setting Up Your Environment

```bash
# Use fresh start for clean environment
./fresh-start.sh

# Or manual Docker setup
docker compose up -d

# Check everything is working
curl http://localhost:8080/php/installation-check.php
```

### 2. Making Code Changes

#### Frontend Changes
1. Edit files in `css/`, `js/`, or main PHP files
2. Refresh browser to see changes
3. Check browser console for JavaScript errors
4. Test responsive design on different screen sizes

#### Backend Changes
1. Edit PHP files in `php/` directory
2. Test API endpoints using browser or curl
3. Check PHP error logs if needed
4. Validate database operations

#### Database Changes
1. Edit `sql/database.sql` for schema changes
2. Use rebuild scripts to apply changes:
   ```bash
   ./scripts/rebuild-database.sh --force
   ```
3. Test with existing data migration if needed

### 3. Testing Your Changes

#### Manual Testing
```bash
# Test main game functionality
curl "http://localhost:8080/php/backend.php?settlementId=1"

# Test admin endpoints
curl "http://localhost:8080/php/admin-backend.php?action=stats"

# Test market functionality  
curl "http://localhost:8080/php/market-backend.php?action=status&settlementId=1"
```

#### Using Test Scripts
```bash
# Run built-in validation tests
php tests/test-validation.php
php tests/test-data-integrity.php
php tests/test-web-interface.php
```

### 4. Database Development

#### Schema Modifications
1. Edit `sql/database.sql`
2. Create migration script if needed
3. Test with fresh database:
   ```bash
   ./scripts/quick-rebuild-db.sh
   ```

#### Adding New Features
1. Update database schema
2. Add backend API endpoints
3. Update frontend JavaScript
4. Add CSS styling if needed
5. Update documentation

## 🎯 Contributing Guidelines

### Code Style

#### PHP Code Style
- **PSR Standards**: Follow PSR-1 and PSR-12 where possible
- **Naming**: Use camelCase for functions, PascalCase for classes
- **Comments**: Use German for internal comments, English for public APIs
- **Error Handling**: Use try-catch blocks and meaningful error messages

```php
// Good example
function getPlayerResources($settlementId) {
    try {
        $database = new Database();
        return $database->getResources($settlementId);
    } catch (Exception $e) {
        error_log("Failed to get resources: " . $e->getMessage());
        return ['error' => 'Resource loading failed'];
    }
}
```

#### JavaScript Code Style
- **ES6+**: Use modern JavaScript features
- **Async/Await**: Prefer over callbacks for API calls
- **Constants**: Use uppercase for configuration values
- **Error Handling**: Always handle API errors gracefully

```javascript
// Good example
async function upgradeBuilding(settlementId, buildingType) {
    try {
        const response = await fetch('php/backend.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'upgrade', 
                settlementId, 
                buildingType 
            })
        });
        
        if (!response.ok) throw new Error('API request failed');
        return await response.json();
    } catch (error) {
        console.error('Upgrade failed:', error);
        showErrorMessage('Building upgrade failed');
    }
}
```

#### CSS Code Style
- **BEM Methodology**: Use Block-Element-Modifier naming
- **Mobile First**: Design for mobile, enhance for desktop
- **CSS Variables**: Use custom properties for theming
- **Comments**: Document complex styles

### Git Workflow

#### Branch Naming
- `feature/feature-name` - New features
- `fix/bug-description` - Bug fixes
- `docs/documentation-update` - Documentation changes
- `refactor/component-name` - Code refactoring

#### Commit Messages
```
feat: add military research system
fix: resolve resource overflow in storage
docs: update API documentation
refactor: simplify database connection handling
```

#### Pull Request Process
1. **Fork** the repository
2. **Create** feature branch from `main`
3. **Implement** changes with tests
4. **Update** documentation if needed
5. **Submit** pull request with description
6. **Address** review feedback

### Testing Requirements

#### Before Submitting PR
- [ ] Game loads without errors
- [ ] All API endpoints respond correctly
- [ ] Admin panel functions properly
- [ ] No JavaScript console errors
- [ ] Responsive design works on mobile
- [ ] Database operations complete successfully
- [ ] Resource generation continues working

#### Test Checklist
```bash
# 1. Fresh environment test
./fresh-start.sh --force

# 2. Basic functionality test
curl http://localhost:8080/php/backend.php?settlementId=1

# 3. Admin panel test
# Visit http://localhost:8080/admin.php

# 4. Database integrity test
php tests/test-data-integrity.php

# 5. Web interface test
php tests/test-web-interface.php
```

## 🐛 Debugging

### Common Issues

#### "Database connection failed"
```bash
# Check container status
docker compose ps

# Check database logs
docker compose logs db

# Restart database
docker compose restart db
```

#### "Event scheduler not running"
```bash
# Enable event scheduler
docker compose exec db mysql -u root -proot123 -e "SET GLOBAL event_scheduler = ON;"

# Verify events are running
docker compose exec db mysql -u browsergame -psicheresPasswort browsergame -e "SHOW EVENTS;"
```

#### JavaScript errors
- Open browser developer tools (F12)
- Check Console tab for errors
- Check Network tab for failed API calls
- Verify JSON responses are valid

### Development Tools

#### Database Access
```bash
# Access MySQL directly
docker compose exec db mysql -u browsergame -psicheresPasswort browsergame

# Run SQL queries
docker compose exec db mysql -u browsergame -psicheresPasswort browsergame -e "SELECT * FROM Settlement;"
```

#### Log Files
```bash
# Docker logs
docker compose logs -f web
docker compose logs -f db

# PHP errors (if available)
tail -f /var/log/apache2/error.log
```

#### API Testing
```bash
# Test with curl
curl -X POST http://localhost:8080/php/backend.php \
  -H "Content-Type: application/json" \
  -d '{"action":"upgrade","settlementId":1,"buildingType":"Holzfäller"}'

# Test with browser developer tools
# Use Network tab to inspect requests/responses
```

## 🚀 Deployment

### Development Deployment
Use Docker setup as described in README.md

### Production Considerations
⚠️ **This setup is for development only!**

For production deployment:
1. **Security**: Change all default passwords
2. **HTTPS**: Use SSL certificates
3. **Firewall**: Restrict database access
4. **Monitoring**: Add application monitoring
5. **Backups**: Implement regular database backups
6. **Updates**: Plan for security updates

## 📚 Resources

### Documentation
- [Game Mechanics](GAME_MECHANICS.md)
- [API Documentation](API_DOCUMENTATION.md)
- [Admin Guide](ADMIN_README.md)
- [Installation Guide](INSTALLATION.md)

### External Resources
- [PHP Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Docker Documentation](https://docs.docker.com/)
- [MDN Web Docs](https://developer.mozilla.org/)

## 💡 Ideas for Contribution

### Features to Implement
- **Battle System**: Combat between settlements
- **Alliance System**: Player cooperation
- **Quest System**: Objectives and rewards
- **Advanced Trading**: Market economics
- **Map Improvements**: Terrain, resources
- **Mobile App**: Native mobile version

### Code Improvements
- **Performance**: Optimize database queries
- **Security**: Input validation, authentication
- **Testing**: Automated test suite
- **Documentation**: Code comments, tutorials
- **Accessibility**: Screen reader support
- **Internationalization**: Multiple languages

### Infrastructure
- **CI/CD**: Automated testing and deployment
- **Monitoring**: Application performance monitoring
- **Caching**: Redis for session storage
- **Load Balancing**: Multi-server setup
- **Database Optimization**: Query optimization

## 🤝 Community

### Getting Help
- **Issues**: Create GitHub issues for bugs
- **Discussions**: Use GitHub Discussions for questions
- **Documentation**: Check existing documentation first
- **Code**: Read existing code for patterns

### Contributing
- **Start Small**: Fix typos, improve documentation
- **Discuss First**: Open issues for large features
- **Be Patient**: Reviews take time
- **Learn**: Use contributions as learning opportunities