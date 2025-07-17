#!/bin/bash

# Test script for rebuild-database.sh functionality
# Tests script without actually modifying database

set -e

echo "Testing rebuild-database.sh functionality..."
echo

# Test 1: Help functionality
echo "Test 1: Help functionality"
./rebuild-database.sh --help
echo "✓ Help works"
echo

# Test 2: SQL file detection
echo "Test 2: SQL file detection"
if [[ -f "../sql/database.sql" ]]; then
    echo "✓ ../sql/database.sql found"
else
    echo "✗ ../sql/database.sql not found"
    exit 1
fi
echo

# Test 3: Environment detection
echo "Test 3: Environment detection"
if [[ -f "docker-compose.yml" ]]; then
    echo "✓ docker-compose.yml found (Docker environment)"
else
    echo "✓ No docker-compose.yml (Manual environment)"
fi
echo

# Test 4: Script permissions
echo "Test 4: Script permissions"
if [[ -x "rebuild-database.sh" ]]; then
    echo "✓ rebuild-database.sh is executable"
else
    echo "✗ rebuild-database.sh is not executable"
    exit 1
fi

if [[ -x "quick-rebuild-db.sh" ]]; then
    echo "✓ quick-rebuild-db.sh is executable"
else
    echo "✗ quick-rebuild-db.sh is not executable"
    exit 1
fi
echo

# Test 5: Documentation
echo "Test 5: Documentation"
if [[ -f "DATABASE_REBUILD.md" ]]; then
    echo "✓ DATABASE_REBUILD.md found"
else
    echo "✗ DATABASE_REBUILD.md not found"
    exit 1
fi
echo

echo "All tests passed! 🎉"
echo
echo "Available scripts:"
echo "  ./rebuild-database.sh      - Main database rebuild script"
echo "  ./quick-rebuild-db.sh      - Quick rebuild (no confirmation)"
echo "  ./test-rebuild-scripts.sh  - This test script"
echo
echo "Documentation:"
echo "  DATABASE_REBUILD.md        - Comprehensive documentation"