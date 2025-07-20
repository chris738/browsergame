# Test Suite Documentation

This directory contains all test files for the Browsergame project. All PHP test files have been organized and centralized here.

## Directory Structure

```
tests/
├── README.md                     # This file
├── bootstrap.php                 # Test bootstrap file
├── run-tests.php                # Main test runner
├── phptest.php                  # PHP configuration tests
├── dbtest.php                   # Database connectivity tests
│
├── 🧪 CORE FUNCTIONALITY TESTS
├── test-admin-login.php         # Admin login functionality tests
├── test-advanced-sql.php        # Advanced SQL operations tests
├── test-barracks-upgrade.php    # Building upgrade tests  
├── test-battle-calculations.php # Battle system calculations
├── test-battle-integration.php  # Battle system integration
├── test-battle-system.php       # Complete battle system
├── test-data-integrity.php      # Data integrity validation tests
├── test-error-scenarios.php     # Error handling tests
├── test-sql-data-reading.php    # SQL data reading tests
├── test-validation.php          # Input validation tests
├── test-web-interface.php       # Web interface tests
│
├── 🔍 SYSTEM VALIDATION & VERIFICATION
├── test-enhanced-views.php      # Database views functionality (moved from root)
├── test-events.sh               # Event system testing (moved from root)  
├── final-validation-script.sh   # Complete system validation (moved from root)
├── final-validation.php         # Final system validation (moved from root)
├── final-verification.php       # System verification (moved from root)
├── validate-sql.sh              # SQL validation script (moved from root)
└── verify-sql-organization.php  # SQL organization verification
```

## ✨ Recent Project Reorganization

As part of the project cleanup (December 2024), several test and validation files were moved from the project root to this organized `tests/` directory:

### Files Moved from Root → `tests/`
- `test-enhanced-views.php` - Database views testing
- `test-events.sh` - Event system testing  
- `final-validation-script.sh` - Complete system validation
- `final-validation.php` - Final system validation
- `final-verification.php` - System verification
- `validate-sql.sh` - SQL validation script

### Benefits of Organization
- ✅ **Cleaner Root Directory** - Development and validation files properly organized
- ✅ **Logical Grouping** - All tests in one location
- ✅ **Easier Maintenance** - Clear separation of concerns
- ✅ **Better CI/CD** - Easier to run all tests from single directory
- ✅ **Preserved Functionality** - All file references updated, no breaking changes

All existing scripts and references have been updated to reflect the new file locations.
├── test-web-interface.php       # Web interface tests
└── results/                     # Test results (auto-generated)
```

## Running Tests

### Quick Commands (using Makefile)

```bash
# Run all basic tests
make test

# Run only quick tests (no database required)
make test-quick

# Run database tests
make test-db

# Check PHP syntax
make test-syntax

# Run all tests including complex ones
make test-all

# Clean test artifacts
make clean
```

### Manual Test Execution

```bash
# Run individual tests
php tests/phptest.php
php tests/dbtest.php

# Run all tests
php tests/run-tests.php

# Run specific test file
php tests/test-validation.php
```

### Composer Scripts

```bash
# Run tests (when PHPUnit is available)
composer test

# Run tests with coverage
composer test-coverage
```

## Test Types

### 1. Configuration Tests (`phptest.php`)
- Verifies PHP version and configuration
- Checks required extensions
- Validates environment setup

### 2. Database Tests (`dbtest.php`)
- Tests database connectivity
- Validates basic database operations
- Reports connection status

### 3. Functionality Tests
- **Admin Login**: Tests admin authentication system
- **SQL Operations**: Advanced database query tests
- **Building Upgrades**: Tests game building mechanics
- **Data Integrity**: Validates data consistency
- **Web Interface**: Tests user interface components

## CI/CD Pipeline

The project includes a GitHub Actions pipeline (`.github/workflows/ci-cd.yml`) that:

- Tests on multiple PHP versions (8.0, 8.1, 8.2, 8.3)
- Sets up MySQL database for testing
- Runs syntax validation
- Performs security checks
- Deploys to staging/production

## Test Configuration

### PHPUnit Configuration (`phpunit.xml`)
- Bootstrap: `tests/bootstrap.php`
- Test directory: `tests/`
- Coverage reporting enabled
- JUnit XML output for CI/CD

### Composer Dependencies (`composer.json`)
- PHPUnit for unit testing
- Autoloading configuration
- Development dependencies

## Writing New Tests

When adding new test files:

1. Place them in the `tests/` directory
2. Use the naming convention: `test-feature-name.php`
3. Include proper error handling
4. Add documentation comments
5. Update this README if needed

### Test Template

```php
<?php
/**
 * Feature Test
 * Description of what this test validates
 */

class FeatureTest {
    private $testResults = [];

    public function run() {
        echo "=== Feature Test ===\n";
        
        $this->testFeatureFunction();
        $this->reportResults();
        
        return count(array_filter($this->testResults)) === count($this->testResults);
    }
    
    private function testFeatureFunction() {
        // Test implementation
        $this->testResults['feature'] = true; // or false
    }
    
    private function reportResults() {
        // Report test results
    }
}

// Run test if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new FeatureTest();
    $result = $test->run();
    exit($result ? 0 : 1);
}
```

## Troubleshooting

### Database Connection Issues
- Ensure database credentials are configured
- Check `.env` file settings
- Verify MySQL service is running
- Tests will use fallback data if database is unavailable

### Permission Issues
- Ensure test files have execute permissions
- Check write permissions for `tests/results/` directory

### CI/CD Pipeline Issues
- Check GitHub Actions logs
- Verify all required secrets are configured
- Ensure database service starts properly

## Dependencies

- PHP 8.0 or higher
- MySQL/MariaDB (for database tests)
- Composer (for dependency management)
- PHPUnit (optional, for advanced testing)

## Contributing

When contributing tests:
1. Follow existing naming conventions
2. Include proper documentation
3. Test locally before committing
4. Update this README for new test categories